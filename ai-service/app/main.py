from hmac import compare_digest
from contextlib import asynccontextmanager
from typing import Annotated

from fastapi import Depends, FastAPI, Header, HTTPException, Request, status
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from pydantic import ValidationError

from app.agents import AgnoEvaluator
from app.config import Settings, get_settings
from app.provider_diagnostics import ProviderError, provider_models
from app.providers import LlmConfigurationError, LlmProviderFactory
from app.schemas import SelectionRequest, SelectionResult, TechnicalEvaluationRequest, TechnicalEvaluationResult
from app.schemas.evaluation import RuntimeConfiguration

@asynccontextmanager
async def lifespan(_app: FastAPI):
    try:
        get_settings()
    except ValidationError:
        raise RuntimeError("Configuração FastAPI inválida: confira AI_SERVICE_TOKEN (32+ caracteres ASCII, sem espaços) e ai-service/.env; reiniciar não gera tokens.") from None
    yield


app = FastAPI(title="Sisamazonia AI Evaluation", version="1.0.0", docs_url=None, redoc_url=None, lifespan=lifespan)


@app.exception_handler(RequestValidationError)
def request_validation_error(_request: Request, _exception: RequestValidationError) -> JSONResponse:
    # A resposta padrão inclui o valor de entrada e poderia refletir uma API key inválida.
    return JSONResponse(
        status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
        content={"code": "INVALID_REQUEST", "detail": "Requisição inválida."},
    )


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
    if authorization is None or not compare_digest(authorization.encode("utf-8"), expected.encode("utf-8")):
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


@app.get("/v1/diagnostics", dependencies=[Depends(authorize)])
def diagnostics() -> dict[str, str]:
    return {"status": "ok", "authentication": "ok"}


@app.post("/v1/configuration/test", dependencies=[Depends(authorize)])
def test_configuration(payload: RuntimeConfiguration, settings: Settings = Depends(validated_settings)) -> dict[str, str]:
    factory = LlmProviderFactory(settings)
    provider, model, *_ = factory.resolve("technical", payload)
    provider_models(factory, payload, check_model=True)
    return {"status": "ready", "provider": provider, "model": model}


@app.post("/v1/evaluations/technical", response_model=TechnicalEvaluationResult, dependencies=[Depends(authorize)])
def technical_evaluation(
    payload: TechnicalEvaluationRequest,
    service: AgnoEvaluator = Depends(evaluator),
) -> TechnicalEvaluationResult:
    return service.technical(payload)


@app.post("/v1/evaluations/selection", response_model=SelectionResult, dependencies=[Depends(authorize)])
def strategic_selection(payload: SelectionRequest, service: AgnoEvaluator = Depends(evaluator)) -> SelectionResult:
    return service.selection(payload)


@app.exception_handler(ProviderError)
def provider_error(_request: Request, exception: ProviderError) -> JSONResponse:
    return JSONResponse(status_code=502, content={"code": exception.code, "detail": "Falha operacional no provider."})


@app.post("/v1/configuration/models", dependencies=[Depends(authorize)])
def list_models(payload: RuntimeConfiguration, settings: Settings = Depends(validated_settings)):
    return {"models": provider_models(LlmProviderFactory(settings), payload)}
