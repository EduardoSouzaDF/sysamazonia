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
            <label>Provider<select class="kt-select" name="provider"><option value="gemini" @selected(old('provider',$settings->provider)==='gemini')>Gemini</option><option value="openai" @selected(old('provider',$settings->provider)==='openai')>OpenAI</option></select></label>
            <label>Modelo<input class="kt-input" name="model" value="{{ old('model',$settings->model) }}" required></label>
            <label>API Key<input class="kt-input" type="password" name="api_key" autocomplete="new-password" placeholder="Deixe vazio para manter a atual"><small>{{ $keyStatus ?? 'Não configurada' }}</small></label>
            <label>Knowledge version<input class="kt-input" name="knowledge_version" value="{{ old('knowledge_version',$settings->knowledgeVersion) }}"></label>
            <label>Timeout (segundos)<input class="kt-input" type="number" name="timeout" min="5" max="300" value="{{ old('timeout',$settings->timeout) }}"></label>
            <label>Connect timeout<input class="kt-input" type="number" name="connect_timeout" min="1" max="30" value="{{ old('connect_timeout',$settings->connectTimeout) }}"></label>
            <label>Tentativas<input class="kt-input" type="number" name="tries" min="1" max="10" value="{{ old('tries',$settings->tries) }}"></label>
            <label>Avaliador técnico<select class="kt-select" name="technical_evaluator_id">@foreach($users as $user)<option value="{{ $user->id }}" @selected($settings->technicalEvaluatorId===$user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
            <label>Indicador estratégico<select class="kt-select" name="selection_evaluator_id">@foreach($users as $user)<option value="{{ $user->id }}" @selected($settings->selectionEvaluatorId===$user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
        </div>
        <div class="flex gap-6"><label><input type="checkbox" name="evaluation_enabled" value="1" @checked(old('evaluation_enabled',$settings->evaluationEnabled))> Avaliação técnica ativa</label><label><input type="checkbox" name="selection_enabled" value="1" @checked(old('selection_enabled',$settings->selectionEnabled))> Seleção estratégica ativa</label></div>
        <div><label>Prompt técnico <small>Versão {{ $settings->technicalPromptVersion }}</small><textarea class="kt-textarea min-h-48" name="technical_prompt">{{ old('technical_prompt',$settings->technicalPrompt) }}</textarea></label></div>
        <div><label>Prompt estratégico <small>Versão {{ $settings->selectionPromptVersion }}</small><textarea class="kt-textarea min-h-48" name="selection_prompt">{{ old('selection_prompt',$settings->selectionPrompt) }}</textarea></label></div>
        <button class="kt-btn kt-btn-primary">Salvar configurações</button>
    </form>

    <div class="kt-card p-6 space-y-4"><h2 class="text-lg font-semibold">Diagnóstico</h2>
        <div class="grid md:grid-cols-4 gap-3"><div>FastAPI: <b>{{ $health ? 'Disponível' : 'Indisponível' }}</b></div><div>API Key: <b>{{ $keyStatus ? 'Configurada' : 'Fallback do serviço' }}</b></div><div>Técnica: <b>{{ $settings->evaluationEnabled ? 'Ativa' : 'Inativa' }}</b></div><div>Seleção: <b>{{ $settings->selectionEnabled ? 'Ativa' : 'Inativa' }}</b></div></div>
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

    <div class="kt-card p-6 space-y-3">
        <h2 class="text-lg font-semibold">Execuções recentes</h2>
        <div class="overflow-x-auto"><table class="kt-table"><thead><tr><th>ID</th><th>Inscrição</th><th>Tipo</th><th>Status</th><th>HTTP</th><th>Atualização</th></tr></thead><tbody>
        @forelse($recent as $execution)<tr><td>#{{ $execution->id }}</td><td>#{{ $execution->registration_id }}</td><td>{{ $execution->type->value }}</td><td>{{ $execution->status->value }}</td><td>{{ $execution->service_http_status ?? '—' }}</td><td>{{ $execution->updated_at?->format('d/m/Y H:i') }}</td></tr>
        @empty<tr><td colspan="6">Nenhuma execução registrada.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
