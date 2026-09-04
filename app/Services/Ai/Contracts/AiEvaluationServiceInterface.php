<?php

namespace App\Services\Ai\Contracts;

use App\Data\Ai\EvaluationRequestData;
use App\Data\Ai\EvaluationResultData;
use App\Data\Ai\SelectionRequestData;
use App\Data\Ai\SelectionResultData;

interface AiEvaluationServiceInterface
{
    public function evaluate(EvaluationRequestData $request): EvaluationResultData;

    public function select(SelectionRequestData $request): SelectionResultData;
}
