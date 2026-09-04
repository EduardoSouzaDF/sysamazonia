<?php

namespace App\Jobs;

use App\Data\Ai\EvaluationRequestData;
use App\Enum\AiExecutionStatus;
use App\Enum\RegistrationStatusEnum;
use App\Exceptions\Ai\NonRetryableAiException;
use App\Models\AiExecution;
use App\Services\Ai\AiExecutionManager;
use App\Services\Ai\Contracts\AiEvaluationServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class EvaluateRegistrationWithAi implements ShouldQueue
{
    use Queueable;

    public int $timeout;

    public function __construct(public readonly int $executionId)
    {
        $this->timeout = (int) config('ai_evaluation.timeout') + 10;
    }

    public function handle(AiEvaluationServiceInterface $service, AiExecutionManager $manager): void
    {
        if (! config('ai_evaluation.enabled')) {
            return;
        }

        $execution = AiExecution::query()->with('registration.category.evaluationCriteria')->findOrFail($this->executionId);
        if ($execution->status === AiExecutionStatus::Completed
            || (int) $execution->registration->status !== RegistrationStatusEnum::Habilitado->value) {
            return;
        }

        $manager->begin($execution);
        $request = EvaluationRequestData::fromRegistration(
            $execution->registration,
            $execution->evaluator_id,
            $execution->correlation_id,
            $execution->prompt_version,
            $execution->evaluation_configuration_hash,
        );
        $startedAt = hrtime(true);
        try {
            $result = $service->evaluate($request);
        } catch (NonRetryableAiException $exception) {
            $manager->fail($execution, $exception);

            return;
        }
        $duration = (int) ((hrtime(true) - $startedAt) / 1_000_000);
        $persisted = $manager->completeEvaluation($execution, $request, $result, $duration);
        Log::info(
            $persisted ? 'Avaliação técnica por IA concluída.' : 'Resultado técnico da IA não foi persistido.',
            $this->logContext($execution, $duration),
        );
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('ai-execution-'.$this->executionId))->dontRelease()->expireAfter(120)];
    }

    public function tries(): int
    {
        return (int) config('ai_evaluation.tries');
    }

    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function failed(?\Throwable $exception): void
    {
        if ($exception !== null && ($execution = AiExecution::query()->find($this->executionId))) {
            app(AiExecutionManager::class)->fail($execution, $exception);
            Log::error('Avaliação técnica por IA falhou.', $this->logContext($execution));
        }
    }

    private function logContext(AiExecution $execution, ?int $duration = null): array
    {
        return array_filter([
            'registration_id' => $execution->registration_id,
            'execution_id' => $execution->id,
            'correlation_id' => $execution->correlation_id,
            'attempt' => $execution->attempts,
            'duration_ms' => $duration,
        ], fn ($value): bool => $value !== null);
    }
}
