from functools import lru_cache

from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    ai_service_token: str = Field(min_length=32)
    llm_provider: str = "openai"
    llm_model: str | None = None
    ai_technical_llm_provider: str | None = None
    ai_technical_llm_model: str | None = None
    ai_selection_llm_provider: str | None = None
    ai_selection_llm_model: str | None = None
    openai_api_key: str | None = None
    gemini_api_key: str | None = None
    llm_timeout: int = Field(default=45, ge=1, le=55)
    knowledge_version: str | None = None


@lru_cache
def get_settings() -> Settings:
    return Settings()  # type: ignore[call-arg]
