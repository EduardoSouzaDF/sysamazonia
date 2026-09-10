<?php

namespace App\Services\Ai;

use App\Data\Ai\AiSettingsData;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AiServiceDiagnostics
{
    public const MESSAGES = [
        'AI_OFFLINE' => 'FastAPI indisponível. Confira o processo, host e porta configurados.',
        'AI_TIMEOUT' => 'FastAPI não respondeu no tempo limite.',
        'AI_UNAUTHORIZED' => 'Falha de autenticação entre Laravel e serviço de IA (HTTP 401).',
        'AI_FORBIDDEN' => 'Acesso ao serviço de IA negado (HTTP 403).',
        'AI_CONFIGURATION_ERROR' => 'Confira AI_SERVICE_URL e AI_SERVICE_TOKEN no ambiente e no cache Laravel.',
        'LLM_CONFIGURATION_ERROR' => 'Provider não configurado: confira modelo, credencial e allowlist de Base URL.',
        'PROVIDER_UNAUTHORIZED' => 'API Key do provider rejeitada.',
        'PROVIDER_FORBIDDEN' => 'Provider negou acesso ao recurso.',
        'MODEL_NOT_FOUND' => 'Modelo selecionado não está disponível.',
        'PROVIDER_RATE_LIMIT' => 'Limite temporário do provider atingido.',
        'PROVIDER_TIMEOUT' => 'Provider não respondeu no tempo limite.',
        'LOCAL_UNAVAILABLE' => 'Servidor de IA local não respondeu.',
        'PROVIDER_UNAVAILABLE' => 'Provider indisponível.',
        'PROVIDER_INVALID_RESPONSE' => 'Provider retornou resposta inválida.',
        'AI_INVALID_RESPONSE' => 'Resposta inválida do serviço de IA.',
    ];

    public function check(AiSettingsData $settings, string $operation = 'diagnostics'): array
    {
        $url = config('ai_evaluation.service_url');
        $token = config('ai_evaluation.token');
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || ! in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            || ! is_string($token) || ! preg_match('/^[\x21-\x7E]{32,}$/D', $token)
            || (app()->environment('production') && parse_url($url, PHP_URL_SCHEME) !== 'https' && ! in_array(parse_url($url, PHP_URL_HOST), ['127.0.0.1', 'localhost', '[::1]'], true))) {
            return $this->failure('AI_CONFIGURATION_ERROR');
        }
        try {
            $client = Http::baseUrl($url)->withToken($token)->acceptJson()->withoutRedirecting()
                ->connectTimeout($settings->connectTimeout)->timeout(min($settings->timeout, 20));
            $response = $operation === 'diagnostics' ? $client->get('/v1/diagnostics') : $client->post('/v1/configuration/'.$operation, [
                'provider' => $settings->provider, 'model' => $settings->model ?: 'model-list', 'api_key' => $settings->apiKey,
                'base_url' => $settings->baseUrl, 'local_type' => $settings->localType,
                'prompt_version' => $settings->technicalPromptVersion, 'timeout' => min(55, $settings->timeout),
            ]);
            if ($response->status() === 401) {
                return $this->failure('AI_UNAUTHORIZED', true);
            }
            if ($response->status() === 403) {
                return $this->failure('AI_FORBIDDEN', true);
            }
            if (! $response->successful()) {
                $code = $response->json('code');

                return $this->failure(is_string($code) && isset(self::MESSAGES[$code]) ? $code : 'AI_INVALID_RESPONSE', true);
            }
            if (! is_array($response->json())) {
                return $this->failure('AI_INVALID_RESPONSE', true);
            }

            return ['ok' => true, 'online' => true, 'code' => 'OK', 'message' => 'Conexão e autenticação verificadas.', 'models' => array_values(array_filter((array) $response->json('models', []), fn ($model) => is_string($model) && preg_match('~^[A-Za-z0-9][A-Za-z0-9._:/-]{0,119}$~D', $model)))];
        } catch (ConnectionException $exception) {
            return $this->failure(str_contains($exception->getMessage(), 'cURL error 28') ? 'AI_TIMEOUT' : 'AI_OFFLINE');
        } catch (\Throwable) {
            return $this->failure('AI_INVALID_RESPONSE');
        }
    }

    private function failure(string $code, bool $online = false): array
    {
        return ['ok' => false, 'online' => $online, 'code' => $code, 'message' => self::MESSAGES[$code], 'models' => []];
    }
}
