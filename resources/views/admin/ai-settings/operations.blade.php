<section class="kt-card p-6 space-y-4" aria-labelledby="ai-diagnostics-title">
    <div class="ai-heading"><div><h2 id="ai-diagnostics-title" class="text-lg font-semibold">Diagnóstico da conexão</h2><p class="text-sm text-secondary-foreground">Confira o serviço antes de iniciar uma operação.</p></div><span class="kt-badge {{ $health['ok'] ? 'kt-badge-success' : 'kt-badge-destructive' }}">{{ $health['ok'] ? 'Conexão verificada' : 'Requer atenção' }}</span></div>
    <div class="grid md:grid-cols-2 gap-4">
        <div><p class="text-sm text-secondary-foreground">Serviço de IA</p><p class="font-semibold">{{ $health['online'] ? 'Online' : 'Não confirmado' }}</p></div>
        <div><p class="text-sm text-secondary-foreground">Chave de API</p><p class="font-semibold">{{ $keyStatus ?: 'Credencial do serviço, quando necessária' }}</p></div>
    </div>
    <p class="text-sm">{{ $health['message'] }}</p>
    <div class="flex gap-3 flex-wrap">
        <form method="POST" action="{{ route('admin.ai-settings.test') }}">@csrf<button class="kt-btn kt-btn-primary">Testar acesso ao modelo</button></form>
        <a class="kt-btn kt-btn-light" href="{{ route('admin.ai-settings.index', ['tab' => 'operations']) }}">Atualizar diagnóstico</a>
    </div>
    <p class="text-sm text-secondary-foreground">O teste verifica o modelo salvo, sem gerar avaliações.</p>
</section>
<div class="grid md:grid-cols-2 gap-6">
    @foreach([['technical', 'Avaliações técnicas', $technicalPending, $settings->evaluationEnabled, 'Avalia as inscrições habilitadas conforme os critérios da categoria.'], ['selection', 'Seleção estratégica', $selectionPending, $settings->selectionEnabled, 'Analisa as inscrições avaliadas para registrar a decisão de indicação.']] as [$type, $title, $pending, $enabled, $description])
    <section class="kt-card p-6 space-y-4">
        <div class="ai-heading"><h2 class="text-lg font-semibold">{{ $title }}</h2><span class="kt-badge {{ $enabled ? 'kt-badge-success' : 'kt-badge-secondary' }}">{{ $enabled ? 'Ativa' : 'Inativa' }}</span></div>
        <p class="text-sm text-secondary-foreground">{{ $description }}</p>
        <p><strong class="text-2xl">{{ $pending->count() }}</strong> registro(s) disponível(is)</p>
        @if($pending->isNotEmpty())
            <ul class="space-y-2 text-sm">@foreach($pending->take(10) as $item)<li>#{{ $item->id }} — {{ $item->title }}</li>@endforeach</ul>
            @if($pending->count() > 10)<p class="text-sm text-secondary-foreground">Exibindo os 10 primeiros registros.</p>@endif
        @else
            <p class="text-sm text-secondary-foreground">{{ $enabled ? 'Nenhum registro disponível para esta etapa.' : 'Habilite esta etapa na Configuração do LLM.' }}</p>
        @endif
        <form method="POST" action="{{ route('admin.ai-settings.process', $type) }}" class="flex flex-wrap gap-3" onsubmit="return confirm('Confirma o envio dos registros para processamento pela IA?')">
            @csrf
            <button name="mode" value="async" class="kt-btn kt-btn-primary" @disabled($pending->isEmpty())>Processar em fila</button>
            <button name="mode" value="sync" class="kt-btn kt-btn-light" @disabled($pending->isEmpty())>Processar 1 agora</button>
        </form>
    </section>
    @endforeach
</div>
<p class="text-sm text-secondary-foreground">Acompanhe conclusões e eventuais falhas em <a class="kt-link" href="{{ route('admin.ai-settings.index', ['tab' => 'executions']) }}">Execuções recentes</a>.</p>
