from typing import Literal
from pydantic import BaseModel, ConfigDict, Field, model_validator
from app.schemas.evaluation import RuntimeConfiguration

Metric = Literal['missing_fields_count','average_criterion_score','registrations_count','enabled_registrations_count','evaluated_registrations_count','indicated_count','not_indicated_count','indication_rate','evaluation_rate','average_score','invalid_birth_date_count','missing_education_count','missing_state_count','missing_city_count','missing_category_count','ai_execution_count','ai_failure_count','ai_success_rate','ai_average_duration_ms']
Dimension = Literal['edition','category','state','city','education','age_group','status','indication_status','day','month','ai_status','criterion','evaluator','region','week','days_to_deadline','quality_field']

class StrictModel(BaseModel):
    model_config = ConfigDict(extra='forbid')

class Filters(StrictModel):
    edition_ids: list[int] | None = Field(default=None, min_length=1, max_length=5)
    last_days: int | None = Field(default=None, ge=1, le=366)
    edition_id: int | None = Field(default=None, ge=1)
    edition_year: int | None = Field(default=None, ge=1900, le=2100)
    category_id: int | None = Field(default=None, ge=1)
    state: list[Literal['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO']] | None = Field(default=None, min_length=1, max_length=27)
    status: int | None = Field(default=None, ge=0, le=5)
    date_from: str | None = Field(default=None, pattern=r'^\d{4}-\d{2}-\d{2}$')
    date_to: str | None = Field(default=None, pattern=r'^\d{4}-\d{2}-\d{2}$')

class Sort(StrictModel):
    field: str
    direction: Literal['asc','desc']

class Visualization(StrictModel):
    type: Literal['kpi','table','bar','horizontal_bar','line','area','donut','stacked_bar','map_brazil'] = 'table'

class AnalyticsPlan(StrictModel):
    intent: Literal['analytics','clarification','refusal']
    question: str | None = Field(default=None, max_length=400)
    metric: Metric | None = None
    dimensions: list[Dimension] = Field(default_factory=list, max_length=2)
    filters: Filters = Field(default_factory=Filters)
    sort: list[Sort] = Field(default_factory=list, max_length=2)
    limit: int = Field(default=100, ge=1, le=100)
    visualization: Visualization = Field(default_factory=Visualization)
    cumulative: bool = False

    @model_validator(mode='after')
    def valid_plan(self):
        if self.intent != 'analytics':
            if not self.question:
                raise ValueError('A question is required')
            return self
        if self.metric is None or len(set(self.dimensions)) != len(self.dimensions):
            raise ValueError('Invalid metric/dimensions')
        if any(s.field not in [*self.dimensions, 'value'] for s in self.sort):
            raise ValueError('Unknown sort')
        if 'ai_status' in self.dimensions and not self.metric.startswith('ai_'):
            raise ValueError('Incompatible dimension')
        if (self.metric == 'missing_fields_count') != (self.dimensions == ['quality_field']):
            raise ValueError('Incompatible quality dimension')
        if 'quality_field' in self.dimensions and self.metric != 'missing_fields_count':
            raise ValueError('Incompatible quality dimension')
        if any(d in self.dimensions for d in ['criterion','evaluator']) and self.metric != 'average_criterion_score':
            raise ValueError('Incompatible score dimension')
        from datetime import date
        if bool(self.filters.date_from) != bool(self.filters.date_to):
            raise ValueError('Both dates required')
        if self.filters.date_from:
            duration = (date.fromisoformat(self.filters.date_to) - date.fromisoformat(self.filters.date_from)).days
            if not 0 <= duration <= 3660:
                raise ValueError('Invalid period')
        if self.cumulative and (self.metric != 'registrations_count' or len(set(self.dimensions) & {'day','month','week','days_to_deadline'}) != 1):
            raise ValueError('Invalid cumulative')
        return self

class AnalyticsRequest(StrictModel):
    question: str = Field(min_length=1, max_length=1000)
    context: AnalyticsPlan | None = None
    catalog: dict
    runtime: RuntimeConfiguration | None = None
