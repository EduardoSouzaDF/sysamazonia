<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpinionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'criteria' => ['required', 'array', 'min:1'],
            'justificativa' => ['required', 'array'],
            'criteria.*' => ['required', 'integer'],
            'justificativa.*' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    /** @return array<int, array{criterion_id: int, score: int, justification: string}> */
    public function scores(): array
    {
        $justifications = $this->validated('justificativa');

        return collect($this->validated('criteria'))->map(
            fn ($score, $criterionId): array => [
                'criterion_id' => (int) $criterionId,
                'score' => (int) $score,
                'justification' => (string) ($justifications[$criterionId] ?? ''),
            ]
        )->values()->all();
    }
}
