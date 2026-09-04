<?php

namespace App\Listeners;

use App\Enum\AiExecutionStatus;
use App\Enum\AiExecutionType;
use App\Enum\RegistrationStatusEnum;
use App\Events\RegistrationStatusChanged;
use App\Jobs\EvaluateRegistrationWithAi;
use App\Jobs\SelectRegistrationWithAi;
use App\Models\AiExecution;
use App\Models\Registration;
use App\Models\User;
use App\Services\Ai\EvaluationConfigurationFingerprint;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchAiEvaluation
{
    public function __construct(private readonly EvaluationConfigurationFingerprint $fingerprint) {}

    public function handle(RegistrationStatusChanged $event): void
    {
        if (! config('ai_evaluation.enabled')) {
            return;
        }

        $configuration = match ($event->currentStatus) {
            RegistrationStatusEnum::Habilitado->value => [
                AiExecutionType::TechnicalEvaluation,
                (int) config('ai_evaluation.technical_evaluator_id'),
                (string) config('ai_evaluation.prompts.technical'),
                EvaluateRegistrationWithAi::class,
            ],
            RegistrationStatusEnum::Avaliado->value => [
                AiExecutionType::StrategicSelection,
                (int) config('ai_evaluation.selection_evaluator_id'),
                (string) config('ai_evaluation.prompts.selection'),
                SelectRegistrationWithAi::class,
            ],
            default => null,
        };

        if ($configuration === null || $configuration[1] <= 0) {
            return;
        }

        if ($event->currentStatus === RegistrationStatusEnum::Avaliado->value
            && ! config('ai_evaluation.selection_enabled')) {
            return;
        }

        [$type, $evaluatorId, $promptVersion, $jobClass] = $configuration;
        if (! User::query()->whereKey($evaluatorId)->exists()) {
            Log::warning('Avaliador de IA configurado não existe.', [
                'registration_id' => $event->registrationId,
                'evaluator_id' => $evaluatorId,
                'type' => $type->value,
            ]);

            return;
        }

        $registration = Registration::query()
            ->with(['category.evaluationCriteria', 'category.evaluators', 'category.indicators'])
            ->find($event->registrationId);
        if ($registration === null) {
            return;
        }

        $isAuthorized = $type === AiExecutionType::TechnicalEvaluation
            ? $registration->category->evaluators->contains('id', $evaluatorId)
            : $registration->category->indicators->contains('id', $evaluatorId);
        if (! $isAuthorized) {
            Log::warning('Avaliador de IA não está vinculado à categoria.', [
                'registration_id' => $event->registrationId,
                'evaluator_id' => $evaluatorId,
                'category_id' => $registration->category_id,
                'type' => $type->value,
            ]);

            return;
        }

        $configurationHash = $type === AiExecutionType::TechnicalEvaluation
            ? $this->fingerprint->forRegistration($registration)
            : $this->fingerprint->forSelection($registration);
        $identity = [
            'registration_id' => $event->registrationId,
            'type' => $type,
            'evaluator_id' => $evaluatorId,
            'prompt_version' => $promptVersion,
            'evaluation_configuration_hash' => $configurationHash,
        ];

        DB::transaction(function () use ($identity, $jobClass): void {
            try {
                $execution = AiExecution::query()->create($identity + [
                    'correlation_id' => (string) str()->uuid(),
                    'status' => AiExecutionStatus::Pending,
                    'knowledge_version' => config('ai_evaluation.knowledge_version'),
                ]);
            } catch (UniqueConstraintViolationException) {
                $execution = AiExecution::query()->where($identity)->firstOrFail();
            }

            if (in_array($execution->status, [AiExecutionStatus::Pending, AiExecutionStatus::Failed], true)) {
                $jobClass::dispatch($execution->id)
                    ->onQueue((string) config('ai_evaluation.queue'))
                    ->afterCommit();
            }
        }, 3);
    }
}
