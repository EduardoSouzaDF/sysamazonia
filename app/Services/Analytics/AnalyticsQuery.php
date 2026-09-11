<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalyticsQuery
{
    public function execute(array $input): array
    {
        $p = AnalyticsPlan::validate($input);
        $ai = str_starts_with($p['metric'], 'ai_');
        $mysql = DB::connection()->getDriverName() === 'mysql';
        $age = $mysql
            ? "CASE WHEN candidates.dt_nascimento IS NULL OR YEAR(candidates.dt_nascimento) < 1 OR MONTH(candidates.dt_nascimento) < 1 OR DAY(candidates.dt_nascimento) < 1 OR DAY(candidates.dt_nascimento) > DAY(LAST_DAY(candidates.dt_nascimento)) OR candidates.dt_nascimento > registrations.created_at THEN NULL ELSE TIMESTAMPDIFF(YEAR, NULLIF(CAST(candidates.dt_nascimento AS CHAR), '0000-00-00'), registrations.created_at) END"
            : "CASE WHEN DATE(candidates.dt_nascimento, '+0 days') != DATE(candidates.dt_nascimento) OR candidates.dt_nascimento > registrations.created_at THEN NULL ELSE CAST(strftime('%Y', registrations.created_at) AS INTEGER) - CAST(strftime('%Y', candidates.dt_nascimento) AS INTEGER) - (strftime('%m-%d', registrations.created_at) < strftime('%m-%d', candidates.dt_nascimento)) END";
        $ages = DB::table('registrations')->leftJoin('candidates', 'candidates.id', '=', 'registrations.candidate_id')->select('registrations.id')->selectRaw("$age AS age");
        $indications = DB::table('indications')->select('registration_id')->selectRaw("MAX(CASE WHEN decision IS NULL OR decision = 'INDICADA' THEN 1 WHEN decision = 'NAO_INDICADA' THEN 0 ELSE NULL END) AS indicated")->groupBy('registration_id');
        $q = DB::table('registrations as r')->leftJoin('candidates as c', 'c.id', '=', 'r.candidate_id')
            ->leftJoin('categories as cat', 'cat.id', '=', 'r.category_id')->leftJoin('modalities as m', 'm.id', '=', 'cat.modality_id')
            ->leftJoin('editions as e', 'e.id', '=', 'm.edition_id')->leftJoinSub($ages, 'a', 'a.id', '=', 'r.id')
            ->leftJoinSub($indications, 'i', 'i.registration_id', '=', 'r.id');
        if ($ai) {
            $q->join('ai_executions as x', 'x.registration_id', '=', 'r.id');
        }
        if ($p['metric'] === 'average_criterion_score') {
            $q->join('opinions as o', 'o.registration_id', '=', 'r.id')->join('scores as s', 's.opinion_id', '=', 'o.id')->join('evaluation_criteria as ec', 'ec.id', '=', 's.evaluation_criterion_id')->whereColumn('ec.category_id', 'r.category_id')->whereNotNull('s.valor');
        }
        $date = $ai ? 'x.created_at' : 'r.created_at';
        foreach ($p['filters'] as $key => $value) {
            match ($key) {
                'edition_ids' => $q->whereIn('e.id', $value),
                'last_days' => $mysql ? $q->whereRaw("DATE($date) BETWEEN DATE_SUB(e.registration_end, INTERVAL ? DAY) AND e.registration_end", [$value - 1]) : $q->whereRaw("DATE($date) BETWEEN DATE(e.registration_end, ?) AND e.registration_end", ['-'.($value - 1).' days']),
                'edition_id' => $q->where('e.id', $value),
                'edition_year' => $q->whereYear('e.registration_start', $value),
                'category_id' => $q->where('cat.id', $value),
                'state' => $q->whereIn('c.ufendereco', $value),
                'status' => $q->where('r.status', $value),
                'date_from' => $q->where($date, '>=', $value.' 00:00:00'),
                'date_to' => $q->where($date, '<', \Carbon\CarbonImmutable::parse($value)->addDay()->format('Y-m-d')),
            };
        }
        $dimensions = [
            'criterion' => $mysql ? "CONCAT(ec.name, ' (#', ec.id, ')')" : "ec.name || ' (#' || ec.id || ')'", 'evaluator' => $mysql ? "CONCAT('Avaliador #', o.user_id)" : "'Avaliador #' || o.user_id",
            'week' => $mysql ? "DATE_SUB(DATE($date), INTERVAL WEEKDAY($date) DAY)" : "DATE($date, '-' || ((CAST(strftime('%w', $date) AS INTEGER) + 6) % 7) || ' days')",
            'days_to_deadline' => $mysql ? 'DATEDIFF(r.created_at, e.registration_end)' : 'CAST(julianday(DATE(r.created_at)) - julianday(e.registration_end) AS INTEGER)',
            'region' => "CASE WHEN c.ufendereco IN ('AC','AP','AM','PA','RO','RR','TO') THEN 'Norte' WHEN c.ufendereco IN ('AL','BA','CE','MA','PB','PE','PI','RN','SE') THEN 'Nordeste' WHEN c.ufendereco IN ('DF','GO','MT','MS') THEN 'Centro-Oeste' WHEN c.ufendereco IN ('ES','MG','RJ','SP') THEN 'Sudeste' WHEN c.ufendereco IN ('PR','RS','SC') THEN 'Sul' ELSE 'Desconhecida' END",
            'edition' => $mysql ? "COALESCE(CONCAT(e.title, ' (#', e.id, ')'), 'Sem edição')" : "COALESCE(e.title || ' (#' || e.id || ')', 'Sem edição')", 'category' => $mysql ? "COALESCE(CONCAT(cat.title, ' (#', cat.id, ')'), 'Sem categoria')" : "COALESCE(cat.title || ' (#' || cat.id || ')', 'Sem categoria')",
            'state' => "COALESCE(NULLIF(TRIM(c.ufendereco), ''), 'Desconhecido')",
            'city' => $mysql ? "CONCAT(COALESCE(NULLIF(TRIM(c.cidade), ''), 'Desconhecido'), ' / ', COALESCE(c.ufendereco, '?'))" : "COALESCE(NULLIF(TRIM(c.cidade), ''), 'Desconhecido') || ' / ' || COALESCE(c.ufendereco, '?')",
            'education' => "COALESCE(NULLIF(TRIM(c.escolaridade), ''), 'Desconhecida')",
            'age_group' => "CASE WHEN a.age IS NULL OR a.age < 0 OR a.age > 120 THEN 'Data inválida/desconhecida' WHEN a.age < 18 THEN 'Menor de 18' WHEN a.age <= 30 THEN '18–30' WHEN a.age <= 40 THEN '31–40' WHEN a.age <= 50 THEN '41–50' WHEN a.age <= 60 THEN '51–60' ELSE 'Acima de 60' END",
            'status' => "CASE r.status WHEN 1 THEN 'Inscrito' WHEN 2 THEN 'Rejeitado' WHEN 3 THEN 'Habilitado' WHEN 4 THEN 'Avaliado' WHEN 5 THEN 'Agraciado' ELSE 'Desconhecido' END",
            'indication_status' => "CASE i.indicated WHEN 1 THEN 'Indicada' WHEN 0 THEN 'Não indicada' ELSE 'Pendente' END",
            'day' => "DATE($date)", 'month' => $mysql ? "DATE_FORMAT($date, '%Y-%m')" : "strftime('%Y-%m', $date)", 'ai_status' => 'x.status',
        ];
        if ($p['metric'] === 'missing_fields_count') {
            $fields = ['education' => "c.escolaridade IS NULL OR TRIM(c.escolaridade) = ''", 'state' => "c.ufendereco IS NULL OR TRIM(c.ufendereco) = ''", 'city' => "c.cidade IS NULL OR TRIM(c.cidade) = ''", 'category' => 'cat.id IS NULL', 'birth_date' => 'c.dt_nascimento IS NULL'];
            $quality = clone $q;
            if ($mysql) {
                $quality->selectRaw('/*+ MAX_EXECUTION_TIME(5000) */ 1 AS analytics_marker');
            }
            foreach ($fields as $field => $condition) {
                $quality->selectRaw("SUM(CASE WHEN $condition THEN 1 ELSE 0 END) AS $field");
            }
            $qualityRows = [];
            foreach (array_intersect_key((array) $quality->first(), $fields) as $field => $count) {
                $qualityRows[] = ['quality_field' => $field, 'value' => (int) $count, 'records' => (int) $count];
            }
            usort($qualityRows, fn ($a, $b) => $b['value'] <=> $a['value']);
        }
        $expression = AnalyticsRegistry::metrics()[$p['metric']][0];
        if (str_ends_with($p['metric'], '_count')) {
            $expression = 'COALESCE('.$expression.', 0)';
        }
        $hint = $mysql ? '/*+ MAX_EXECUTION_TIME(5000) */ ' : '';
        $summary = (clone $q)->selectRaw($hint."$expression AS value, COUNT(*) AS records, MIN($date) AS period_start, MAX($date) AS period_end")->first();
        if ($mysql) {
            $q->selectRaw('/*+ MAX_EXECUTION_TIME(5000) */ 1 AS analytics_marker');
        }
        foreach (isset($qualityRows) ? [] : $p['dimensions'] as $d) {
            $q->selectRaw($dimensions[$d].' AS '.$d)->groupByRaw($dimensions[$d]);
            // Preserve distinct entities even when their titles are identical.
            if (in_array($d, ['edition', 'category'])) {
                $q->groupBy($d === 'edition' ? 'e.id' : 'cat.id');
            }
            if ($d === 'criterion') {
                $q->groupBy('ec.id');
            }
            if ($d === 'city') {
                $q->groupBy('c.ufendereco');
            }
        }
        $q->selectRaw("$expression AS value, COUNT(*) AS records");
        $cumulative = $p['cumulative'] ?? false;
        if (isset($qualityRows)) {
            // Fixed quality report already ordered by missing occurrences.
        } elseif ($cumulative) {
            foreach ($p['dimensions'] as $d) {
                $q->orderBy($d);
            }
        } else {
            foreach ($p['sort'] as $s) {
                $q->orderBy($s['field'], $s['direction']);
            }
            foreach ($p['dimensions'] as $d) {
                $q->orderBy($d);
            }
        }
        // A bounded result must never silently look like a complete distribution.
        $rows = $qualityRows ?? $q->limit(501)->get()->map(function ($row) {
            $r = (array) $row;
            unset($r['analytics_marker']);

            return $r;
        })->all();
        if ($cumulative && count($rows) > $p['limit']) {
            throw ValidationException::withMessages(['plan' => 'Curva acumulada excede o limite. Use meses ou refine o período.']);
        }
        if (count($rows) > 500) {
            throw ValidationException::withMessages(['plan' => 'Mais de 500 grupos. Refine o período ou os filtros.']);
        }
        if ($cumulative) {
            $running = [];
            foreach ($rows as &$row) {
                $key = implode('|', array_map(fn ($d) => (string) $row[$d], array_diff($p['dimensions'], ['day', 'month', 'week', 'days_to_deadline'])));
                $running[$key] = ($running[$key] ?? 0) + $row['value'];
                $row['value'] = $running[$key];
            }
            unset($row);
        }
        $groups = count($rows);
        if (isset($qualityRows)) {
            $summary->value = array_sum(array_column($qualityRows, 'value'));
        }
        $rows = array_slice($rows, 0, $p['limit']);
        $type = $p['visualization']['type'];
        $warnings = [];
        if ($groups > $p['limit']) {
            $warnings[] = "Exibindo {$p['limit']} de $groups grupos; o KPI considera todos os registros filtrados.";
        }
        if ($type === 'map_brazil') {
            $type = 'horizontal_bar';
            $warnings[] = 'Mapa indisponível: usando barras e tabela por UF.';
        }
        if (! $p['dimensions']) {
            $type = 'kpi';
        } elseif (array_intersect($p['dimensions'], ['day', 'month', 'week', 'days_to_deadline'])) {
            $type = in_array($type, ['table', 'area', 'bar', 'horizontal_bar']) ? $type : 'line';
        } elseif (count($p['dimensions']) > 1 && $type !== 'table') {
            $type = 'stacked_bar';
        } elseif ($type === 'donut' && count($rows) > 8) {
            $type = 'horizontal_bar';
        }
        $value = $summary->value === null ? null : round((float) $summary->value, 2);

        return ['answer' => $summary->records ? 'Resultado calculado sobre os registros filtrados.' : 'Nenhum registro encontrado para esses filtros.',
            'metric' => $p['metric'], 'filters' => $p['filters'], 'context' => $p,
            'kpis' => [['label' => AnalyticsRegistry::label($p['metric']), 'value' => $value]], 'columns' => array_merge($p['dimensions'], ['value', 'records']),
            'rows' => $rows, 'total_groups' => $groups, 'chart' => ['type' => $type, 'x' => $p['dimensions'][0] ?? null, 'series' => ['value']],
            'warnings' => $warnings, 'source' => $ai ? 'MySQL / ai_executions e inscrições relacionadas' : ($p['metric'] === 'average_criterion_score' ? 'MySQL / scores, opinions e critérios; registros agregados são notas' : 'MySQL / registrations e relacionamentos'),
            'period' => ['from' => $p['filters']['date_from'] ?? $summary->period_start, 'to' => $p['filters']['date_to'] ?? $summary->period_end, 'timezone' => config('app.timezone'), 'field' => $date],
            'records_aggregated' => (int) $summary->records, 'queried_at' => now()->toIso8601String(),
            'calculation' => AnalyticsRegistry::metrics()[$p['metric']][1].' Idade na data de criação da inscrição. Período baseado em created_at.'];
    }
}
