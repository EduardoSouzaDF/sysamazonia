<?php

namespace App\Http\Controllers;

use App\Enum\AiExecutionType;
use App\Http\Requests\UpdateAiSettingsRequest;
use App\Models\AiExecution;
use App\Models\AiSetting;
use App\Models\User;
use App\Services\Ai\AiPendingOperations;
use App\Services\Ai\AiSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class AiSettingsController extends Controller
{
    public function index(AiSettingsService $settings, AiPendingOperations $operations): View
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
            'health' => $this->health($resolved->connectTimeout),
        ]);
    }

    public function update(UpdateAiSettingsRequest $request, AiSettingsService $settings): RedirectResponse
    {
        $settings->update($request->validated(), $request->user());

        return back()->with('success', 'Configurações de IA atualizadas com segurança.');
    }

    public function testConnection(AiSettingsService $settings): RedirectResponse
    {
        $s = $settings->current();
        try {
            $response = Http::baseUrl((string) config('ai_evaluation.service_url'))->withToken((string) config('ai_evaluation.token'))
                ->connectTimeout($s->connectTimeout)->timeout(min($s->timeout, 20))->acceptJson()->post('/v1/configuration/test', [
                    'provider' => $s->provider, 'model' => $s->model, 'api_key' => $s->apiKey,
                    'prompt_version' => $s->technicalPromptVersion, 'knowledge_version' => $s->knowledgeVersion,
                    'timeout' => min(55, $s->timeout),
                ]);

            return $response->successful() ? back()->with('success', 'FastAPI e configuração do provider estão prontos.') : back()->with('error', 'FastAPI respondeu, mas o provider não está pronto.');
        } catch (\Throwable) {
            return back()->with('error', 'Não foi possível conectar ao serviço de IA.');
        }
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

    private function health(int $timeout): bool
    {
        try {
            return Http::baseUrl((string) config('ai_evaluation.service_url'))->connectTimeout($timeout)->timeout($timeout)->get('/health')->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
