<?php

namespace Tests\Feature;

use App\Http\Requests\StoreOpinionRequest;
use Tests\TestCase;

class OpinionSubmissionTest extends TestCase
{
    public function test_human_submission_request_requires_scores_and_justifications(): void
    {
        $rules = (new StoreOpinionRequest)->rules();

        $this->assertArrayHasKey('criteria', $rules);
        $this->assertArrayHasKey('justificativa', $rules);
        $this->assertArrayHasKey('criteria.*', $rules);
        $this->assertArrayHasKey('justificativa.*', $rules);
    }
}
