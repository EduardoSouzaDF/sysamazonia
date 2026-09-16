@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed py-8 space-y-6">
    <div><h1 class="text-2xl font-semibold">Configurações de IA</h1><p class="text-secondary-foreground">Configuração, diagnóstico e operações do avaliador.</p></div>
    @if(session('success')) <div class="kt-alert kt-alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="kt-alert kt-alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="kt-alert kt-alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif

    <form method="POST" action="{{ route('admin.ai-settings.update') }}" class="kt-card p-6 space-y-5">
        @csrf @method('PUT')
        <h2 class="text-lg font-semibold">Configuração do LLM</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <label>Provider<select class="kt-select" name="provider">@foreach(config('ai_evaluation.providers') as $value => $label)<option value="{{ $value }}" @selected(old('provider',$settings->provider)===$value)>{{ $label }}</option>@endforeach</select></label>
            <label>Modelo<input class="kt-input" name="model" list="ai-models" maxlength="120" value="{{ old('model',$settings->model) }}" required><datalist id="ai-models">@foreach($models as $model)<option value="{{ $model }}">@endforeach</datalist><small>Informe o ID exato ou escolha uma sugestão. Salve antes de atualizar modelos.</small></label>
            <label>Base URL (Local / custom)<input class="kt-input" type="url" name="base_url" value="{{ old('base_url',$settings->baseUrl) }}" placeholder="http://127.0.0.1:11434"><small>Deve estar autorizada na allowlist do FastAPI. Custom exige HTTPS.</small></label>
            <label>Tipo local<select class="kt-select" name="local_type"><option value="ollama" @selected(old('local_type',$settings->localType)==='ollama')>Ollama (URL sem /v1)</option><option value="openai-compatible" @selected(old('local_type',$settings->localType)==='openai-compatible')>OpenAI-compatible (URL com /v1)</option></select></label>
            <label>API Key<input class="kt-input" type="password" name="api_key" autocomplete="new-password" placeholder="Deixe vazio para manter a atual"><small>{{ $keyStatus ?? 'Não configurada' }}</small></label>
            <label>Knowledge version<input class="kt-input" name="knowledge_version" value="{{ old('knowledge_version',$settings->knowledgeVersion) }}"></label>
            <label>Timeout (segundos)<input class="kt-input" type="number" name="timeout" min="5" max="60" value="{{ old('timeout',$settings->timeout) }}"></label>
            <label>Connect timeout<input class="kt-input" type="number" name="connect_timeout" min="1" max="30" value="{{ old('connect_timeout',$settings->connectTimeout) }}"></label>
            <label>Tentativas<input class="kt-input" type="number" name="tries" min="1" max="10" value="{{ old('tries',$settings->tries) }}"></label>
        </div>
        <p class="text-sm">API Key opcional para Local. Campo vazio mantém a chave somente no mesmo provider e endpoint.</p>
        @foreach([['technical_evaluator_id','Avaliador IA','evaluation_enabled','Avaliação técnica por IA',$settings->technicalEvaluatorId,$settings->evaluationEnabled],['selection_evaluator_id','Indicador IA','selection_enabled','Indicação estratégica por IA',$settings->selectionEvaluatorId,$settings->selectionEnabled]] as [$field,$label,$toggle,$title,$selected,$enabled])
        <div class="border-b border-border py-4 flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
            <label class="grow">{{ $label }}<select class="kt-select" name="{{ $field }}">@foreach($users as $user)<option value="{{ $user->id }}" @selected((int) old($field,$selected)===$user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
            <label class="flex items-center gap-2"><input class="kt-switch" type="checkbox" name="{{ $toggle }}" value="1" @checked(old($toggle,$enabled))> {{ $title }}</label>
        </div>
        @endforeach
        <div><label>Prompt técnico <small>Versão {{ $settings->technicalPromptVersion }}</small><textarea class="kt-textarea min-h-48" name="technical_prompt">{{ old('technical_prompt',$settings->technicalPrompt) }}</textarea></label></div>
        <div><label>Prompt estratégico <small>Versão {{ $settings->selectionPromptVersion }}</small><textarea class="kt-textarea min-h-48" name="selection_prompt">{{ old('selection_prompt',$settings->selectionPrompt) }}</textarea></label></div>
        <button class="kt-btn kt-btn-primary">Salvar configurações</button>
    </form>

    <div class="kt-card p-6 space-y-4"><h2 class="text-lg font-semibold">Diagnóstico</h2>
        <div class="grid md:grid-cols-4 gap-3"><div>FastAPI: <b>{{ $health['online'] ? 'Online' : 'Não confirmado' }}</b></div><div>API Key: <b>{{ $keyStatus ? 'Configurada' : 'Fallback do serviço' }}</b></div><div>Técnica: <b>{{ $settings->evaluationEnabled ? 'Ativa' : 'Inativa' }}</b></div><div>Seleção: <b>{{ $settings->selectionEnabled ? 'Ativa' : 'Inativa' }}</b></div></div>
        <p>Autenticação Laravel ↔ FastAPI: <b>{{ $health['ok'] ? 'OK' : $health['message'] }}</b></p>
        <p>Provider: use o teste de conexão para verificar acesso ao modelo salvo.</p>
        <form method="POST" action="{{ route('admin.ai-settings.models') }}">@csrf<button class="kt-btn kt-btn-light">Atualizar modelos</button></form>
        <div class="flex gap-3 flex-wrap">@foreach(['pending','processing','failed','completed'] as $status)<span class="kt-badge">{{ $status }}: {{ $counts[$status] ?? 0 }}</span>@endforeach</div>
        <form method="POST" action="{{ route('admin.ai-settings.test') }}">@csrf<button class="kt-btn kt-btn-light">Testar conexão</button></form>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        @foreach([['technical','Avaliações técnicas',$technicalPending],['selection','Seleções estratégicas',$selectionPending]] as [$type,$title,$pending])
        <div class="kt-card p-6 space-y-3"><h2 class="text-lg font-semibold">{{ $title }}</h2><p>Pendentes por status: <b>{{ $pending->count() }}</b></p>
            @if($pending->isNotEmpty())<ul class="text-sm list-disc ps-5">@foreach($pending->take(10) as $item)<li>#{{ $item->id }} — {{ $item->title }}</li>@endforeach</ul>@endif
            <form method="POST" action="{{ route('admin.ai-settings.process',$type) }}" onsubmit="return confirm('Confirma o processamento dos registros pendentes?')">@csrf
                <button name="mode" value="async" class="kt-btn kt-btn-primary">Processar em fila</button>
                <button name="mode" value="sync" class="kt-btn kt-btn-light">Processar 1 agora</button>
            </form>
        </div>
        @endforeach
    </div>

    @include('admin.ai-settings.metrics')

    <div class="kt-card p-6 space-y-3">
        <h2 class="text-lg font-semibold">Execuções recentes</h2>
        <div class="overflow-x-auto"><table class="kt-table"><thead><tr><th>ID</th><th>Inscrição</th><th>Tipo</th><th>Status</th><th>HTTP</th><th>Atualização</th></tr></thead><tbody>
        @forelse($recent as $execution)<tr><td>#{{ $execution->id }}</td><td>#{{ $execution->registration_id }}</td><td>{{ $execution->type->value }}</td><td>{{ $execution->status->value }}</td><td>{{ $execution->service_http_status ?? '—' }}</td><td>{{ $execution->updated_at?->format('d/m/Y H:i') }}</td></tr>
        @empty<tr><td colspan="6">Nenhuma execução registrada.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    body:has(#ai-progress-title) > .flex.grow { min-width: 0; width: 100%; }
    .kt-wrapper:has(#ai-progress-title), #content:has(#ai-progress-title) { min-width: 0; }
    #content:has(#ai-progress-title) .grid > *, #content:has(#ai-progress-title) .grow { min-width: 0; }
    #ai-status-chart, #ai-daily-chart { max-width: 100%; overflow: hidden; }
</style>
@endpush
