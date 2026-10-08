<?php

namespace App\Services\Ai;

use App\Data\Ai\EvaluationRequestData;
use App\Enum\AiExecutionStatus;
use App\Enum\RegistrationStatusEnum;
use App\Exceptions\Ai\NonRetryableAiException;
use App\Exceptions\Ai\RetryableAiException;
use App\Models\AiExecution;
use App\Services\Ai\Contracts\AiEvaluationServiceInterface;
use Illuminate\Support\Facades\Cache;

class TechnicalEvaluationProcessor
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
            if (! $settings->evaluationEnabled) {
                return false;
            }
            $execution = AiExecution::query()->with('registration.category.evaluationCriteria')->findOrFail($executionId);
            if ($execution->superseded_at || $execution->attempts >= $settings->tries || $execution->status === AiExecutionStatus::Completed || (int) $execution->registration->status !== RegistrationStatusEnum::Habilitado->value) {
                return false;
            }
            $this->manager->begin($execution);
            $request = EvaluationRequestData::fromRegistration($execution->registration, $execution->evaluator_id, $execution->correlation_id, $execution->prompt_version, $execution->evaluation_configuration_hash);
            $started = hrtime(true);
            try {
                $result = $this->service->evaluate($request);
            } catch (NonRetryableAiException $e) {
                $this->manager->fail($execution, $e);

                return false;
            } catch (RetryableAiException $e) {
                $this->manager->fail($execution, $e);

                throw $e;
            }

            return $this->manager->completeEvaluation($execution, $request, $result, (int) ((hrtime(true) - $started) / 1_000_000));
        } finally {
            $lock->release();
        }
    }
}
