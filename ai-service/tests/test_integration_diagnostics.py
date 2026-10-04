from unittest.mock import patch
import logging

import httpx
import pytest
from fastapi.testclient import TestClient
from pydantic import ValidationError

from app.config import Settings, get_settings
from app.main import app
from app.providers import LlmProviderFactory
from app.provider_diagnostics import ProviderError, provider_models
from app.schemas.evaluation import RuntimeConfiguration
from app.safe_logging import ProviderLogFilter
from app.transport import EndpointError, validate_endpoint

TOKEN = 'test-only-internal-token-' + 'x' * 32


def settings(**kwargs):
    return Settings(_env_file=None, ai_service_token=TOKEN, llm_model='test-model', **kwargs)


def runtime(**kwargs):
    return RuntimeConfiguration(**({'provider': 'local', 'model': 'test-model', 'base_url': 'http://127.0.0.1:11434', 'prompt_version': 'technical_evaluator_v1'} | kwargs))


def test_environment_path_is_independent_of_cwd(tmp_path, monkeypatch):
    from pathlib import Path
    assert Path(Settings.model_config['env_file']).is_absolute()
    monkeypatch.chdir(tmp_path)
    monkeypatch.setenv('AI_SERVICE_TOKEN', TOKEN)
    get_settings.cache_clear()
    assert get_settings().ai_service_token == TOKEN
    get_settings.cache_clear()


@pytest.mark.parametrize('token', ['', 'short', 'x' * 32 + '\n', 'é' * 32])
def test_invalid_token_fails_safely(token):
    with pytest.raises(ValidationError) as error:
        Settings(_env_file=None, ai_service_token=token)
    assert "input_value=" not in str(error.value)


def test_http_auth_health_and_restart(monkeypatch):
    monkeypatch.setenv('AI_SERVICE_TOKEN', TOKEN)
    for _ in range(2):
        get_settings.cache_clear()
        with TestClient(app) as client:
            assert client.get('/health').json() == {'status': 'ok'}
            assert client.get('/v1/diagnostics').status_code == 401
            assert client.get('/v1/diagnostics', headers={'Authorization': 'Bearer wrong'}).status_code == 401
            assert client.get('/v1/diagnostics', headers={'Authorization': 'Bearer ' + TOKEN}).status_code == 200
    get_settings.cache_clear()


@pytest.mark.parametrize('provider', ['openai','gemini','anthropic','mistral','groq','openai-compatible','local'])
def test_all_adapters_construct(provider):
    r = runtime(provider=provider, api_key=None if provider == 'local' else 'test-only-provider-key', base_url='https://llm.example.org/v1' if provider == 'openai-compatible' else 'http://127.0.0.1:11434')
    configured = LlmProviderFactory(settings(ai_custom_allowed_urls='https://llm.example.org/v1')).create('technical', r)
    assert configured.provider == provider
    assert configured.model_id == 'test-model'
    if getattr(configured.model, 'http_client', None):
        configured.model.http_client.close()


@pytest.mark.parametrize('url', ['file:///etc/passwd','http://169.254.169.254','http://example.com','http://127.0.0.1:11434?key=x','http://user:pass@127.0.0.1:11434','http://127.0.0.1:11434/../admin','http://[::ffff:169.254.169.254]'])
def test_local_ssrf_rejected_even_if_allowlisted(url):
    with pytest.raises(EndpointError):
        validate_endpoint(url, 'local', url)


def test_allowlist_required_and_private_ip_supported():
    with pytest.raises(EndpointError):
        validate_endpoint('http://10.0.0.1:8000/v1', 'local', '')
    assert validate_endpoint('http://10.0.0.1:8000/v1', 'local', 'http://10.0.0.1:8000/v1')


@pytest.mark.parametrize('status,code', [(401,'PROVIDER_UNAUTHORIZED'),(403,'PROVIDER_FORBIDDEN'),(404,'MODEL_NOT_FOUND'),(429,'PROVIDER_RATE_LIMIT'),(500,'PROVIDER_UNAVAILABLE')])
def test_provider_failures_are_sanitized(status, code):
    with patch('app.provider_diagnostics.httpx.Client') as client:
        client.return_value.__enter__.return_value.get.return_value = httpx.Response(status, json={'error':'secret-key'})
        with pytest.raises(ProviderError) as error:
            provider_models(LlmProviderFactory(settings()), runtime())
        assert error.value.code == code
        assert 'secret-key' not in str(error.value)


