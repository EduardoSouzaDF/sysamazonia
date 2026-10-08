<?php

namespace App\Services\Ai;

use App\Data\Ai\SelectionRequestData;
use App\Enum\AiExecutionStatus;
use App\Enum\RegistrationStatusEnum;
use App\Exceptions\Ai\NonRetryableAiException;
use App\Exceptions\Ai\RetryableAiException;
use App\Models\AiExecution;
use App\Services\Ai\Contracts\AiEvaluationServiceInterface;
use Illuminate\Support\Facades\Cache;

class StrategicSelectionProcessor
{
    public function __construct(private AiEvaluationServiceInterface $service, private AiExecutionManager $manager, private AiSettingsService $settings) {}

    public function process(int $executionId): bool
    {
        $identity = AiExecution::query()->findOrFail($executionId);
        // All rounds share a lock, so an old job cannot overlap its manual replacement.
        $lock = Cache::lock('ai-registration-process-'.$identity->registration_id.'-'.$identity->type->value, $this->settings->current()->timeout + 30);
        if (! $lock->get()) {
            return false;
        }
        try {
            $settings = $this->settings->current();
            if (! $settings->selectionEnabled) {
                return false;
            }
            $execution = AiExecution::query()->with('registration.opinions.scores.evaluationCriterion')->findOrFail($executionId);
            if ($execution->superseded_at || $execution->attempts >= $settings->tries || $execution->status === AiExecutionStatus::Completed || (int) $execution->registration->status !== RegistrationStatusEnum::Avaliado->value) {
                return false;
            }
            $this->manager->begin($execution);
            $request = SelectionRequestData::fromRegistration($execution->registration, $execution->evaluator_id, $execution->correlation_id, $execution->prompt_version, $execution->evaluation_configuration_hash);
            $started = hrtime(true);
            try {
                $result = $this->service->select($request);
            } catch (NonRetryableAiException $e) {
                $this->manager->fail($execution, $e);

                return false;
            } catch (RetryableAiException $e) {
                $this->manager->fail($execution, $e);

                throw $e;
            }
            $this->manager->completeSelection($execution, $request, $result, (int) ((hrtime(true) - $started) / 1_000_000));

            return $execution->refresh()->status === AiExecutionStatus::Completed;
        } finally {
            $lock->release();
        }
    }
}
