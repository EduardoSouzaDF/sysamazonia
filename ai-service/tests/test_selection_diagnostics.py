import json
from types import SimpleNamespace
from unittest.mock import Mock, patch

import pytest

from app.agents.evaluators import AgnoEvaluator
from app.provider_diagnostics import ProviderError
from app.schemas import SelectionRequest
from test_api import payload, settings


def selection_request():
    data = payload()
    data.pop('criterios')
    data['avaliacoes'] = [{'criterio_id': 1, 'nota': 4}]
    data['runtime']['prompt'] = 'Produza uma justificativa curta.'
    return SelectionRequest.model_validate(data)


@pytest.mark.parametrize('words,valid', [(49, False), (50, True), (150, True), (151, False)])
@pytest.mark.parametrize('as_json', [False, True])
def test_selection_limits_and_safe_diagnostics(words, valid, as_json, caplog):
    request = selection_request()
    result = {'inscricao_id': request.inscricao_id, 'prompt_version': request.prompt_version,
              'indicacao': 'INDICADA', 'justificativa': ' '.join(['private'] * words)}
    client = Mock()
    configured = SimpleNamespace(model=SimpleNamespace(http_client=client), provider='mock', model_id='test')
    factory = Mock()
    factory.create.return_value = configured
    with patch('app.agents.evaluators.Agent') as agent:
        agent.return_value.run.return_value.content = json.dumps(result) if as_json else result
        evaluator = AgnoEvaluator(settings(), factory)
        if valid:
            assert evaluator.selection(request).indicacao.value == 'INDICADA'
        else:
            with pytest.raises(ProviderError) as raised:
                evaluator.selection(request)
            assert raised.value.code == 'PROVIDER_INVALID_RESPONSE'
            assert 'justificativa' in caplog.text
            assert 'justification_word_count' in caplog.text
            assert str(request.correlation_id) in caplog.text
        assert '50 e 150 palavras' in agent.call_args.kwargs['instructions']
        assert '2000 caracteres' in agent.call_args.kwargs['instructions']
    client.close.assert_called_once()
    assert 'private' not in caplog.text
    assert 'test-key-value' not in caplog.text


def test_unexpected_exception_logs_type_without_message(caplog):
    configured = SimpleNamespace(model=SimpleNamespace(http_client=None), provider='mock', model_id='test')
    factory = Mock()
    factory.create.return_value = configured
    with patch('app.agents.evaluators.Agent') as agent:
        agent.return_value.run.side_effect = RuntimeError('private-provider-secret')
        with pytest.raises(ProviderError):
            AgnoEvaluator(settings(), factory).selection(selection_request())
    assert 'RuntimeError' in caplog.text
    assert 'private-provider-secret' not in caplog.text


def test_unknown_response_fields_are_not_logged(caplog):
    configured = SimpleNamespace(model=SimpleNamespace(http_client=None), provider='mock', model_id='test')
    factory = Mock()
    factory.create.return_value = configured
    request = selection_request()
    result = {'inscricao_id': request.inscricao_id, 'prompt_version': request.prompt_version,
              'indicacao': 'INDICADA', 'justificativa': ' '.join(['private'] * 50),
              'private-field-name': 'private-value'}
    with patch('app.agents.evaluators.Agent') as agent:
        agent.return_value.run.return_value.content = result
        with pytest.raises(ProviderError):
            AgnoEvaluator(settings(), factory).selection(request)
    assert 'extra_forbidden' in caplog.text
    assert 'unknown' in caplog.text
    assert 'private' not in caplog.text
