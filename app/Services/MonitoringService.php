<?php

namespace App\Services;

use App\Enum\RegistrationStatusEnum;
use App\Models\Category;
use App\Models\Edition;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoringService
{
    public function report(?int $editionId = null): array
    {
        $editions = Edition::query()->orderByDesc('registration_start')->orderByDesc('id')->get();
        $edition = $editionId !== null ? $editions->firstWhere('id', $editionId)
            : ($editions->firstWhere('is_registration_active', true) ?? $editions->first());
        $categories = Category::query()->where('is_honorific', false)
            ->whereHas('modality', fn (Builder $query) => $query->where('edition_id', $edition?->id))
            ->orderBy('title')->with(['registrations' => function (HasMany $query): void {
                $this->evaluated($query)->with(['candidate:id,nome,ufendereco', 'opinions.user:id,name', 'opinions.scores.evaluationCriterion', 'indications.user:id,name'])
                    ->orderByDesc('evaluation_avg')->orderBy('id')->limit(20);
            }])->get();
        $counts = $this->evaluated(Registration::query())->whereIn('category_id', $categories->pluck('id'))
            ->select('category_id', 'evaluation_avg')->selectRaw('COUNT(*) AS total')
            ->groupBy('category_id', 'evaluation_avg')->get()->groupBy('category_id');
        $distributions = [];
        foreach ($categories as $category) {
            $distribution = ['Recomendada' => 0, 'Meritória' => 0, 'Não recomendada' => 0];
            foreach ($counts->get($category->id, collect()) as $score) {
                $label = match ($score->getTextEvaluationAvg()) {
                    'Recomendado' => 'Recomendada',
                    'Meritório' => 'Meritória',
                    default => 'Não recomendada',
                };
                $distribution[$label] += (int) $score->total;
            }
            $distributions[$category->id] = $distribution;
        }

        $generalCounts = $this->evaluated(Registration::query())
            ->whereHas('category', fn (Builder $query) => $query->where('is_honorific', false)
                ->whereHas('modality.edition'))
            ->select('evaluation_avg')->selectRaw('COUNT(*) AS total')
            ->groupBy('evaluation_avg')->get();
        $generalDistribution = ['Recomendada' => 0, 'Meritória' => 0, 'Não recomendada' => 0];
        foreach ($generalCounts as $score) {
            $label = match ($score->getTextEvaluationAvg()) {
                'Recomendado' => 'Recomendada',
                'Meritório' => 'Meritória',
                default => 'Não recomendada',
            };
            $generalDistribution[$label] += (int) $score->total;
        }

        $evaluatedByEdition = $this->evaluated(Registration::query())
            ->whereHas('category', fn (Builder $query) => $query->where('is_honorific', false)->whereHas('modality.edition'))
            ->with('category.modality')->get(['id', 'category_id', 'evaluation_avg'])
            ->groupBy(fn (Registration $registration) => $registration->category->modality->edition_id);
        $chronologicalEditions = $editions->sortBy('id')->sortBy('registration_start')->values();
        $qualityLabels = $chronologicalEditions->map(fn (Edition $edition) => $edition->title
            . ($edition->registration_start ? ' ('.$edition->registration_start->format('Y').')' : ''))->all();
        $averages = $chronologicalEditions->map(function (Edition $edition) use ($evaluatedByEdition): ?float {
            $registrations = $evaluatedByEdition->get($edition->id);
            return $registrations?->isNotEmpty() ? (float) $registrations->avg('evaluation_avg') / 2 : null;
        })->all();
        // Least-squares regression over the chronological edition positions.
        $points = array_filter($averages, fn ($value) => $value !== null);
        $trend = array_fill(0, count($averages), null);
        if (count($points) >= 2) {
            $meanX = array_sum(array_keys($points)) / count($points);
            $meanY = array_sum($points) / count($points);
            $numerator = $denominator = 0.0;
            foreach ($points as $x => $y) {
                $numerator += ($x - $meanX) * ($y - $meanY);
                $denominator += ($x - $meanX) ** 2;
            }
            $slope = $numerator / $denominator;
            foreach ($trend as $x => $_) {
                $trend[$x] = round($meanY + $slope * ($x - $meanX), 2);
            }
        }
        $qualitySeries = [
            ['name' => 'Qualidade — nota média', 'data' => array_map(fn ($value) => $value === null ? null : round($value, 2), $averages)],
            ['name' => 'Tendência da qualidade', 'data' => $trend],
        ];
        $hasQualityData = count($points) > 0;

        return compact('editions', 'edition', 'categories', 'distributions', 'generalDistribution', 'qualityLabels', 'qualitySeries', 'hasQualityData');
    }

    private function evaluated(Builder|HasMany $query): Builder|HasMany
    {
        return $query->whereIn('status', [RegistrationStatusEnum::Avaliado->value, RegistrationStatusEnum::Agraciado->value])
            ->whereNotNull('evaluation_avg')->whereBetween('evaluation_avg', [0, 100]);
    }
}
