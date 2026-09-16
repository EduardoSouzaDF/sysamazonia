<?php

namespace App\Services\Ai;

use App\Data\Ai\AiSettingsData;
use App\Models\AiExecution;
use App\Models\AiSetting;
use App\Models\AiSettingVersion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AiSettingsService
{
    private const CACHE_KEY = 'ai.settings.resolved.v2';

    public function current(): AiSettingsData
    {
        // Remove the legacy cache containing a decrypted credential on upgrade.
        Cache::forget('ai.settings.resolved');
        $setting = AiSetting::query()->latest('id')->first();
        if ($setting === null) {
            return $this->fallback();
        }
        $resolved = $this->fromModel($setting);

        return $resolved;
    }

    public function update(array $data, User $user): AiSetting
    {
        return DB::transaction(function () use ($data, $user): AiSetting {
            $setting = AiSetting::query()->lockForUpdate()->first();
            $technicalChanged = array_key_exists('technical_prompt', $data) && filled($data['technical_prompt'])
                && ($setting === null || $data['technical_prompt'] !== $setting->technical_prompt);
            $selectionChanged = array_key_exists('selection_prompt', $data) && filled($data['selection_prompt'])
                && ($setting === null || $data['selection_prompt'] !== $setting->selection_prompt);
            if ($setting === null) {
                $f = $this->fallback();
                $setting = new AiSetting(['provider' => $f->provider, 'model' => $f->model, 'technical_prompt_version' => $f->technicalPromptVersion, 'selection_prompt_version' => $f->selectionPromptVersion, 'evaluation_enabled' => $f->evaluationEnabled, 'selection_enabled' => $f->selectionEnabled, 'technical_evaluator_id' => $f->technicalEvaluatorId ?: null, 'selection_evaluator_id' => $f->selectionEvaluatorId ?: null, 'connect_timeout' => $f->connectTimeout, 'timeout' => $f->timeout, 'tries' => $f->tries, 'knowledge_version' => $f->knowledgeVersion]);
            }
            $endpointChanged = $setting->exists && (
                ($data['provider'] ?? $setting->provider) !== $setting->provider
                || (array_key_exists('base_url', $data) && $data['base_url'] !== $setting->base_url)
            );
            if ($endpointChanged && empty($data['api_key'])) {
                $setting->api_key = null;
            }
            if (empty($data['api_key'])) {
                unset($data['api_key']);
            }
            if ($technicalChanged) {
                $data['technical_prompt_version'] = $this->nextVersion($setting->technical_prompt_version, 'technical_evaluator');
            }
            if ($selectionChanged) {
                $data['selection_prompt_version'] = $this->nextVersion($setting->selection_prompt_version, 'selection_reviewer');
            }
            $setting->fill($data);
            $setting->updated_by = $user->id;
            $setting->save();
            $version = $setting->versions()->create(['provider' => $setting->provider, 'model' => $setting->model, 'base_url' => $setting->base_url, 'local_type' => $setting->local_type, 'technical_prompt' => $setting->technical_prompt, 'selection_prompt' => $setting->selection_prompt, 'technical_prompt_version' => $setting->technical_prompt_version, 'selection_prompt_version' => $setting->selection_prompt_version, 'knowledge_version' => $setting->knowledge_version, 'changed_by' => $user->id]);
            Cache::forget(self::CACHE_KEY);

            return $setting;
        }, 3);
    }

    public function maskedKey(?AiSetting $setting = null): ?string
    {
        $key = ($setting ?? AiSetting::query()->first())?->api_key;

        return $key ? 'Configurada ••••'.(strlen($key) > 4 ? strtoupper(substr($key, -4)) : '') : null;
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function forCorrelation(string $correlationId): AiSettingsData
    {
        $execution = AiExecution::query()->where('correlation_id', $correlationId)->first();
        $current = $this->current();
        if (! $execution?->ai_setting_version_id) {
            return $current;
        }
        $version = AiSettingVersion::query()->find($execution->ai_setting_version_id);
        if (! $version) {
            return $current;
        }

        return new AiSettingsData($current->id, $version->id, $version->provider, $version->model,
            ($version->provider === $current->provider && $version->base_url === $current->baseUrl) ? $current->apiKey : null, $version->technical_prompt, $version->selection_prompt,
            $version->technical_prompt_version, $version->selection_prompt_version,
            $current->evaluationEnabled, $current->selectionEnabled, $current->technicalEvaluatorId,
            $current->selectionEvaluatorId, $current->connectTimeout, $current->timeout,
            $current->tries, $version->knowledge_version, $version->base_url, $version->local_type);
    }

    private function fromModel(AiSetting $s, ?AiSettingVersion $v = null): AiSettingsData
    {
        $v ??= $s->versions()->latest('id')->first();

        return new AiSettingsData($s->id, $v?->id, $s->provider, $s->model, $s->api_key, $s->technical_prompt, $s->selection_prompt, $s->technical_prompt_version, $s->selection_prompt_version, $s->evaluation_enabled, $s->selection_enabled, (int) $s->technical_evaluator_id, (int) $s->selection_evaluator_id, $s->connect_timeout, $s->timeout, $s->tries, $s->knowledge_version, $s->base_url, $s->local_type);
    }

    private function fallback(): AiSettingsData
    {
        return new AiSettingsData(null, null, (string) config('ai_evaluation.provider', 'gemini'), (string) config('ai_evaluation.model', ''), null, null, null, (string) config('ai_evaluation.prompts.technical'), (string) config('ai_evaluation.prompts.selection'), (bool) config('ai_evaluation.enabled'), (bool) config('ai_evaluation.selection_enabled'), (int) config('ai_evaluation.technical_evaluator_id'), (int) config('ai_evaluation.selection_evaluator_id'), (int) config('ai_evaluation.connect_timeout'), (int) config('ai_evaluation.timeout'), (int) config('ai_evaluation.tries'), config('ai_evaluation.knowledge_version'));
    }

    private function nextVersion(string $current, string $prefix): string
    {
        preg_match('/_v(\d+)$/', $current, $matches);

        return $prefix.'_v'.(((int) ($matches[1] ?? 0)) + 1);
    }
}
