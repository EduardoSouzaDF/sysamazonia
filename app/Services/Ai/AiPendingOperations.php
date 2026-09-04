<?php

namespace App\Services\Ai;

use App\Enum\AiExecutionType;
use App\Enum\RegistrationStatusEnum;
use App\Models\AiExecution;
use App\Models\Registration;
use Illuminate\Support\Collection;

class AiPendingOperations
{
    public function __construct(private AiExecutionDispatcher $dispatcher, private TechnicalEvaluationProcessor $technical, private StrategicSelectionProcessor $strategic, private AiSettingsService $settings, private EvaluationConfigurationFingerprint $fingerprint) {}

    public function candidates(AiExecutionType $type): Collection
    {
        $status = $type === AiExecutionType::TechnicalEvaluation ? RegistrationStatusEnum::Habilitado : RegistrationStatusEnum::Avaliado;
        $settings = $this->settings->current();
        $enabled = $type === AiExecutionType::TechnicalEvaluation
            ? $settings->evaluationEnabled
            : ($settings->evaluationEnabled && $settings->selectionEnabled);
        if (! $enabled) {
            return collect();
        }
        $evaluatorId = $type === AiExecutionType::TechnicalEvaluation ? $settings->technicalEvaluatorId : $settings->selectionEvaluatorId;
        $promptVersion = $type === AiExecutionType::TechnicalEvaluation ? $settings->technicalPromptVersion : $settings->selectionPromptVersion;
        $relations = $type === AiExecutionType::TechnicalEvaluation ? ['category.evaluationCriteria', 'category.evaluators'] : ['category.indicators', 'opinions.scores.evaluationCriterion'];

        return Registration::query()->where('status', $status->value)->with($relations)->orderBy('id')->get()
            ->filter(function (Registration $registration) use ($type, $evaluatorId, $promptVersion): bool {
                $authorized = $type === AiExecutionType::TechnicalEvaluation
                    ? $registration->category->evaluators->contains('id', $evaluatorId)
                    : $registration->category->indicators->contains('id', $evaluatorId);
                if (! $authorized) {
                    return false;
                }
                $hash = $type === AiExecutionType::TechnicalEvaluation ? $this->fingerprint->forRegistration($registration) : $this->fingerprint->forSelection($registration);

                return ! AiExecution::query()->where(['registration_id' => $registration->id, 'evaluator_id' => $evaluatorId, 'type' => $type, 'prompt_version' => $promptVersion, 'evaluation_configuration_hash' => $hash, 'status' => \App\Enum\AiExecutionStatus::Completed])->exists();
            })->values();
    }

    public function start(AiExecutionType $type, bool $synchronous = false, ?int $limit = null): array
    {
        $started = 0;
        $completed = 0;
        $candidates = $this->candidates($type);
        if ($limit !== null) {
            $candidates = $candidates->take($limit);
        }
        foreach ($candidates as $registration) {
            $execution = $this->dispatcher->create($registration, $type);
            if (! $execution || $execution->status->value === 'completed') {
                continue;
            }
            $started++;
            if ($synchronous) {
                $completed += ($type === AiExecutionType::TechnicalEvaluation ? $this->technical->process($execution->id) : $this->strategic->process($execution->id)) ? 1 : 0;
            } else {
                $this->dispatcher->dispatch($execution);
            }
        }

        return compact('started', 'completed');
    }
}
