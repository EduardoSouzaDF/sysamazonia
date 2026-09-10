<?php

namespace App\Http\Controllers;

use App\Enum\AiExecutionType;
use App\Http\Requests\UpdateAiSettingsRequest;
use App\Models\AiExecution;
use App\Models\AiSetting;
use App\Models\User;
use App\Services\Ai\AiDashboardMetrics;
use App\Services\Ai\AiPendingOperations;
use App\Services\Ai\AiServiceDiagnostics;
use App\Services\Ai\AiSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function index(Request $request, AiSettingsService $settings, AiPendingOperations $operations, AiServiceDiagnostics $diagnostics, AiDashboardMetrics $metrics): View
    {
        $resolved = $settings->current();
        $model = AiSetting::query()->first();
        $counts = AiExecution::query()->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status');

        return view('admin.ai-settings.index', [
            'settings' => $resolved, 'keyStatus' => $settings->maskedKey($model),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            'counts' => $counts, 'recent' => AiExecution::query()->latest()->limit(10)->get(),
            'technicalPending' => $operations->candidates(AiExecutionType::TechnicalEvaluation),
            'selectionPending' => $operations->candidates(AiExecutionType::StrategicSelection),
            'health' => $diagnostics->check($resolved),
            'metrics' => $metrics->forDays((int) $request->input('days', 30)),
            'models' => Cache::get('ai.models.'.($resolved->versionId ?? 'fallback'), []),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request, AiSettingsService $settings): RedirectResponse
    {
        $settings->update($request->validated(), $request->user());

        return back()->with('success', 'Configurações de IA atualizadas com segurança.');
    }

    public function testConnection(AiSettingsService $settings, AiServiceDiagnostics $diagnostics): RedirectResponse
    {
        $result = $diagnostics->check($settings->current(), 'test');

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Provider conectado; modelo acessível. Teste de metadados sem inferência.' : $result['message']);
    }

    public function refreshModels(AiSettingsService $settings, AiServiceDiagnostics $diagnostics): RedirectResponse
    {
        $resolved = $settings->current();
        $key = 'ai.models.'.($resolved->versionId ?? 'fallback');
        if (Cache::has($key)) {
            return back()->with('success', 'Lista de modelos em cache (5 minutos). Modelo manual continua disponível.');
        }
        $result = $diagnostics->check($resolved, 'models');
        if ($result['ok']) {
            Cache::put($key, $result['models'], 300);
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Modelos atualizados. Se necessário, informe outro modelo manualmente.' : $result['message']);
    }

    public function process(Request $request, string $type, AiPendingOperations $operations): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $validated = $request->validate(['mode' => ['required', 'in:sync,async']]);
        $executionType = $type === 'technical' ? AiExecutionType::TechnicalEvaluation : AiExecutionType::StrategicSelection;
        $synchronous = $validated['mode'] === 'sync';
        $result = $operations->start($executionType, $synchronous, $synchronous ? 1 : null);

        return back()->with('success', "Operação iniciada: {$result['started']} registro(s); {$result['completed']} concluído(s) sincronamente.");
    }
}