@pytest.mark.parametrize('error,code', [(httpx.ConnectError('secret'),'LOCAL_UNAVAILABLE'), (httpx.ReadTimeout('secret'),'PROVIDER_TIMEOUT')])
def test_transport_failures(error, code):
    with patch('app.provider_diagnostics.httpx.Client') as client:
        client.return_value.__enter__.return_value.get.side_effect = error
        with pytest.raises(ProviderError) as raised:
            provider_models(LlmProviderFactory(settings()), runtime())
        assert raised.value.code == code


def test_model_list_and_metadata_check_make_no_inference_call():
    with patch('app.provider_diagnostics.httpx.Client') as client:
        get = client.return_value.__enter__.return_value.get
        get.return_value = httpx.Response(200, json={'data':[{'id':'test-model'}]})
        assert provider_models(LlmProviderFactory(settings()), runtime()) == ['test-model']
        assert get.call_args.args[0] == 'http://127.0.0.1:11434/v1/models'
        get.return_value = httpx.Response(200, json={'id':'test-model'})
        assert provider_models(LlmProviderFactory(settings()), runtime(), True) == ['test-model']


def test_invalid_response_and_model_rejected():
    with patch('app.provider_diagnostics.httpx.Client') as client:
        client.return_value.__enter__.return_value.get.return_value = httpx.Response(200, json={'wrong': []})
        with pytest.raises(ProviderError):
            provider_models(LlmProviderFactory(settings()), runtime())
    with pytest.raises(ValidationError):
        runtime(model='bad\nmodel')


def test_sdk_log_filter_does_not_emit_secrets():
    record = logging.LogRecord('agno', logging.ERROR, '', 0, 'Authorization Bearer secret-key %s', ('private-token',), None)
    ProviderLogFilter().filter(record)
    assert 'secret-key' not in record.getMessage()
    assert 'private-token' not in record.getMessage()


def test_bounded_transport_rejects_redirects_and_large_response():
    from app.transport import BoundedTransport
    for response in [httpx.Response(302, headers={'location': 'http://169.254.169.254'}), httpx.Response(200, content=b'x' * 30)]:
        transport = BoundedTransport(limit=10)
        with patch.object(transport.inner, 'handle_request', return_value=response):
            with pytest.raises(EndpointError):
                transport.handle_request(httpx.Request('GET', 'http://127.0.0.1:11434/v1/models'))
        transport.close()


def test_remote_dns_rebinding_to_private_address_is_blocked():
    from app.transport import BoundedTransport
    transport = BoundedTransport(public_only=True)
    with patch('app.transport.socket.getaddrinfo', return_value=[(2, 1, 6, '', ('127.0.0.1', 443))]):
        with pytest.raises(EndpointError):
            transport.handle_request(httpx.Request('GET', 'https://approved.example.org/v1/models'))
    transport.close()


@pytest.mark.parametrize('valid', [True, False])
def test_local_openai_protocol_runs_existing_evaluator_and_validates_schema(valid):
    import json
    from app.agents.evaluators import AgnoEvaluator
    from app.schemas import TechnicalEvaluationRequest
    from test_api import payload, valid_justification
    data = payload()
    data['runtime'] = runtime().model_dump()
    result = {'inscricao_id': 1, 'avaliador_id': 2, 'prompt_version': 'technical_evaluator_v1',
              'criterios': [{'criterio_id': 1, 'nota': 4, 'justificativa': valid_justification()}]}
    content = json.dumps(result) if valid else 'invalid provider output'
    def respond(request):
        assert str(request.url) == 'http://127.0.0.1:11434/v1/chat/completions'
        return httpx.Response(200, request=request, json={'id':'test-response','object':'chat.completion','created':1,'model':'test-model',
            'choices':[{'index':0,'message':{'role':'assistant','content':content},'finish_reason':'stop'}],
            'usage':{'prompt_tokens':1,'completion_tokens':1,'total_tokens':2}})
    with patch('app.transport.BoundedTransport.handle_request', side_effect=respond):
        if valid:
            response = AgnoEvaluator(settings()).technical(TechnicalEvaluationRequest.model_validate(data))
            assert response.criterios[0].nota == 4
            assert response.provider == 'local'
        else:
            with pytest.raises(ProviderError):
                AgnoEvaluator(settings()).technical(TechnicalEvaluationRequest.model_validate(data))
