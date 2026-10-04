<?php

namespace App\Data\Ai;

use App\Enum\SelectionDecision;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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

        $wordCount = preg_match_all('/[\p{L}\p{N}]+(?:[\x{2019}\'\-][\p{L}\p{N}]+)*/u', $validated['justificativa']);
        if ($wordCount < 50 || $wordCount > 150) {
            throw ValidationException::withMessages(['justificativa' => 'A justificativa deve conter entre 50 e 150 palavras.']);
        }

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
