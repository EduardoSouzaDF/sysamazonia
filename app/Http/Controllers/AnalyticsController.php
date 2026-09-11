<?php

namespace App\Http\Controllers;

use App\Services\Analytics\AnalyticsPlan;
use App\Services\Analytics\AnalyticsPlanner;
use App\Services\Analytics\AnalyticsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AnalyticsController extends Controller
{
    public function index()
    {
        return view('admin.analytics.index', ['analyticsContext' => session('analytics.context')]);
    }

    public function clear(Request $request)
    {
        $request->session()->forget('analytics.context');

        return response()->json(['ok' => true]);
    }

    public function query(Request $request, AnalyticsQuery $query, AnalyticsPlanner $planner)
    {
        $input = $request->validate(['question' => 'required_without:plan|string|max:1000', 'plan' => 'required_without:question|array', 'remove_filter' => 'nullable|string']);
        $start = microtime(true);
        $success = false;
        $errorType = null;
        try {
            $context = $request->session()->get('analytics.context');
            if (isset($input['remove_filter']) && $context) {
                unset($context['filters'][$input['remove_filter']]);
            }
            $plan = isset($input['plan']) ? AnalyticsPlan::validate($input['plan']) : $planner->interpret($input['question'], $context);
            if ($plan['intent'] !== 'analytics') {
                return response()->json(['answer' => $plan['question'], 'intent' => $plan['intent']]);
            }
            $key = 'analytics.v1.'.$request->user()->id.'.'.hash('sha256', json_encode($plan));
            $result = Cache::remember($key, 30, fn () => $query->execute($plan));
            $request->session()->put('analytics.context', $plan);
            $success = true;

            return response()->json($result);
        } catch (ValidationException $e) {
            $errorType = 'INVALID_PLAN';

            return response()->json(['message' => 'Plano inválido ou consulta ampla demais. Refine os filtros.', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            $errorType = match (true) {
                $e instanceof \Illuminate\Http\Client\ConnectionException => str_contains($e->getMessage(), '28') ? 'AI_TIMEOUT' : 'AI_OFFLINE',
                $e instanceof \Illuminate\Database\QueryException => 'QUERY_FAILED',
                in_array($e->getMessage(), ['AI_CONFIGURATION_ERROR', 'AI_RATE_LIMIT', 'AI_UNAVAILABLE']) => $e->getMessage(),
                default => 'ANALYTICS_UNAVAILABLE',
            };

            return response()->json(['message' => 'Não foi possível concluir a análise. Verifique a conexão e tente novamente.', 'code' => $errorType], $errorType === 'AI_RATE_LIMIT' ? 429 : 503);
        } finally {
            // Deliberately omit question, filters, provider responses and exception messages.
            Log::info('analytics.query', ['user_id' => $request->user()->id, 'metric' => $plan['metric'] ?? null, 'success' => $success, 'error_type' => $errorType, 'duration_ms' => (int) ((microtime(true) - $start) * 1000)]);
        }
    }
}
