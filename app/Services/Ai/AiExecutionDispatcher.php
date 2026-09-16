<?php

namespace App\Services\Ai;

use App\Enum\AiExecutionStatus;
use App\Enum\AiExecutionType;
use App\Jobs\EvaluateRegistrationWithAi;
use App\Jobs\SelectRegistrationWithAi;
use App\Models\AiExecution;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class AiExecutionDispatcher
{
    public function __construct(private AiSettingsService $settings, private EvaluationConfigurationFingerprint $fingerprint) {}

    public function create(Registration $registration, AiExecutionType $type): ?AiExecution
    {
        $settings = $this->settings->current();
        $evaluatorId = $type === AiExecutionType::TechnicalEvaluation ? $settings->technicalEvaluatorId : $settings->selectionEvaluatorId;
        $enabled = $type === AiExecutionType::TechnicalEvaluation ? $settings->evaluationEnabled : ($settings->evaluationEnabled && $settings->selectionEnabled);
        if (! $enabled || $evaluatorId <= 0 || ! User::query()->whereKey($evaluatorId)->exists()) {
            return null;
        }
        $registration->loadMissing(['category.evaluators', 'category.indicators', 'category.evaluationCriteria']);
        $authorized = $type === AiExecutionType::TechnicalEvaluation
            ? $registration->category->evaluators->contains('id', $evaluatorId)
            : $registration->category->indicators->contains('id', $evaluatorId);
        if (! $authorized) {
            return null;
        }
        $promptVersion = $type === AiExecutionType::TechnicalEvaluation ? $settings->technicalPromptVersion : $settings->selectionPromptVersion;
        $hash = $type === AiExecutionType::TechnicalEvaluation ? $this->fingerprint->forRegistration($registration) : $this->fingerprint->forSelection($registration);
        $identity = ['registration_id' => $registration->id, 'type' => $type, 'evaluator_id' => $evaluatorId, 'prompt_version' => $promptVersion, 'evaluation_configuration_hash' => $hash];

        return DB::transaction(function () use ($identity, $settings): AiExecution {
            try {
                return AiExecution::query()->create($identity + ['ai_setting_version_id' => $settings->versionId, 'correlation_id' => (string) str()->uuid(), 'status' => AiExecutionStatus::Pending, 'knowledge_version' => $settings->knowledgeVersion]);
            } catch (UniqueConstraintViolationException) {
                return AiExecution::query()->where($identity)->firstOrFail();
            }
        }, 3);
    }

    public function dispatch(AiExecution $execution): void
    {
        if (! in_array($execution->status, [AiExecutionStatus::Pending, AiExecutionStatus::Failed], true)) {
            return;
        }
        $job = $execution->type === AiExecutionType::TechnicalEvaluation ? new EvaluateRegistrationWithAi($execution->id) : new SelectRegistrationWithAi($execution->id);
        dispatch($job->onQueue((string) config('ai_evaluation.queue')));
    }
}
