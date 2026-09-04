<?php

namespace App\Jobs;

use App\Models\AiExecution;
use App\Services\Ai\AiExecutionManager;
use App\Services\Ai\AiSettingsService;
use App\Services\Ai\TechnicalEvaluationProcessor;
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
        $this->timeout = app(AiSettingsService::class)->current()->timeout + 10;
    }

    public function handle(TechnicalEvaluationProcessor $processor): void
    {
        $processor->process($this->executionId);
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('ai-execution-'.$this->executionId))->dontRelease()->expireAfter(120)];
    }

    public function tries(): int
    {
        return app(AiSettingsService::class)->current()->tries;
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
