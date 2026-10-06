<?php

namespace App\Services;

use App\Models\Edition;
use App\Support\BrazilStates;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardStatisticsService
{
    public function statistics(): array
    {
        $editions = Edition::query()->orderByDesc('registration_start')->orderByDesc('id')
            ->get(['id', 'title', 'registration_start', 'is_registration_active']);
        $active = $editions->where('is_registration_active', true);
        $edition = $active->first() ?? $editions->first();
        if ($active->count() > 1) {
            Log::warning('Dashboard: mais de uma edição ativa; selecionada a mais recente.', [
                'edition_id' => $edition->id, 'active_count' => $active->count(),
            ]);
        }
        $ttl = max(0, min(300, (int) config('dashboard.cache_ttl', 30)));
        $global = fn () => $this->aggregate(null);

        return [
            'edition' => $edition ? ['id' => $edition->id, 'title' => $edition->title] : null,
            'fallback' => $edition !== null && $active->isEmpty(),
            'cacheTtl' => $ttl,
            'global' => $ttl ? Cache::remember('dashboard.statistics.v1.'.($edition?->id ?? 'none'), $ttl, $global) : $global(),
            'current' => $edition ? $this->aggregate($edition->id) : null,
        ];
    }

    private function inscriptions(?int $editionId): Builder
    {
        $regular = DB::table('registrations')->select('candidate_id', 'category_id', 'created_at')->selectRaw("'regular' AS source");
        $honorary = DB::table('nominees')->select('candidate_id', 'category_id', 'created_at')->selectRaw("'honorary' AS source");

        return DB::query()->fromSub($regular->unionAll($honorary), 'i')
            ->leftJoin('candidates as c', 'c.id', '=', 'i.candidate_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'i.category_id')
            ->leftJoin('modalities as m', 'm.id', '=', 'cat.modality_id')
            ->leftJoin('editions as e', 'e.id', '=', 'm.edition_id')
            ->when($editionId !== null, fn (Builder $query) => $query->where('e.id', $editionId));
    }

    private function aggregate(?int $editionId): array
    {
        $base = $this->inscriptions($editionId);
        $totals = (clone $base)->selectRaw("COUNT(*) AS total, SUM(CASE WHEN i.source = 'regular' THEN 1 ELSE 0 END) AS regular, SUM(CASE WHEN i.source = 'honorary' THEN 1 ELSE 0 END) AS honorary")->first();
        $total = (int) $totals->total;
        $states = $this->group($base, $this->stateSql(), $total);
        $stateCounts = array_column($states, 'total', 'label');
        $regions = array_fill_keys(BrazilStates::REGIONS, 0);
        $map = [];
        foreach (BrazilStates::STATES as $uf => $metadata) {
            $count = $stateCounts[$uf] ?? 0;
            $regions[$metadata['region']] += $count;
            $map[] = $metadata + ['uf' => $uf, 'total' => $count, 'percentage' => $this->percentage($count, $total)];
        }
        $unmapped = ($stateCounts['Não informado'] ?? 0) + ($stateCounts['UF inválida'] ?? 0);
        $regionRows = [];
        foreach ($regions as $label => $count) {
            $regionRows[] = $this->row($label, $count, $total);
        }
        if ($unmapped) {
            $regionRows[] = $this->row('Não informado / UF inválida', $unmapped, $total);
        }
        $ageCounts = array_column($this->group($base, $this->ageGroupSql(), $total), 'total', 'label');
        $ages = [];
        foreach (['Até 17', '18 a 30', '31 a 40', '41 a 59', '60 ou mais', 'Não informado'] as $label) {
            if ($label === 'Até 17' && empty($ageCounts[$label])) {
                continue;
            }
            $ages[] = $this->row($label, $ageCounts[$label] ?? 0, $total);
        }
        $data = [
            'total' => $total, 'regular' => (int) $totals->regular, 'honorary' => (int) $totals->honorary,
            'modalities' => $this->taxonomy($base, 'modality', $editionId, $total),
            'categories' => $this->taxonomy($base, 'category', $editionId, $total),
            'states' => $states, 'map' => $map, 'regions' => $regionRows, 'unmapped' => $unmapped,
            'ages' => $ages, 'generatedAt' => now()->format('d/m/Y H:i:s'),
        ];
        if ($editionId === null) {
            $data['editions'] = $this->taxonomy($base, 'edition', null, $total);
        } else {
            $data['sex'] = $this->group($base, "COALESCE(NULLIF(TRIM(c.sexo), ''), 'Não informado')", $total);
            $data['education'] = $this->group($base, "COALESCE(NULLIF(TRIM(c.escolaridade), ''), 'Não informado')", $total);
        }

        return $data;
    }

    private function group(Builder $base, string $expression, int $total): array
    {
        return (clone $base)->selectRaw("$expression AS label, COUNT(*) AS total")
            ->groupBy('label')->orderByDesc('total')->orderBy('label')->get()
            ->map(fn ($row) => $this->row((string) $row->label, (int) $row->total, $total))->all();
    }

    private function taxonomy(Builder $base, string $dimension, ?int $editionId, int $total): array
    {
        $column = ['edition' => 'e.id', 'modality' => 'm.id', 'category' => 'cat.id'][$dimension];
        $counts = (clone $base)->selectRaw("$column AS dimension_id, COUNT(*) AS total")
            ->groupBy($column)->pluck('total', 'dimension_id');
        $catalog = match ($dimension) {
            'edition' => DB::table('editions as d')->select('d.id', 'd.title')->orderBy('d.registration_start')->orderBy('d.id'),
            'modality' => DB::table('modalities as d')->leftJoin('editions as e', 'e.id', '=', 'd.edition_id')
                ->select('d.id', 'd.title', 'e.title as edition')->when($editionId, fn ($q) => $q->where('e.id', $editionId))->orderBy('d.id'),
            'category' => DB::table('categories as d')->leftJoin('modalities as m', 'm.id', '=', 'd.modality_id')
                ->leftJoin('editions as e', 'e.id', '=', 'm.edition_id')->select('d.id', 'd.title', 'm.title as modality', 'e.title as edition')
                ->when($editionId, fn ($q) => $q->where('e.id', $editionId))->orderBy('d.id'),
        };
        $rows = [];
        foreach ($catalog->get() as $item) {
            $label = $item->title;
            if ($dimension === 'category') {
                $label .= ' · '.($item->modality ?? 'Modalidade não informada');
            }
            if ($dimension !== 'edition' && $editionId === null) {
                $label .= ' · '.($item->edition ?? 'Edição não informada');
            }
            $rows[] = $this->row($label, (int) ($counts[$item->id] ?? 0), $total) + ['id' => $item->id];
        }
        if (isset($counts[''])) {
            $rows[] = $this->row('Não informado', (int) $counts[''], $total) + ['id' => null];
        }

        return $rows;
    }

    private function stateSql(): string
    {
        $ufs = implode("','", array_keys(BrazilStates::STATES));

        return "CASE WHEN c.ufendereco IS NULL OR TRIM(c.ufendereco) = '' THEN 'Não informado' WHEN UPPER(TRIM(c.ufendereco)) IN ('$ufs') THEN UPPER(TRIM(c.ufendereco)) ELSE 'UF inválida' END";
    }

    private function ageGroupSql(): string
    {
        // Date components avoid engine-specific birthday rounding and reject impossible dates.
        $birth = 'SUBSTR(c.dt_nascimento, 1, 10)';
        $date = 'SUBSTR(i.created_at, 1, 10)';
        $cast = DB::connection()->getDriverName() === 'sqlite' ? 'INTEGER' : 'SIGNED';
        $age = "(CAST(SUBSTR($date, 1, 4) AS $cast) - CAST(SUBSTR($birth, 1, 4) AS $cast) - CASE WHEN SUBSTR($date, 6, 5) < SUBSTR($birth, 6, 5) THEN 1 ELSE 0 END)";
        $valid = $this->validDateSql($birth).' AND '.$this->validDateSql($date)." AND LENGTH(c.dt_nascimento) = 10 AND $birth <= $date";

        return "CASE WHEN NOT ($valid) OR c.dt_nascimento IS NULL OR i.created_at IS NULL THEN 'Não informado' WHEN $age < 18 THEN 'Até 17' WHEN $age <= 30 THEN '18 a 30' WHEN $age <= 40 THEN '31 a 40' WHEN $age <= 59 THEN '41 a 59' ELSE '60 ou mais' END";
    }

    private function validDateSql(string $date): string
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $cast = $sqlite ? 'INTEGER' : 'SIGNED';
        $year = "CAST(SUBSTR($date, 1, 4) AS $cast)";
        $month = "CAST(SUBSTR($date, 6, 2) AS $cast)";
        $day = "CAST(SUBSTR($date, 9, 2) AS $cast)";
        $format = $sqlite ? "$date GLOB '[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]'" : "$date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'";
        $lastDay = "CASE WHEN $month = 2 THEN CASE WHEN ($year % 4 = 0 AND $year % 100 <> 0) OR $year % 400 = 0 THEN 29 ELSE 28 END WHEN $month IN (4,6,9,11) THEN 30 ELSE 31 END";

        return "($format AND $year BETWEEN 1 AND 9999 AND $month BETWEEN 1 AND 12 AND $day BETWEEN 1 AND ($lastDay))";
    }

    private function row(string $label, int $count, int $total): array
    {
        return ['label' => $label, 'total' => $count, 'percentage' => $this->percentage($count, $total)];
    }

    private function percentage(int $count, int $total): float
    {
        return $total ? round(100 * $count / $total, 2) : 0.0;
    }
}
