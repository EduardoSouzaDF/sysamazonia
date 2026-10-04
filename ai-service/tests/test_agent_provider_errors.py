"""Regressions for Agno returning provider errors as RunOutput.content."""
import json
from types import SimpleNamespace
from unittest.mock import patch

import pytest
import httpx
from agno.agent import Agent
from agno.models.google import Gemini
from agno.run.base import RunStatus
from google.genai import types
from google.genai.errors import ServerError

from app.agents.evaluators import AgnoEvaluator
from app.config import Settings
from app.provider_diagnostics import ProviderError
from app.providers import ConfiguredModel
from app.schemas import TechnicalEvaluationResult


def evaluator():
    return AgnoEvaluator(Settings(_env_file=None, ai_service_token="test-" * 8))


def configured_model():
    return ConfiguredModel(Gemini(id="offline-test", api_key="fake-key"), "gemini", "offline-test")


def run(agent_evaluator):
    request = SimpleNamespace(
        correlation_id="test-correlation",
        model_dump=lambda **kwargs: {"inscricao_id": 1},
    )
    return agent_evaluator._run(configured_model(), "Offline test", request, TechnicalEvaluationResult)


def test_real_agno_converts_gemini_503_to_run_error_and_service_preserves_cause(caplog):
    body = {"error": {
        "code": 503, "status": "UNAVAILABLE", "message": "sensitive-provider-content",
    }}
    error = ServerError(503, body, httpx.Response(503, json=body))
    with patch("google.genai.models.Models.generate_content", side_effect=error) as generate:
        with pytest.raises(ProviderError) as failure:
            run(evaluator())

    assert failure.value.code == "PROVIDER_UNAVAILABLE"
    assert failure.value.issues == []
    assert generate.call_count == 1
    assert "provider_http_status=503" in caplog.text
    assert "sensitive-provider-content" not in caplog.text
    assert "extra_forbidden" not in caplog.text


@pytest.mark.parametrize("status,code", [
    (401, "PROVIDER_UNAUTHORIZED"), (403, "PROVIDER_FORBIDDEN"),
    (404, "MODEL_NOT_FOUND"), (408, "PROVIDER_TIMEOUT"),
    (429, "PROVIDER_RATE_LIMIT"), (500, "PROVIDER_UNAVAILABLE"),
    (503, "PROVIDER_UNAVAILABLE"), (504, "PROVIDER_TIMEOUT"),
])
@pytest.mark.parametrize("serialized", [False, True])
def test_failed_run_is_classified_before_schema_validation(status, code, serialized):
    content = {"error": {"code": status, "message": "private detail"}}
    response = SimpleNamespace(status=RunStatus.error, content=json.dumps(content) if serialized else content)
    with patch.object(Agent, "run", return_value=response):
        with pytest.raises(ProviderError) as failure:
            run(evaluator())
    assert failure.value.code == code
    assert failure.value.issues == []
    assert "private detail" not in str(failure.value)


@pytest.mark.parametrize("content", [None, "sensitive error text", [], {"error": {"code": []}}])
def test_unstructured_failed_run_is_not_misreported_as_invalid_evaluation(content):
    with patch.object(Agent, "run", return_value=SimpleNamespace(status=RunStatus.error, content=content)):
        with pytest.raises(ProviderError) as failure:
            run(evaluator())
    assert failure.value.code == "PROVIDER_UNAVAILABLE"


def test_completed_but_malformed_response_still_fails_schema_validation():
    with patch.object(Agent, "run", return_value=SimpleNamespace(status=RunStatus.completed, content={"error": {"code": 503}})):
        with pytest.raises(ProviderError) as failure:
            run(evaluator())
    assert failure.value.code == "PROVIDER_INVALID_RESPONSE"
    assert failure.value.issues


def test_valid_gemini_response_is_still_accepted():
    content = {"inscricao_id": 1, "avaliador_id": 7, "prompt_version": "v1", "criterios": [
        {"criterio_id": 1, "nota": 5, "justificativa": " ".join(["evidência"] * 60)},
    ]}
    response = types.GenerateContentResponse(candidates=[types.Candidate(
        content=types.Content(role="model", parts=[types.Part(text=json.dumps(content))]), finish_reason="STOP",
    )])
    with patch("google.genai.models.Models.generate_content", return_value=response):
        result = run(evaluator())
    assert result.inscricao_id == 1
    assert result.criterios[0].nota == 5
