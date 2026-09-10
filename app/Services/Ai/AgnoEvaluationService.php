<?php

namespace App\Services\Ai;

use App\Data\Ai\EvaluationRequestData;
use App\Data\Ai\EvaluationResultData;
use App\Data\Ai\SelectionRequestData;
use App\Data\Ai\SelectionResultData;
use App\Exceptions\Ai\NonRetryableAiException;
use App\Models\AiExecution;
use App\Services\Ai\Contracts\AiEvaluationServiceInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AgnoEvaluationService implements AiEvaluationServiceInterface
{
    public function __construct(private AiSettingsService $settings) {}

    public function evaluate(EvaluationRequestData $request): EvaluationResultData
    {
        $payload = $this->request('/v1/evaluations/technical', $request->toArray());

        try {
            return EvaluationResultData::fromArray($payload, $request);
        } catch (ValidationException $exception) {
            throw new NonRetryableAiException('AI_INVALID_RESPONSE', 'O serviço de IA retornou uma avaliação inválida.');
        }
    }

    public function select(SelectionRequestData $request): SelectionResultData
    {
        $payload = $this->request('/v1/evaluations/selection', $request->toArray());

        try {
            return SelectionResultData::fromArray($payload, $request);
        } catch (ValidationException $exception) {
            throw new NonRetryableAiException('AI_INVALID_RESPONSE', 'O serviço de IA retornou uma seleção inválida.');
        }
    }

    private function request(string $path, array $payload): array
    {
        $settings = $this->settings->forCorrelation((string) ($payload['correlation_id'] ?? ''));
        $payload['runtime'] = [
            'provider' => $settings->provider ?: null,
            'model' => $settings->model ?: null,
            'base_url' => $settings->baseUrl, 'local_type' => $settings->localType,
            'api_key' => $settings->apiKey,
            'prompt' => str_contains($path, '/technical') ? $settings->technicalPrompt : $settings->selectionPrompt,
            'prompt_version' => str_contains($path, '/technical') ? $settings->technicalPromptVersion : $settings->selectionPromptVersion,
            'knowledge_version' => $settings->knowledgeVersion,
            'timeout' => min(55, $settings->timeout),
        ];
        $response = $this->client()->post($path, $payload);
        AiExecution::query()
            ->where('correlation_id', (string) ($payload['correlation_id'] ?? ''))
            ->update(['service_http_status' => $response->status()]);
        $providerCode = $response->json('code');
        if (is_string($providerCode) && in_array($providerCode, ['PROVIDER_UNAUTHORIZED', 'PROVIDER_FORBIDDEN', 'MODEL_NOT_FOUND', 'PROVIDER_INVALID_RESPONSE'], true)) {
            throw new NonRetryableAiException($providerCode, AiServiceDiagnostics::MESSAGES[$providerCode]);
        }
        if ($response->serverError() && $response->json('code') === 'LLM_CONFIGURATION_ERROR') {
            throw new NonRetryableAiException(
                'LLM_CONFIGURATION_ERROR',
                'O provedor de IA está com configuração incompleta ou inválida.',
            );
        }

        if (in_array($response->status(), [400, 401, 403, 422], true)) {
            $code = $response->status() === 401 ? 'AI_UNAUTHORIZED' : 'AI_REQUEST_REJECTED';
            throw new NonRetryableAiException($code, 'O serviço de IA rejeitou a requisição.');
        }

        $response->throw();
        $decoded = $response->json();
        if (! is_array($decoded)) {
            throw new NonRetryableAiException('AI_INVALID_RESPONSE', 'O serviço de IA retornou JSON inválido.');
        }

        return $decoded;
    }

    private function client(): PendingRequest
    {
        $url = config('ai_evaluation.service_url');
        $token = config('ai_evaluation.token');
        if (! is_string($url) || $url === '' || ! is_string($token) || $token === '') {
            throw new NonRetryableAiException('AI_CONFIGURATION_ERROR', 'O serviço de avaliação por IA não está configurado.');
        }

        $host = parse_url($url, PHP_URL_HOST);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $isLoopback = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        if (app()->environment('production') && (! $isLoopback && $scheme !== 'https')) {
            throw new NonRetryableAiException('AI_INSECURE_TRANSPORT', 'HTTPS é obrigatório para um serviço de IA remoto.');
        }

        return Http::baseUrl($url)
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->connectTimeout($this->settings->current()->connectTimeout)
            ->timeout($this->settings->current()->timeout);
    }
}
