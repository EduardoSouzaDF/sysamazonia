from hmac import compare_digest
from typing import Annotated

from fastapi import Depends, FastAPI, Header, HTTPException, Request, status
from fastapi.responses import JSONResponse
from pydantic import ValidationError

from app.agents import AgnoEvaluator
from app.config import Settings, get_settings
from app.providers import LlmConfigurationError, LlmProviderFactory
from app.schemas import SelectionRequest, SelectionResult, TechnicalEvaluationRequest, TechnicalEvaluationResult

app = FastAPI(title="Sisamazonia AI Evaluation", version="1.0.0", docs_url=None, redoc_url=None)


@app.exception_handler(LlmConfigurationError)
def llm_configuration_error(_request: Request, exception: LlmConfigurationError) -> JSONResponse:
    return JSONResponse(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        content={"code": exception.code, "detail": str(exception)},
    )


def validated_settings() -> Settings:
    try:
        return get_settings()
    except ValidationError as exception:
        raise LlmConfigurationError("AI service configuration is invalid") from exception


def authorize(
    authorization: Annotated[str | None, Header()] = None,
    settings: Settings = Depends(validated_settings),
) -> None:
    expected = f"Bearer {settings.ai_service_token}"
    if authorization is None or not compare_digest(authorization, expected):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Não autorizado")


def evaluator(request: Request, settings: Settings = Depends(validated_settings)) -> AgnoEvaluator:
    override = getattr(request.app.state, "evaluator", None)
    return override if override is not None else AgnoEvaluator(settings)


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.get("/ready", dependencies=[Depends(authorize)])
def readiness(settings: Settings = Depends(validated_settings)) -> dict[str, str]:
    factory = LlmProviderFactory(settings)
    factory.create("technical")
    factory.create("selection")

    return {"status": "ready"}


@app.post("/v1/evaluations/technical", response_model=TechnicalEvaluationResult, dependencies=[Depends(authorize)])
def technical_evaluation(
    payload: TechnicalEvaluationRequest,
    service: AgnoEvaluator = Depends(evaluator),
) -> TechnicalEvaluationResult:
    return service.technical(payload)


@app.post("/v1/evaluations/selection", response_model=SelectionResult, dependencies=[Depends(authorize)])
def strategic_selection(payload: SelectionRequest, service: AgnoEvaluator = Depends(evaluator)) -> SelectionResult:
    return service.selection(payload)
