<?php

namespace App\Services\Analytics;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnalyticsPlan
{
    public static function validate(array $input): array
    {
        if (isset($input['dimensions']) && ! is_array($input['dimensions'])) {
            self::fail();
        }
        $allowed = ['intent', 'metric', 'dimensions', 'filters', 'sort', 'limit', 'visualization', 'cumulative'];
        if (array_diff(array_keys($input), $allowed)) {
            self::fail();
        }
        $p = Validator::make($input, [
            'intent' => ['required', Rule::in(['analytics'])],
            'metric' => ['required', Rule::in(array_keys(AnalyticsRegistry::metrics()))],
            'dimensions' => 'present|array|max:2', 'dimensions.*' => ['string', 'distinct', Rule::in(AnalyticsRegistry::DIMENSIONS)],
            'filters' => ['present', 'array:'.implode(',', AnalyticsRegistry::FILTERS)],
            'filters.edition_ids' => 'array|min:1|max:5', 'filters.edition_ids.*' => 'integer|distinct|exists:editions,id',
            'filters.last_days' => 'integer|min:1|max:366',
            'filters.edition_id' => 'integer|min:1|exists:editions,id', 'filters.edition_year' => 'integer|min:1900|max:2100',
            'filters.category_id' => 'integer|min:1|exists:categories,id',
            'filters.state' => 'array|min:1|max:27', 'filters.state.*' => ['distinct', Rule::in(AnalyticsRegistry::STATES)],
            'filters.status' => ['integer', Rule::in([0, 1, 2, 3, 4, 5])],
            'filters.date_from' => 'required_with:filters.date_to|date_format:Y-m-d',
            'filters.date_to' => 'required_with:filters.date_from|date_format:Y-m-d|after_or_equal:filters.date_from',
            'limit' => 'required|integer|min:1|max:100',
            'sort' => 'present|array|max:2', 'sort.*' => 'array:field,direction',
            'sort.*.field' => ['required', Rule::in(array_merge($input['dimensions'] ?? [], ['value']))],
            'sort.*.direction' => ['required', Rule::in(['asc', 'desc'])],
            'visualization' => 'required|array:type', 'visualization.type' => ['required', Rule::in(AnalyticsRegistry::CHARTS)],
            'cumulative' => 'sometimes|boolean',
        ])->validate();
        if (isset($p['filters']['date_from']) && CarbonImmutable::parse($p['filters']['date_from'])->diffInDays(CarbonImmutable::parse($p['filters']['date_to'])) > 3660) {
            self::fail();
        }
        if (($p['metric'] === 'missing_fields_count') !== ($p['dimensions'] === ['quality_field'])) {
            self::fail();
        }
        if (in_array('quality_field', $p['dimensions']) && $p['metric'] !== 'missing_fields_count') {
            self::fail();
        }
        if (array_intersect($p['dimensions'], ['criterion', 'evaluator']) && $p['metric'] !== 'average_criterion_score') {
            self::fail();
        }
        if (in_array('days_to_deadline', $p['dimensions']) && str_starts_with($p['metric'], 'ai_')) {
            self::fail();
        }
        if (in_array('ai_status', $p['dimensions']) && ! str_starts_with($p['metric'], 'ai_')) {
            self::fail();
        }
        if (($p['cumulative'] ?? false) && ($p['metric'] !== 'registrations_count' || count(array_intersect($p['dimensions'], ['day', 'month', 'week', 'days_to_deadline'])) !== 1)) {
            self::fail();
        }

        $temporal = array_values(array_intersect($p['dimensions'], ['day', 'month', 'week', 'days_to_deadline']));
        if (count($temporal) > 1) {
            self::fail();
        }
        if ($temporal) {
            $p['dimensions'] = array_values(array_unique(array_merge($temporal, $p['dimensions'])));
        }

        return $p;
    }

    private static function fail(): never
    {
        throw ValidationException::withMessages(['plan' => 'Plano fora do catálogo ou dos limites permitidos.']);
    }
}
