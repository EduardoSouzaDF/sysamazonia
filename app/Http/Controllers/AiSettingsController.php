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
        $tab = $request->query('tab', 'progress');
        if (! in_array($tab, ['progress', 'configuration', 'operations', 'executions'], true)) {
            $tab = 'progress';
        }
        if ($request->session()->get('errors')?->any()) {
            $tab = 'configuration';
        }
        $statusFilter = in_array($request->query('status'), ['pending', 'processing', 'completed', 'failed'], true) ? $request->query('status') : '';
        $typeFilter = in_array($request->query('type'), ['technical_evaluation', 'strategic_selection'], true) ? $request->query('type') : '';
        $recent = $tab === 'executions' ? AiExecution::query()
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->when($typeFilter, fn ($query) => $query->where('type', $typeFilter))
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)->withQueryString() : null;

        return view('admin.ai-settings.index', [
            'settings' => $resolved, 'keyStatus' => $settings->maskedKey($model),
            'users' => $tab === 'configuration' ? User::query()->orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'tab' => $tab, 'recent' => $recent, 'statusFilter' => $statusFilter, 'typeFilter' => $typeFilter,
            'technicalPending' => $tab === 'operations' ? $operations->candidates(AiExecutionType::TechnicalEvaluation) : collect(),
            'selectionPending' => $tab === 'operations' ? $operations->candidates(AiExecutionType::StrategicSelection) : collect(),
            'health' => $tab === 'operations' ? $diagnostics->check($resolved) : null,
            'metrics' => $tab === 'progress' ? $metrics->forDays((int) $request->input('days', 30)) : null,
            'models' => Cache::get('ai.models.'.($resolved->versionId ?? 'fallback'), []),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request, AiSettingsService $settings): RedirectResponse
    {
        $settings->update($request->validated(), $request->user());

        return to_route('admin.ai-settings.index', ['tab' => 'configuration'])->with('success', 'Configurações de IA atualizadas com segurança.');
    }

    public function testConnection(AiSettingsService $settings, AiServiceDiagnostics $diagnostics): RedirectResponse
    {
        $result = $diagnostics->check($settings->current(), 'test');

        return to_route('admin.ai-settings.index', ['tab' => 'operations'])->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Provedor conectado; modelo acessível. Teste de metadados sem inferência.' : $result['message']);
    }

    public function refreshModels(AiSettingsService $settings, AiServiceDiagnostics $diagnostics): RedirectResponse
    {
        $resolved = $settings->current();
        $key = 'ai.models.'.($resolved->versionId ?? 'fallback');
        if (Cache::has($key)) {
            return to_route('admin.ai-settings.index', ['tab' => 'configuration'])->with('success', 'Lista de modelos em cache (5 minutos). Modelo manual continua disponível.');
        }
        $result = $diagnostics->check($resolved, 'models');
        if ($result['ok']) {
            Cache::put($key, $result['models'], 300);
        }

        return to_route('admin.ai-settings.index', ['tab' => 'configuration'])->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Modelos atualizados. Se necessário, informe outro modelo manualmente.' : $result['message']);
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
