<?php

namespace App\Services\Analytics;

use App\Services\Ai\AiSettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AnalyticsPlanner
{
    public function interpret(string $question, ?array $context): array
    {
        if (preg_match('/\b(select|sql|users|cpf|rg|email|e-mail|telefone|senha|token)\b|ignore.*regras/iu', $question)) {
            return ['intent' => 'refusal', 'question' => 'Somente análises agregadas do catálogo são permitidas.'];
        }
        // Do not forward obvious personal identifiers accidentally pasted into a question.
        $question = preg_replace(['/\b[\w.+-]+@[\w.-]+\.[a-z]{2,}\b/iu', '/\b(?:\d[ .()\/-]*){8,}\b/u'], '[DADO REMOVIDO]', $question);
        if ($context !== null) {
            $context['filters'] = (object) $context['filters'];
        }
        $settings = app(AiSettingsService::class)->current();
        $token = config('ai_evaluation.token');
        if (! $token) {
            throw new \RuntimeException('AI_CONFIGURATION_ERROR');
        }
        $catalog = AnalyticsRegistry::catalog();
        $catalog['editions'] = \Illuminate\Support\Facades\DB::table('editions')->select('id', 'title', 'registration_start', 'registration_end', 'is_registration_active')->orderByDesc('registration_start')->limit(50)->get()->all();
        $response = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(30)
            ->post(rtrim(config('ai_evaluation.service_url'), '/').'/v1/analytics/plan', [
                'question' => $question, 'context' => $context, 'catalog' => $catalog,
                'runtime' => ['provider' => $settings->provider ?: null, 'model' => $settings->model ?: null,
                    'api_key' => $settings->apiKey, 'base_url' => $settings->baseUrl, 'local_type' => $settings->localType, 'timeout' => 25, 'prompt_version' => 'analytics_v1'],
            ]);
        if (! $response->successful()) {
            throw new \RuntimeException(($response->status() === 429 || $response->json('code') === 'PROVIDER_RATE_LIMIT') ? 'AI_RATE_LIMIT' : 'AI_UNAVAILABLE');
        }
        $plan = $response->json();
        if (! is_array($plan)) {
            throw ValidationException::withMessages(['plan' => 'Resposta inválida do interpretador.']);
        }
        if (in_array($plan['intent'] ?? null, ['clarification', 'refusal'])) {
            return ['intent' => $plan['intent'], 'question' => $plan['intent'] === 'refusal' ? 'Somente análises agregadas do catálogo são permitidas.' : 'Especifique a métrica (quantidade, nota média ou indicação), a edição e os filtros desejados.'];
        }
        unset($plan['question']);

        return AnalyticsPlan::validate($plan);
    }
}
