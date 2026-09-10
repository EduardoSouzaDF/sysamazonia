from functools import lru_cache
from pathlib import Path

from pydantic import Field, field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=Path(__file__).resolve().parents[1] / ".env", env_file_encoding="utf-8", extra="ignore", hide_input_in_errors=True)

    ai_service_token: str = Field(min_length=32, repr=False)

    @field_validator("ai_service_token")
    @classmethod
    def valid_token(cls, value: str) -> str:
        if any(ord(c) < 33 or ord(c) > 126 for c in value):
            raise ValueError("AI_SERVICE_TOKEN must be ASCII without whitespace")
        return value
    llm_provider: str = "openai"
    llm_model: str | None = None
    ai_technical_llm_provider: str | None = None
    ai_technical_llm_model: str | None = None
    ai_selection_llm_provider: str | None = None
    ai_selection_llm_model: str | None = None
    openai_api_key: str | None = None
    gemini_api_key: str | None = None
    anthropic_api_key: str | None = None
    mistral_api_key: str | None = None
    groq_api_key: str | None = None
    # Exact base URLs, controlled by infrastructure, never by the browser.
    ai_local_allowed_urls: str = "http://127.0.0.1:11434"
    ai_custom_allowed_urls: str = ""
    llm_timeout: int = Field(default=45, ge=1, le=55)
    knowledge_version: str | None = None
    knowledge_max_chars: int = Field(default=50000, ge=1000, le=200000)


@lru_cache
def get_settings() -> Settings:
    return Settings()  # type: ignore[call-arg]
