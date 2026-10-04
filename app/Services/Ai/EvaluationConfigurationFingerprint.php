<?php

namespace App\Services\Ai;

use App\Models\Registration;

class EvaluationConfigurationFingerprint
{
    public function forRegistration(Registration $registration): string
    {
        $registration->loadMissing('category.evaluationCriteria');
        $criteria = $registration->category->evaluationCriteria
            ->sortBy('id')
            ->map(fn ($criterion): array => [
                'criterion_id' => (int) $criterion->id,
                'description' => $criterion->description,
                'min_score' => (string) $criterion->min_score,
                'max_score' => (string) $criterion->max_score,
                'rubric' => $this->canonicalize($criterion->rubric ?? []),
                'rubric_version' => $criterion->rubric_version,
            ])->values()->all();

        return hash('sha256', json_encode($criteria, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    public function forSelection(Registration $registration): string
    {
        $registration->loadMissing('opinions.scores.evaluationCriterion');
        $payload = [
            'registration' => [
                'title' => $registration->title,
                'resumo' => $registration->resumo,
                'objetivo' => $registration->objetivo,
                'desenvolvimento' => $registration->desenvolvimento,
                'conclusao' => $registration->conclusao,
            ],
            'evaluations' => $registration->opinions->flatMap(fn ($opinion) => $opinion->scores->map(fn ($score): array => [
                'opinion_id' => (int) $opinion->id,
                'criterion_id' => (int) $score->evaluation_criterion_id,
                'score' => (int) $score->valor,
                'justification' => $score->descricao,
            ]))->sortBy(fn (array $item): string => sprintf('%020d:%020d', $item['opinion_id'], $item['criterion_id']))->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
