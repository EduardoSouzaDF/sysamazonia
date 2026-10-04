from dataclasses import dataclass
from typing import Literal

import httpx
from agno.models.base import Model
from agno.models.google import Gemini
from agno.models.openai import OpenAIChat

from app.config import Settings
from app.schemas.evaluation import RuntimeConfiguration
from app.transport import BoundedTransport, EndpointError, validate_endpoint

AgentKind = Literal["technical", "selection"]


class LlmConfigurationError(RuntimeError):
    code = "LLM_CONFIGURATION_ERROR"


@dataclass(frozen=True)
class ConfiguredModel:
    model: Model
    provider: str
    model_id: str


class LlmProviderFactory:
    COMPATIBLE_URLS = {'mistral': 'https://api.mistral.ai/v1', 'groq': 'https://api.groq.com/openai/v1'}

    def __init__(self, settings: Settings) -> None:
        self._settings = settings

    def resolve(self, agent_kind: AgentKind, runtime: RuntimeConfiguration | None = None):
        provider = (runtime.provider if runtime else None) or getattr(self._settings, f'ai_{agent_kind}_llm_provider') or self._settings.llm_provider
        model_id = (runtime.model if runtime else None) or getattr(self._settings, f'ai_{agent_kind}_llm_model') or self._settings.llm_model
        provider = provider.strip().lower()
        if provider not in ('openai', 'gemini', 'anthropic', 'mistral', 'groq', 'openai-compatible', 'local'):
            raise LlmConfigurationError('Unsupported LLM provider')
        if not model_id or not model_id.strip():
            raise LlmConfigurationError('LLM model is not configured')
        key = runtime.api_key if runtime else None
        key = key or getattr(self._settings, f'{provider}_api_key', None)
        if provider != 'local':
            key = self._required_key(key, f'{provider.upper()}_API_KEY')
        url = self.COMPATIBLE_URLS.get(provider)
        if provider in ('local', 'openai-compatible'):
            try:
                url = validate_endpoint(runtime.base_url if runtime and runtime.base_url else '', provider,
                    self._settings.ai_local_allowed_urls if provider == 'local' else self._settings.ai_custom_allowed_urls)
            except EndpointError as error:
                raise LlmConfigurationError(str(error)) from None
            if provider == 'local' and runtime.local_type == 'ollama':
                url += '/v1'
        return provider, model_id.strip(), key, url, runtime.timeout if runtime else self._settings.llm_timeout

    def create(self, agent_kind: AgentKind, runtime: RuntimeConfiguration | None = None) -> ConfiguredModel:
        provider, model_id, key, url, timeout = self.resolve(agent_kind, runtime)
        builders = {'openai': self._openai, 'gemini': self._gemini, 'anthropic': self._anthropic}
        model = builders[provider](model_id, key, url, timeout) if provider in builders else self._compatible(model_id, key, url, timeout, provider != "local")
        return ConfiguredModel(model=model, provider=provider, model_id=model_id)

    @staticmethod
    def _openai(model_id, key, url, timeout):
        return OpenAIChat(id=model_id, api_key=key, timeout=timeout)

    @staticmethod
    def _gemini(model_id, key, url, timeout):
        return Gemini(id=model_id, api_key=key, timeout=timeout)

    @staticmethod
    def _anthropic(model_id, key, url, timeout):
        from agno.models.anthropic import Claude
        return Claude(id=model_id, api_key=key, timeout=timeout, max_tokens=4096)

    @staticmethod
    def _compatible(model_id, key, url, timeout, public_only=True):
        return OpenAIChat(id=model_id, api_key=key or 'local-no-auth', base_url=url, timeout=timeout,
            max_retries=0, supports_native_structured_outputs=False,
            http_client=httpx.Client(transport=BoundedTransport(public_only=public_only), follow_redirects=False, trust_env=False, timeout=timeout))

    @staticmethod
    def _required_key(value: str | None, variable: str) -> str:
        if value is None or not value.strip():
            raise LlmConfigurationError(f'Required credential is not configured: {variable}')
        return value
