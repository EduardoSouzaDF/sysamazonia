<?php

namespace App\Services;

use App\Enum\RegistrationStatusEnum;
use App\Models\Opinion;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Collection;

class EvaluationCompletionService
{
    public function recalculate(Registration $registration): void
    {
        $validOpinions = $this->validOpinions($registration);
        if ($validOpinions->isEmpty()) {
            $registration->update(['evaluation_avg' => null]);

            return;
        }

        $total = $validOpinions->sum(function (Opinion $opinion): float {
            $weightedSum = 0.0;
            $totalWeight = 0.0;

            foreach ($opinion->scores as $score) {
                $criterion = $score->evaluationCriterion;
                $weight = (float) $criterion->weight;
                $range = (float) $criterion->max_score - (float) $criterion->min_score;
                if ($weight <= 0 || $range <= 0) {
                    continue;
                }

                $weightedSum += (((int) $score->valor - (float) $criterion->min_score) / $range) * $weight;
                $totalWeight += $weight;
            }

            return $totalWeight > 0 ? $weightedSum / $totalWeight : 0.0;
        });

        $registration->update([
            'evaluation_avg' => (int) round(($total / $validOpinions->count()) * 100),
        ]);
    }

    public function hasRequiredQuorum(Registration $registration): bool
    {
        $registration->loadMissing('category');
        $requiredOpinions = (int) $registration->category->evaluations_count;

        return $requiredOpinions > 0 && $this->validOpinions($registration)->count() >= $requiredOpinions;
    }

    public function completeIfReady(Registration $registration): bool
    {
        if ((int) $registration->status !== RegistrationStatusEnum::Habilitado->value
            || ! $this->hasRequiredQuorum($registration)) {
            return false;
        }

        $registration->update(['status' => RegistrationStatusEnum::Avaliado]);

        return true;
    }

    /** @return Collection<int, Opinion> */
    public function validOpinions(Registration $registration): Collection
    {
        $registration->loadMissing(['category.evaluationCriteria', 'category.evaluators']);
        $criteria = $registration->category->evaluationCriteria->keyBy('id');
        $expectedCriterionIds = $criteria->keys()->map(fn ($id): int => (int) $id)->sort()->values()->all();
        $authorizedEvaluatorIds = $registration->category->evaluators->modelKeys();

        if ($expectedCriterionIds === [] || $authorizedEvaluatorIds === []) {
            return new Collection;
        }

        return $registration->opinions()
            ->whereIn('user_id', $authorizedEvaluatorIds)
            ->with('scores.evaluationCriterion')
            ->get()
            ->filter(function (Opinion $opinion) use ($criteria, $expectedCriterionIds): bool {
                $receivedIds = $opinion->scores->pluck('evaluation_criterion_id')->map(fn ($id): int => (int) $id);
                if ($receivedIds->count() !== $receivedIds->unique()->count()
                    || $receivedIds->sort()->values()->all() !== $expectedCriterionIds) {
                    return false;
                }

                return $opinion->scores->every(function ($score) use ($criteria): bool {
                    $criterion = $criteria->get($score->evaluation_criterion_id);

                    return $criterion !== null
                        && $score->valor !== null
                        && (int) $score->valor >= (float) $criterion->min_score
                        && (int) $score->valor <= (float) $criterion->max_score
                        && is_string($score->descricao)
                        && trim($score->descricao) !== '';
                });
            })->values();
    }
}
