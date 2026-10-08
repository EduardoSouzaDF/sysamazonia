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
use App\Services\Ai\AiWorkflowDashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function index(Request $request, AiSettingsService $settings, AiPendingOperations $operations, AiServiceDiagnostics $diagnostics, AiDashboardMetrics $metrics, AiWorkflowDashboard $workflow): View
    {
        $resolved = $settings->current();
        $model = AiSetting::query()->first();
        $tab = $request->query('tab', 'progress');
        if (! in_array($tab, ['progress', 'evaluations', 'indications', 'problems', 'configuration', 'executions'], true)) {
            $tab = 'progress';
        }
        if ($request->session()->get('errors')?->any()) {
            // Os erros do reenvio permanecem na Auditoria; os de Settings abrem Configuração.
            $tab = $request->session()->get('errors')?->has('reprocessing') || $request->query('tab') === 'executions' ? 'executions' : 'configuration';
        }
        $statusFilter = in_array($request->query('status'), ['pending', 'processing', 'completed', 'failed'], true) ? $request->query('status') : '';
        $typeFilter = in_array($request->query('type'), ['technical_evaluation', 'strategic_selection'], true) ? $request->query('type') : '';
        $recent = $tab === 'executions' ? AiExecution::query()->with(['settingVersion', 'requestedBy'])
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->when($typeFilter, fn ($query) => $query->where('type', $typeFilter))
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(15)->withQueryString() : null;

        return view('admin.ai-settings.index', [
            'settings' => $resolved, 'keyStatus' => $settings->maskedKey($model),
            'users' => $tab === 'configuration' ? User::query()->orderBy('name')->get(['id', 'name', 'email']) : collect(),
            'tab' => $tab, 'recent' => $recent, 'statusFilter' => $statusFilter, 'typeFilter' => $typeFilter,
            'technicalPending' => $tab === 'evaluations' ? $operations->candidates(AiExecutionType::TechnicalEvaluation) : collect(),
            'selectionPending' => $tab === 'indications' ? $operations->candidates(AiExecutionType::StrategicSelection) : collect(),
            'health' => $tab === 'problems' ? $diagnostics->check($resolved) : null,
            'metrics' => $tab === 'progress' ? $metrics->forDays((int) $request->input('days', 30)) : null,
            'workflow' => in_array($tab, ['progress', 'evaluations', 'indications', 'problems'], true) ? $workflow->summary($resolved) : null,
            'recentProblems' => $tab === 'problems' ? AiExecution::query()->whereIn('status', ['failed', 'processing'])->latest('updated_at')->limit(20)->get() : collect(),
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

        return to_route('admin.ai-settings.index', ['tab' => 'problems'])->with($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Provedor conectado; modelo acessível. Teste de metadados sem inferência.' : $result['message']);
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

        $redirect = to_route('admin.ai-settings.index', ['tab' => $type === 'technical' ? 'evaluations' : 'indications']);
        if ($result['started'] === 0) {
            return $redirect->with('success', 'Nenhum registro disponível para processamento.');
        }
        if (! $synchronous) {
            return $redirect->with('success', "{$result['started']} registro(s) encaminhado(s) à fila. Acompanhe em Execuções recentes.");
        }
        if ($result['completed'] < $result['started']) {
            return $redirect->with('error', 'A avaliação não foi concluída. Consulte o status e o motivo em Execuções recentes.');
        }

        return $redirect->with('success', "{$result['completed']} avaliação(ões) concluída(s).");
    }
}
