<?php

namespace App\Services\Analytics;

class AnalyticsRegistry
{
    public const STATES = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public const DIMENSIONS = ['edition', 'category', 'state', 'city', 'education', 'age_group', 'status', 'indication_status', 'day', 'month', 'ai_status', 'criterion', 'evaluator', 'region', 'week', 'days_to_deadline', 'quality_field'];

    public const FILTERS = ['edition_id', 'edition_year', 'category_id', 'state', 'status', 'date_from', 'date_to', 'edition_ids', 'last_days'];

    public const CHARTS = ['kpi', 'table', 'bar', 'horizontal_bar', 'line', 'area', 'donut', 'stacked_bar', 'map_brazil'];

    public static function label(string $metric): string
    {
        return [
            'registrations_count' => 'Total de inscrições', 'enabled_registrations_count' => 'Habilitadas',
            'evaluated_registrations_count' => 'Avaliadas', 'indicated_count' => 'Indicadas', 'not_indicated_count' => 'Não indicadas',
            'indication_rate' => 'Taxa de indicação (%)', 'evaluation_rate' => 'Taxa de avaliação (%)',
            'average_score' => 'Nota média consolidada', 'average_criterion_score' => 'Nota média bruta por critério',
            'ai_execution_count' => 'Execuções IA', 'ai_failure_count' => 'Falhas IA', 'ai_success_rate' => 'Sucesso IA (%)',
            'ai_average_duration_ms' => 'Duração média IA (ms)', 'invalid_birth_date_count' => 'Nascimento inválido/desconhecido',
            'missing_education_count' => 'Escolaridade ausente', 'missing_state_count' => 'UF ausente',
            'missing_city_count' => 'Município ausente', 'missing_category_count' => 'Categoria ausente',
            'missing_fields_count' => 'Ocorrências de campos ausentes',
        ][$metric];
    }

    public static function metrics(): array
    {
        return [
            'missing_fields_count' => ['COUNT(*)', 'Ocorrências de campos ausentes por inscrição. Uma inscrição pode aparecer em vários campos; não some como candidatos únicos.'],
            'average_criterion_score' => ['AVG(s.valor)', 'Média bruta das notas registradas por critério; não substitui a nota consolidada e pode incluir pareceres incompletos.'],
            'registrations_count' => ['COUNT(*)', 'Total de inscrições (não candidatos únicos).'],
            'enabled_registrations_count' => ['SUM(CASE WHEN r.status IN (3,4,5) THEN 1 ELSE 0 END)', 'Inscrições habilitadas, avaliadas ou agraciadas.'],
            'evaluated_registrations_count' => ['SUM(CASE WHEN r.status IN (4,5) THEN 1 ELSE 0 END)', 'Inscrições avaliadas ou agraciadas, pelo status oficial.'],
            'indicated_count' => ['SUM(CASE WHEN i.indicated = 1 THEN 1 ELSE 0 END)', 'Inscrições com pelo menos uma indicação positiva, incluindo indicação humana legada sem decision.'],
            'not_indicated_count' => ['SUM(CASE WHEN i.indicated = 0 THEN 1 ELSE 0 END)', 'Inscrições com decisão negativa e nenhuma indicação positiva; pendentes excluídas.'],
            'indication_rate' => ['100.0 * SUM(CASE WHEN i.indicated = 1 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)', 'Percentual de inscrições com indicação positiva sobre todas as inscrições filtradas.'],
            'evaluation_rate' => ['100.0 * SUM(CASE WHEN r.status IN (4,5) THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)', 'Percentual de inscrições avaliadas ou agraciadas sobre todas as inscrições filtradas.'],
            'average_score' => ['AVG(r.evaluation_avg)', 'Média das notas consolidadas oficiais (0–100), ignorando notas nulas.'],
            'invalid_birth_date_count' => ['SUM(CASE WHEN a.age IS NULL OR a.age < 0 OR a.age > 120 THEN 1 ELSE 0 END)', 'Nascimento ausente, inválido, futuro ou idade acima de 120 anos na inscrição.'],
            'missing_education_count' => ["SUM(CASE WHEN c.escolaridade IS NULL OR TRIM(c.escolaridade) = '' THEN 1 ELSE 0 END)", 'Inscrições com escolaridade ausente.'],
            'missing_state_count' => ["SUM(CASE WHEN c.ufendereco IS NULL OR TRIM(c.ufendereco) = '' THEN 1 ELSE 0 END)", 'Inscrições com UF ausente.'],
            'missing_city_count' => ["SUM(CASE WHEN c.cidade IS NULL OR TRIM(c.cidade) = '' THEN 1 ELSE 0 END)", 'Inscrições com município ausente.'],
            'missing_category_count' => ['SUM(CASE WHEN cat.id IS NULL THEN 1 ELSE 0 END)', 'Inscrições sem categoria relacionada.'],
            'ai_execution_count' => ['COUNT(*)', 'Execuções IA, contando cada execução uma vez.'],
            'ai_failure_count' => ["SUM(CASE WHEN x.status = 'failed' THEN 1 ELSE 0 END)", 'Execuções IA com status failed.'],
            'ai_success_rate' => ["100.0 * SUM(CASE WHEN x.status = 'completed' THEN 1 ELSE 0 END) / NULLIF(SUM(CASE WHEN x.status IN ('completed','failed') THEN 1 ELSE 0 END),0)", 'Percentual completed entre completed e failed; pendentes excluídas.'],
            'ai_average_duration_ms' => ["AVG(CASE WHEN x.status = 'completed' THEN x.duration_ms END)", 'Duração média em ms das execuções concluídas com duração registrada.'],
        ];
    }

    public static function catalog(): array
    {
        return ['metrics' => array_map(fn ($m) => $m[1], self::metrics()), 'dimensions' => self::DIMENSIONS, 'filters' => self::FILTERS, 'visualizations' => self::CHARTS];
    }
}
