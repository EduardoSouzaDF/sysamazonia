from dataclasses import dataclass
from typing import Literal

from agno.models.base import Model
from agno.models.google import Gemini
from agno.models.openai import OpenAIChat

from app.config import Settings
from app.schemas.evaluation import RuntimeConfiguration

AgentKind = Literal["technical", "selection"]


class LlmConfigurationError(RuntimeError):
    code = "LLM_CONFIGURATION_ERROR"


@dataclass(frozen=True)
class ConfiguredModel:
    model: Model
    provider: str
    model_id: str


class LlmProviderFactory:
    def __init__(self, settings: Settings) -> None:
        self._settings = settings

    def create(self, agent_kind: AgentKind, runtime: RuntimeConfiguration | None = None) -> ConfiguredModel:
        provider, model_id = self._resolve(agent_kind)
        if runtime is not None:
            provider = runtime.provider or provider
            model_id = runtime.model or model_id

        if provider == "openai":
            api_key = self._required_key(runtime.api_key if runtime and runtime.api_key else self._settings.openai_api_key, "OPENAI_API_KEY")
            model = OpenAIChat(id=model_id, api_key=api_key, timeout=runtime.timeout if runtime else self._settings.llm_timeout)
        elif provider == "gemini":
            api_key = self._required_key(runtime.api_key if runtime and runtime.api_key else self._settings.gemini_api_key, "GEMINI_API_KEY")
            model = Gemini(id=model_id, api_key=api_key, timeout=runtime.timeout if runtime else self._settings.llm_timeout)
        else:
            raise LlmConfigurationError(f"Unsupported LLM provider: {provider}")

        return ConfiguredModel(model=model, provider=provider, model_id=model_id)

    def _resolve(self, agent_kind: AgentKind) -> tuple[str, str]:
        if agent_kind == "technical":
            provider_override = self._settings.ai_technical_llm_provider
            model_override = self._settings.ai_technical_llm_model
        else:
            provider_override = self._settings.ai_selection_llm_provider
            model_override = self._settings.ai_selection_llm_model

        provider = (provider_override or self._settings.llm_provider).strip().lower()
        model_id = (model_override or self._settings.llm_model or "").strip()
        if not provider:
            raise LlmConfigurationError("LLM provider is not configured")
        if not model_id:
            raise LlmConfigurationError(f"LLM model is not configured for provider: {provider}")

        return provider, model_id

    @staticmethod
    def _required_key(value: str | None, variable: str) -> str:
        if value is None or not value.strip():
            raise LlmConfigurationError(f"Required credential is not configured: {variable}")

        return value
