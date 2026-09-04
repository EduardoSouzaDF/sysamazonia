<?php

namespace App\Services;

use App\Enum\RegistrationStatusEnum;
use App\Models\Opinion;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpinionSubmissionService
{
    public function __construct(private readonly EvaluationCompletionService $completionService) {}

    /**
     * @param  array<int, array{criterion_id: int, score: int, justification: string}>  $scores
     */
    public function submit(Registration $registration, User $evaluator, array $scores): Opinion
    {
        return DB::transaction(function () use ($registration, $evaluator, $scores): Opinion {
            $lockedRegistration = Registration::query()
                ->with(['category.evaluationCriteria', 'category.evaluators'])
                ->lockForUpdate()
                ->findOrFail($registration->id);

            $this->validateSubmission($lockedRegistration, $evaluator, $scores);

            $opinion = Opinion::query()->create([
                'user_id' => $evaluator->id,
                'registration_id' => $lockedRegistration->id,
            ]);

            foreach ($scores as $score) {
                $opinion->scores()->create([
                    'evaluation_criterion_id' => $score['criterion_id'],
                    'valor' => $score['score'],
                    'descricao' => trim($score['justification']),
                ]);
            }

            $this->completionService->recalculate($lockedRegistration);
            $this->completionService->completeIfReady($lockedRegistration);

            return $opinion;
        }, 3);
    }

    /**
     * @param  array<int, array{criterion_id: int, score: int, justification: string}>  $scores
     */
    private function validateSubmission(Registration $registration, User $evaluator, array $scores): void
    {
        if ((int) $registration->status !== RegistrationStatusEnum::Habilitado->value) {
            throw ValidationException::withMessages(['registration' => 'A inscrição não está habilitada para avaliação.']);
        }

        if (! $registration->category->evaluators->contains('id', $evaluator->id)) {
            throw ValidationException::withMessages(['evaluator' => 'O usuário não é avaliador autorizado desta categoria.']);
        }

        $criteria = $registration->category->evaluationCriteria->keyBy('id');
        $expectedIds = $criteria->keys()->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $receivedIds = collect($scores)->pluck('criterion_id')->map(fn ($id): int => (int) $id);
        if ($receivedIds->count() !== $receivedIds->unique()->count()
            || $receivedIds->sort()->values()->all() !== $expectedIds) {
            throw ValidationException::withMessages(['criteria' => 'Informe exatamente todos os critérios da categoria uma única vez.']);
        }

        foreach ($scores as $index => $score) {
            $criterion = $criteria->get($score['criterion_id']);
            if (! is_int($score['score'])
                || $score['score'] < (float) $criterion->min_score
                || $score['score'] > (float) $criterion->max_score) {
                throw ValidationException::withMessages(["scores.{$index}.score" => 'A nota está fora da escala do critério.']);
            }

            if (trim($score['justification']) === '') {
                throw ValidationException::withMessages(["scores.{$index}.justification" => 'A justificativa é obrigatória.']);
            }
        }
    }
}
