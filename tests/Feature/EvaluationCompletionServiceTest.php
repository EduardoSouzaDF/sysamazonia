<?php

namespace Tests\Feature;

use App\Services\EvaluationCompletionService;
use Tests\TestCase;

class EvaluationCompletionServiceTest extends TestCase
{
    public function test_completion_service_exposes_separate_quorum_operations(): void
    {
        $this->assertTrue(method_exists(EvaluationCompletionService::class, 'recalculate'));
        $this->assertTrue(method_exists(EvaluationCompletionService::class, 'hasRequiredQuorum'));
        $this->assertTrue(method_exists(EvaluationCompletionService::class, 'completeIfReady'));
    }
}
