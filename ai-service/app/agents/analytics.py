import re
from app.agents.evaluators import AgnoEvaluator
from app.prompts.analytics import ANALYTICS_PROMPT
from app.schemas.analytics import AnalyticsPlan, AnalyticsRequest
from app.provider_diagnostics import ProviderError


def interpret(request: AnalyticsRequest, service: AgnoEvaluator) -> AnalyticsPlan:
    if re.search(r'\b(select|sql|users|cpf|rg|email|e-mail|telefone|senha|token)\b|ignore.*regras', request.question, re.I):
        return AnalyticsPlan(intent='refusal', question='Somente análises agregadas do catálogo são permitidas.')
    model = service._provider_factory.create('technical', request.runtime)
    result = service._run(model, ANALYTICS_PROMPT, request, AnalyticsPlan)
    if result.intent == 'analytics':
        if result.metric not in request.catalog.get('metrics', {}) or any(d not in request.catalog.get('dimensions', []) for d in result.dimensions):
            raise ProviderError('PROVIDER_INVALID_RESPONSE')
    return result
