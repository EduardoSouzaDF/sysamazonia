    <form method="POST" action="{{ route('admin.ai-settings.update') }}" class="kt-card p-6 space-y-5 ai-settings-form" id="ai-settings-form">
        @csrf @method('PUT')
        <div><h2 class="text-lg font-semibold">Provedor e modelo</h2><p class="text-sm text-secondary-foreground">Configure a conexão e os responsáveis por cada etapa.</p></div>
        <div class="grid md:grid-cols-2 gap-4">
            <label>Provedor<select class="kt-select" name="provider">@foreach(config('ai_evaluation.providers') as $value => $label)<option value="{{ $value }}" @selected(old('provider',$settings->provider)===$value)>{{ $label }}</option>@endforeach</select></label>
            <label>Modelo<input class="kt-input" name="model" list="ai-models" maxlength="120" value="{{ old('model',$settings->model) }}" required><datalist id="ai-models">@foreach($models as $model)<option value="{{ $model }}">@endforeach</datalist><small>Informe o ID exato ou escolha uma sugestão. Salve antes de atualizar modelos.</small></label>
            <label data-ai-endpoint>URL do serviço (local / personalizado)<input class="kt-input" type="url" name="base_url" value="{{ old('base_url',$settings->baseUrl) }}" placeholder="http://127.0.0.1:11434"><small>O endereço deve estar autorizado no serviço de IA. Provedores personalizados exigem HTTPS.</small></label>
            <label data-ai-local>Tipo local<select class="kt-select" name="local_type"><option value="ollama" @selected(old('local_type',$settings->localType)==='ollama')>Ollama (URL sem /v1)</option><option value="openai-compatible" @selected(old('local_type',$settings->localType)==='openai-compatible')>OpenAI-compatible (URL com /v1)</option></select></label>
            <label>Chave de API<input class="kt-input" type="password" name="api_key" autocomplete="new-password" placeholder="Deixe vazio para manter a atual"><small>{{ $keyStatus ?? 'Não configurada' }}</small></label>
        </div>
        <p class="text-sm">Chave de API opcional para Local. Campo vazio mantém a chave somente no mesmo provedor e endereço.</p>
        <h2 class="text-lg font-semibold">Responsáveis e etapas</h2>
        @foreach([['technical_evaluator_id','Avaliador IA','evaluation_enabled','Avaliação técnica por IA',$settings->technicalEvaluatorId,$settings->evaluationEnabled],['selection_evaluator_id','Indicador IA','selection_enabled','Indicação estratégica por IA',$settings->selectionEvaluatorId,$settings->selectionEnabled]] as [$field,$label,$toggle,$title,$selected,$enabled])
        <div class="border-b border-border py-4 flex flex-col md:flex-row gap-4 md:items-center md:justify-between">
            <label class="grow">{{ $label }}<select class="kt-select" name="{{ $field }}">@foreach($users as $user)<option value="{{ $user->id }}" @selected((int) old($field,$selected)===$user->id)>{{ $user->name }} ({{ $user->email }})</option>@endforeach</select></label>
            <label class="flex items-center gap-2"><input type="hidden" name="{{ $toggle }}" value="0"><input class="kt-switch" type="checkbox" name="{{ $toggle }}" value="1" @checked(old($toggle,$enabled))> {{ $title }}</label>
        </div>
        @endforeach
        <details class="ai-prompt" @if($errors->hasAny(['knowledge_version', 'timeout', 'connect_timeout', 'tries'])) open @endif>
            <summary>Opções avançadas</summary>
            <div class="grid md:grid-cols-2 gap-4 mt-4">
            <label>Versão da base de conhecimento<input class="kt-input" name="knowledge_version" value="{{ old('knowledge_version',$settings->knowledgeVersion) }}"></label>
            <label>Timeout (segundos)<input class="kt-input" type="number" name="timeout" min="5" max="60" value="{{ old('timeout',$settings->timeout) }}"></label>
            <label>Tempo de conexão (segundos)<input class="kt-input" type="number" name="connect_timeout" min="1" max="30" value="{{ old('connect_timeout',$settings->connectTimeout) }}"></label>
            <label>Tentativas<input class="kt-input" type="number" name="tries" min="1" max="10" value="{{ old('tries',$settings->tries) }}"></label>
            </div>
        </details>
        <details class="ai-prompt" @if($errors->has('technical_prompt')) open @endif>
            <summary>Prompt técnico <span class="kt-badge kt-badge-outline">Versão {{ $settings->technicalPromptVersion }}</span></summary>
            <label class="block mt-4">Instruções do avaliador<textarea class="kt-textarea min-h-48" name="technical_prompt">{{ old('technical_prompt',$settings->technicalPrompt) }}</textarea></label>
        </details>
        <details class="ai-prompt" @if($errors->has('selection_prompt')) open @endif>
            <summary>Prompt estratégico <span class="kt-badge kt-badge-outline">Versão {{ $settings->selectionPromptVersion }}</span></summary>
            <label class="block mt-4">Instruções do avaliador<textarea class="kt-textarea min-h-48" name="selection_prompt">{{ old('selection_prompt',$settings->selectionPrompt) }}</textarea></label>
        </details>
        <div class="ai-save-bar"><span class="text-sm text-secondary-foreground">As alterações serão aplicadas após salvar.</span><button class="kt-btn kt-btn-primary">Salvar configurações</button></div>
    </form>

    <form method="POST" action="{{ route('admin.ai-settings.models') }}" class="flex flex-wrap items-center gap-3">@csrf<button class="kt-btn kt-btn-light">Atualizar lista de modelos</button><span class="text-sm text-secondary-foreground">Utiliza o provedor e a credencial salvos.</span></form>
