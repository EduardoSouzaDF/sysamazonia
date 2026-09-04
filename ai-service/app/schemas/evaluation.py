from enum import StrEnum
from typing import Literal
from uuid import UUID

from pydantic import BaseModel, ConfigDict, Field, field_validator, model_validator


def word_count(value: str) -> int:
    return len(value.split())


class StrictModel(BaseModel):
    model_config = ConfigDict(extra="forbid")


class Proposal(StrictModel):
    titulo: str = Field(min_length=1, max_length=255)
    resumo: str = Field(min_length=1)
    objetivo: str = Field(min_length=1)
    desenvolvimento: str = Field(min_length=1)
    conclusao: str = Field(min_length=1)


class Criterion(StrictModel):
    criterio_id: int = Field(gt=0)
    nome: str = Field(min_length=1)
    descricao: str | None = None
    nota_minima: float
    nota_maxima: float
    rubrica: dict[str, object] = Field(default_factory=dict)
    rubric_version: str | None = None

    @model_validator(mode="after")
    def valid_range(self) -> "Criterion":
        if self.nota_minima > self.nota_maxima:
            raise ValueError("nota_minima não pode superar nota_maxima")
        return self


class CriterionEvaluation(StrictModel):
    criterio_id: int = Field(gt=0)
    nota: int
    justificativa: str = Field(min_length=10, max_length=2000)

    @field_validator("justificativa")
    @classmethod
    def valid_justification_length(cls, value: str) -> str:
        if not 50 <= word_count(value) <= 150:
            raise ValueError("justificativa deve conter entre 50 e 150 palavras")
        return value


class RuntimeConfiguration(StrictModel):
    provider: Literal["openai", "gemini"] | None = None
    model: str | None = Field(default=None, min_length=1, max_length=120)
    api_key: str | None = Field(default=None, min_length=8, max_length=1000)
    prompt: str | None = Field(default=None, max_length=100000)
    prompt_version: str = Field(min_length=1, max_length=80)
    knowledge_version: str | None = Field(default=None, max_length=80)
    timeout: int = Field(default=45, ge=1, le=55)


class TechnicalEvaluationRequest(StrictModel):
    inscricao_id: int = Field(gt=0)
    avaliador_id: int = Field(gt=0)
    correlation_id: UUID
    prompt_version: str = Field(min_length=1, max_length=80)
    evaluation_configuration_hash: str = Field(pattern=r"^[a-f0-9]{64}$")
    inscricao: Proposal
    criterios: list[Criterion] = Field(min_length=1)
    runtime: RuntimeConfiguration | None = None

    @model_validator(mode="after")
    def matching_prompt_version(self) -> "TechnicalEvaluationRequest":
        if self.runtime is not None and self.prompt_version != self.runtime.prompt_version:
            raise ValueError("versão do prompt não corresponde à configuração")
        return self

    @model_validator(mode="after")
    def unique_criteria(self) -> "TechnicalEvaluationRequest":
        ids = [item.criterio_id for item in self.criterios]
        if len(ids) != len(set(ids)):
            raise ValueError("criterio_id deve ser único")
        return self


class TechnicalEvaluationResult(StrictModel):
    inscricao_id: int = Field(gt=0)
    avaliador_id: int = Field(gt=0)
    prompt_version: str
    provider: str | None = None
    model: str | None = None
    criterios: list[CriterionEvaluation] = Field(min_length=1)


class PreviousEvaluation(StrictModel):
    criterio_id: int = Field(gt=0)
    criterio: str | None = None
    nota: int
    justificativa: str | None = None


class SelectionRequest(StrictModel):
    inscricao_id: int = Field(gt=0)
    avaliador_id: int = Field(gt=0)
    correlation_id: UUID
    prompt_version: str = Field(min_length=1, max_length=80)
    evaluation_configuration_hash: str = Field(pattern=r"^[a-f0-9]{64}$")
    inscricao: Proposal
    avaliacoes: list[PreviousEvaluation] = Field(min_length=1)
    runtime: RuntimeConfiguration | None = None

    @model_validator(mode="after")
    def matching_prompt_version(self) -> "SelectionRequest":
        if self.runtime is not None and self.prompt_version != self.runtime.prompt_version:
            raise ValueError("versão do prompt não corresponde à configuração")
        return self


class SelectionDecision(StrEnum):
    INDICADA = "INDICADA"
    NAO_INDICADA = "NAO_INDICADA"


class SelectionResult(StrictModel):
    inscricao_id: int = Field(gt=0)
    prompt_version: str
    provider: str | None = None
    model: str | None = None
    indicacao: SelectionDecision
    justificativa: str = Field(min_length=10, max_length=2000)

    @field_validator("justificativa")
    @classmethod
    def valid_justification_length(cls, value: str) -> str:
        if not 50 <= word_count(value) <= 150:
            raise ValueError("justificativa deve conter entre 50 e 150 palavras")
        return value
