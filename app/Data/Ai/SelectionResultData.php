<?php

namespace App\Data\Ai;

use App\Enum\SelectionDecision;
use Illuminate\Support\Facades\Validator;

final readonly class SelectionResultData
{
    public function __construct(
        public int $registrationId,
        public string $promptVersion,
        public string $provider,
        public string $model,
        public SelectionDecision $decision,
        public string $justification,
    ) {}

    public static function fromArray(array $payload, SelectionRequestData $request): self
    {
        $validated = Validator::make($payload, [
            'inscricao_id' => ['required', 'integer', 'in:'.$request->registrationId],
            'prompt_version' => ['required', 'string', 'in:'.$request->promptVersion],
            'provider' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:120'],
            'indicacao' => ['required', 'string', 'in:'.implode(',', array_column(SelectionDecision::cases(), 'value'))],
            'justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        return new self(
            $validated['inscricao_id'],
            $validated['prompt_version'],
            $validated['provider'],
            $validated['model'],
            SelectionDecision::from($validated['indicacao']),
            $validated['justificativa'],
        );
    }
}
