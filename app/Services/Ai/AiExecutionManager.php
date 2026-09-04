<?php

namespace App\Services\Ai;

use App\Data\Ai\EvaluationRequestData;
use App\Data\Ai\EvaluationResultData;
use App\Data\Ai\SelectionResultData;
use App\Enum\AiExecutionStatus;
use App\Exceptions\Ai\NonRetryableAiException;
use App\Models\AiExecution;
use App\Models\Indication;
use App\Models\Registration;
use App\Models\User;
use App\Services\OpinionSubmissionService;
use Illuminate\Support\Facades\DB;

class AiExecutionManager
{
    public function __construct(
        private readonly OpinionSubmissionService $opinionSubmissionService,
        private readonly EvaluationConfigurationFingerprint $fingerprint,
    ) {}

    public function begin(AiExecution $execution): void
    {
        $execution->update([
            'status' => AiExecutionStatus::Processing,
            'attempts' => $execution->attempts + 1,
            'started_at' => now(),
            'completed_at' => null,
            'duration_ms' => null,
            'error_code' => null,
            'error_message' => null,
        ]);
    }

    public function completeEvaluation(AiExecution $execution, EvaluationRequestData $request, EvaluationResultData $result, int $duration): bool
    {
        return DB::transaction(function () use ($execution, $request, $result, $duration): bool {
            $locked = AiExecution::query()->lockForUpdate()->findOrFail($execution->id);
            if ($locked->status === AiExecutionStatus::Completed) {
                return false;
            }

            $registration = Registration::query()->lockForUpdate()->findOrFail($result->registrationId);
            if ((int) $registration->status !== \App\Enum\RegistrationStatusEnum::Habilitado->value) {
                $locked->update([
                    'status' => AiExecutionStatus::Failed,
                    'error_code' => 'AI_REGISTRATION_STATUS_CHANGED',
                    'error_message' => 'A inscrição mudou de status durante a avaliação.',
                    'duration_ms' => $duration,
                    'completed_at' => now(),
                ]);

                return false;
            }

            if ($this->fingerprint->forRegistration($registration) !== $request->configurationHash) {
                $locked->update([
                    'status' => AiExecutionStatus::Failed,
                    'error_code' => 'AI_CONFIGURATION_CHANGED',
                    'error_message' => 'Os critérios mudaram durante a avaliação.',
                    'duration_ms' => $duration,
                    'completed_at' => now(),
                ]);

                return false;
            }

            $scores = collect($result->criteria)->map(fn (array $criterion): array => [
                'criterion_id' => $criterion['criterio_id'],
                'score' => $criterion['nota'],
                'justification' => $criterion['justificativa'],
            ])->all();
            $opinion = $this->opinionSubmissionService->submit(
                $registration,
                User::query()->findOrFail($result->evaluatorId),
                $scores,
            );

            $locked->update([
                'opinion_id' => $opinion->id,
                'status' => AiExecutionStatus::Completed,
                'provider' => $result->provider,
                'model' => $result->model,
                'duration_ms' => $duration,
                'rubric_version' => collect($request->criteria)->pluck('rubric_version')->filter()->unique()->implode(','),
                'response_metadata' => ['criteria_count' => count($result->criteria)],
                'completed_at' => now(),
            ]);

            return true;
        });
    }

    public function completeSelection(AiExecution $execution, SelectionResultData $result, int $duration): void
    {
        DB::transaction(function () use ($execution, $result, $duration): void {
            $locked = AiExecution::query()->lockForUpdate()->findOrFail($execution->id);
            if ($locked->status === AiExecutionStatus::Completed) {
                return;
            }

            $registration = Registration::query()->lockForUpdate()->findOrFail($locked->registration_id);
            $isAuthorized = $registration->category()
                ->whereHas('indicators', fn ($query) => $query->whereKey($locked->evaluator_id))
                ->exists();
            if ($result->registrationId !== $registration->id
                || (int) $registration->status !== \App\Enum\RegistrationStatusEnum::Avaliado->value
                || ! $isAuthorized) {
                $locked->update([
                    'status' => AiExecutionStatus::Failed,
                    'error_code' => 'AI_SELECTION_CONTEXT_CHANGED',
                    'error_message' => 'A inscrição ou a autorização mudou durante a seleção.',
                    'duration_ms' => $duration,
                    'completed_at' => now(),
                ]);

                return;
            }

            $indication = Indication::query()->create([
                'user_id' => $locked->evaluator_id,
                'registration_id' => $registration->id,
                'decision' => $result->decision,
                'descricao' => $result->justification,
            ]);
            $locked->update([
                'indication_id' => $indication->id,
                'status' => AiExecutionStatus::Completed,
                'provider' => $result->provider,
                'model' => $result->model,
                'duration_ms' => $duration,
                'response_metadata' => ['decision' => $result->decision->value],
                'completed_at' => now(),
            ]);
        });
    }

    public function fail(AiExecution $execution, \Throwable $exception): void
    {
        DB::transaction(function () use ($execution, $exception): void {
            $locked = AiExecution::query()->lockForUpdate()->find($execution->id);
            if ($locked === null || $locked->status === AiExecutionStatus::Completed) {
                return;
            }

            $errorCode = $exception instanceof NonRetryableAiException
                ? $exception->errorCode
                : 'AI_SERVICE_UNAVAILABLE';
            $safeMessage = $exception instanceof NonRetryableAiException
                ? $exception->getMessage()
                : 'O serviço de IA não pôde concluir a operação.';
            $locked->update([
                'status' => AiExecutionStatus::Failed,
                'error_code' => $errorCode,
                'error_message' => $safeMessage,
                'completed_at' => now(),
            ]);
        });
    }
}
