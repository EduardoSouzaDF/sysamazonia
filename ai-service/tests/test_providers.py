from unittest.mock import patch

import pytest

from app.config import Settings
from app.providers import LlmConfigurationError, LlmProviderFactory
from app.schemas.evaluation import RuntimeConfiguration


def settings(**overrides):
    values = {
        "ai_service_token": "x" * 32,
        "llm_provider": "openai",
        "llm_model": "global-model",
        "openai_api_key": "openai-test-key",
        "gemini_api_key": "gemini-test-key",
    }
    values.update(overrides)
    return Settings(_env_file=None, **values)


@patch("app.providers.OpenAIChat")
def test_openai_provider_creates_openai_model(openai_model):
    configured = LlmProviderFactory(settings()).create("technical")

    openai_model.assert_called_once_with(id="global-model", api_key="openai-test-key", timeout=45)
    assert configured.provider == "openai"
    assert configured.model_id == "global-model"


@patch("app.providers.Gemini")
def test_gemini_provider_creates_gemini_model(gemini_model):
    configured = LlmProviderFactory(settings(llm_provider="gemini")).create("technical")

    gemini_model.assert_called_once_with(id="global-model", api_key="gemini-test-key", timeout=45)
    assert configured.provider == "gemini"


def test_missing_gemini_key_is_a_safe_configuration_error():
    with pytest.raises(LlmConfigurationError, match="GEMINI_API_KEY"):
        LlmProviderFactory(settings(llm_provider="gemini", gemini_api_key=None)).create("technical")


def test_missing_openai_key_is_a_safe_configuration_error():
    with pytest.raises(LlmConfigurationError, match="OPENAI_API_KEY"):
        LlmProviderFactory(settings(openai_api_key=None)).create("technical")


def test_unsupported_provider_does_not_fallback():
    with pytest.raises(LlmConfigurationError, match="Unsupported LLM provider"):
        LlmProviderFactory(settings(llm_provider="claude")).create("technical")


@patch("app.providers.Gemini")
def test_technical_provider_and_model_override_global_values(gemini_model):
    configured = LlmProviderFactory(
        settings(ai_technical_llm_provider="gemini", ai_technical_llm_model="technical-model")
    ).create("technical")

    gemini_model.assert_called_once_with(id="technical-model", api_key="gemini-test-key", timeout=45)
    assert configured.provider == "gemini"
    assert configured.model_id == "technical-model"


@patch("app.providers.OpenAIChat")
def test_selection_provider_and_model_override_global_values(openai_model):
    configured = LlmProviderFactory(
        settings(
            llm_provider="gemini",
            ai_selection_llm_provider="openai",
            ai_selection_llm_model="selection-model",
        )
    ).create("selection")

    openai_model.assert_called_once_with(id="selection-model", api_key="openai-test-key", timeout=45)
    assert configured.provider == "openai"
    assert configured.model_id == "selection-model"


@patch("app.providers.OpenAIChat")
def test_runtime_configuration_safely_overrides_provider_model_key_and_timeout(openai_model):
    runtime = RuntimeConfiguration(
        provider="openai", model="runtime-model", api_key="runtime-secret",
        prompt_version="technical_evaluator_v2", timeout=20,
    )
    configured = LlmProviderFactory(settings(llm_provider="gemini")).create("technical", runtime)
    openai_model.assert_called_once_with(id="runtime-model", api_key="runtime-secret", timeout=20)
    assert configured.provider == "openai"
