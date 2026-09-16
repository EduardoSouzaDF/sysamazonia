"""Read-only metadata checks: never generate evaluations or consume inference tokens."""
import re
from urllib.parse import quote

import httpx

from app.providers import LlmProviderFactory
from app.transport import BoundedTransport, EndpointError


class ProviderError(RuntimeError):
    def __init__(self, code: str):
        self.code = code
        super().__init__('Falha operacional no provider.')


def provider_models(factory: LlmProviderFactory, runtime, check_model=False):
    provider, model, key, base, timeout = factory.resolve('technical', runtime)
    base = base or {'openai': 'https://api.openai.com/v1', 'gemini': 'https://generativelanguage.googleapis.com/v1beta', 'anthropic': 'https://api.anthropic.com/v1'}[provider]
    headers = {'Authorization': f'Bearer {key}'} if key else {}
    if provider == 'gemini':
        headers = {'x-goog-api-key': key}
    elif provider == 'anthropic':
        headers = {'x-api-key': key, 'anthropic-version': '2023-06-01'}
    url = base + '/models'
    # Metadata lookup checks existence/access without inference cost.
    if check_model and provider in ('openai', 'gemini', 'anthropic', 'local'):
        url += '/' + quote(model.removeprefix('models/'), safe='')
    try:
        with httpx.Client(transport=BoundedTransport(public_only=provider != "local"), follow_redirects=False, trust_env=False, timeout=min(timeout, 15)) as client:
            response = client.get(url, headers=headers)
        codes = {401:'PROVIDER_UNAUTHORIZED',403:'PROVIDER_FORBIDDEN',404:'MODEL_NOT_FOUND',429:'PROVIDER_RATE_LIMIT',408:'PROVIDER_TIMEOUT'}
        if not response.is_success:
            raise ProviderError(codes.get(response.status_code, 'PROVIDER_UNAVAILABLE'))
        data = response.json()
        if not isinstance(data, dict):
            raise ProviderError('PROVIDER_INVALID_RESPONSE')
        if check_model and url != base + '/models':
            if not isinstance(data.get('id') or data.get('name'), str):
                raise ProviderError('PROVIDER_INVALID_RESPONSE')
            return [model]
        items = data.get('models' if provider == 'gemini' else 'data')
        if not isinstance(items, list):
            raise ProviderError('PROVIDER_INVALID_RESPONSE')
        models = sorted({name.removeprefix('models/') for item in items if isinstance(item, dict)
            and isinstance(name := item.get('name' if provider == 'gemini' else 'id'), str)
            and re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9._:/-]{0,119}', name)})[:1000]
        if check_model and model not in models:
            raise ProviderError('MODEL_NOT_FOUND')
        return models
    except httpx.TimeoutException:
        raise ProviderError('PROVIDER_TIMEOUT') from None
    except httpx.RequestError:
        raise ProviderError('LOCAL_UNAVAILABLE' if provider == 'local' else 'PROVIDER_UNAVAILABLE') from None
    except (ValueError, EndpointError):
        raise ProviderError('PROVIDER_INVALID_RESPONSE') from None
