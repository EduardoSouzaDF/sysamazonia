<?php

namespace App\Services\Ai;

use App\Enum\AiExecutionType;
use App\Models\AiExecution;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AiDashboardMetrics
{
    public function forDays(int $days): array
    {
        $days = in_array($days, [7, 30, 90], true) ? $days : 30;
        // Eloquent stores naive SQL timestamps in the application's timezone.
        $end = Carbon::now(config('app.timezone'))->endOfDay();
        $start = $end->copy()->startOfDay()->subDays($days - 1);

        return Cache::remember('ai.dashboard.'.$days.'.'.$start->toDateString(), 30, function () use ($start, $end, $days) {
            $counts = AiExecution::query()->whereBetween('created_at', [$start, $end])
                ->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status')->all();
            $daily = AiExecution::query()->where('status', 'completed')->whereBetween('completed_at', [$start, $end])
                ->selectRaw('DATE(completed_at) AS day, type, COUNT(*) AS total')->groupByRaw('DATE(completed_at), type')->get();
            $labels = [];
            $series = [];
            foreach (AiExecutionType::cases() as $type) {
                $series[$type->value] = array_fill(0, $days, 0);
            }
            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $labels[] = $day->toDateString();
            }
            foreach ($daily as $row) {
                $index = array_search($row->day, $labels, true);
                if ($index !== false) {
                    $series[$row->type->value][$index] = (int) $row->total;
                }
            }
            $finished = ($counts['completed'] ?? 0) + ($counts['failed'] ?? 0);

            return ['days' => $days, 'counts' => $counts, 'labels' => $labels, 'series' => $series,
                'successRate' => $finished ? round(100 * ($counts['completed'] ?? 0) / $finished, 1) : null];
        });
    }
}
