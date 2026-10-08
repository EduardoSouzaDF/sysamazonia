@php
    $statusLabels = ['pending' => 'Aguardando', 'processing' => 'Em processamento', 'completed' => 'Concluída', 'failed' => 'Falha'];
    $typeLabels = ['technical_evaluation' => 'Avaliação técnica', 'strategic_selection' => 'Seleção estratégica'];
    $statusClasses = ['pending' => 'kt-badge-secondary', 'processing' => 'kt-badge-primary', 'completed' => 'kt-badge-success', 'failed' => 'kt-badge-destructive'];
@endphp
<section class="kt-card p-6 space-y-5" aria-labelledby="ai-executions-title">
    <div class="ai-heading"><div><h2 id="ai-executions-title" class="text-lg font-semibold">Execuções recentes</h2><p class="text-sm text-secondary-foreground">Histórico ordenado pela última atualização.</p></div><span class="kt-badge kt-badge-outline">{{ $recent->total() }} registro(s)</span></div>
    <form method="GET" action="{{ route('admin.ai-settings.index') }}" class="flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="executions">
        <label>Etapa<select class="kt-select" name="type"><option value="">Todas as etapas</option>@foreach($typeLabels as $value => $label)<option value="{{ $value }}" @selected($typeFilter === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Situação<select class="kt-select" name="status"><option value="">Todas as situações</option>@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>@endforeach</select></label>
        <button class="kt-btn kt-btn-primary">Filtrar</button><a class="kt-btn kt-btn-light" href="{{ route('admin.ai-settings.index', ['tab' => 'executions']) }}">Limpar filtros</a>
    </form>
    {{-- Selected IDs are posted explicitly; there is no automatic retry beyond a round's limit. --}}
    <form id="ai-reprocess-batch" method="POST" action="{{ route('admin.ai-settings.reprocess') }}" class="kt-card p-4 space-y-3" onsubmit="return confirm('Criar novas rodadas para as execuções selecionadas? Esta ação pode consumir créditos.');">
        @csrf
        <h3 class="font-semibold">Reprocessar falhas com tentativas esgotadas</h3>
        <p>Selecione até 50 execuções. Configuração atual: {{ $settings->provider }} / {{ $settings->model }}. Cada solicitação preserva o histórico e exige que não exista resultado ou processamento ativo.</p>
        <label>Configuração<select name="configuration" class="kt-select"><option value="current">Atual do painel</option><option value="previous">Anterior de cada execução</option></select></label>
        <label>Motivo<textarea class="kt-textarea" name="reason" minlength="10" maxlength="1000" required placeholder="Ex.: indisponibilidade do modelo anterior corrigida"></textarea></label>
        <label><input type="checkbox" name="confirmed" value="1" required> Confirmo os reprocessamentos selecionados e o possível consumo de créditos.</label>
        <button class="kt-btn kt-btn-primary">Reprocessar selecionadas</button>
    </form>
    <div class="overflow-x-auto"><table class="kt-table">
        <caption class="sr-only">Execuções de IA e detalhes de processamento</caption>
        <thead><tr><th scope="col">Reprocessar</th><th scope="col">Execução</th><th scope="col">Inscrição</th><th scope="col">Etapa</th><th scope="col">Situação</th><th scope="col">Atualização</th><th scope="col">Detalhes</th></tr></thead>
        <tbody>
        @forelse($recent as $execution)
            <tr>
                <td>
                    @if($execution->status->value === 'failed' && $execution->attempts >= $settings->tries && ! $execution->superseded_at)
                        <input type="checkbox" form="ai-reprocess-batch" name="execution_ids[]" value="{{ $execution->id }}" aria-label="Selecionar execução {{ $execution->id }} para reprocessar">
                    @endif
                </td>
                <td>#{{ $execution->id }}</td><td>#{{ $execution->registration_id }}</td><td>{{ $typeLabels[$execution->type->value] }}</td>
                <td><span class="kt-badge {{ $statusClasses[$execution->status->value] }}">{{ $statusLabels[$execution->status->value] }}</span></td>
                <td>{{ $execution->updated_at?->format('d/m/Y H:i') }}</td>
                <td><details class="ai-error"><summary>Ver detalhes <span class="sr-only">da execução #{{ $execution->id }}</span></summary><div class="space-y-2 mt-2 text-sm">
                    <p>Rodada: {{ $execution->retry_round }} @if($execution->parent_execution_id) · Origem: #{{ $execution->parent_execution_id }} @endif</p>
                    <p>Configuração registrada: {{ $execution->settingVersion?->provider ?? 'legada' }} / {{ $execution->settingVersion?->model ?? 'não versionada' }}</p>
                    @if($execution->requested_at)<p>Solicitada por {{ $execution->requestedBy?->name ?? 'usuário removido' }} em {{ $execution->requested_at->format('d/m/Y H:i') }}. Motivo: {{ $execution->retry_reason }}</p>@endif
                    @if($execution->superseded_at)<p>Esta rodada foi substituída por um reprocessamento; não recebe novas tentativas.</p>@endif
                    <p>Tentativas: {{ $execution->attempts }} · HTTP: {{ $execution->service_http_status ?? '—' }}</p>
                    @if($execution->error_code)<p class="font-semibold">{{ $execution->error_code }}</p><p>{{ $execution->error_message }}</p>
                    @else<p>{{ $execution->status->value === 'completed' ? 'Processamento concluído.' : 'Sem erro registrado.' }}</p>@endif
                    @if($execution->status->value === 'failed' && $execution->attempts >= $settings->tries && ! $execution->superseded_at)
                        {{-- Independent single-item form; batch checkboxes belong to another form. --}}
                        <form method="POST" action="{{ route('admin.ai-settings.reprocess') }}" class="space-y-2" onsubmit="return confirm('Reprocessar esta execução em uma nova rodada?');">
                            @csrf
                            <input type="hidden" name="execution_ids[]" value="{{ $execution->id }}">
                            <label>Configuração<select name="configuration" class="kt-select"><option value="current">Atual: {{ $settings->provider }} / {{ $settings->model }}</option><option value="previous">Anterior: {{ $execution->settingVersion?->provider }} / {{ $execution->settingVersion?->model }}</option></select></label>
                            <label>Motivo<textarea class="kt-textarea" name="reason" minlength="10" maxlength="1000" required></textarea></label>
                            <label><input type="checkbox" name="confirmed" value="1" required> Confirmo a nova rodada e o possível consumo de créditos.</label>
                            <button class="kt-btn kt-btn-primary">Reprocessar avaliação / indicação</button>
                        </form>
                    @endif
                </div></details></td>
            </tr>
        @empty<tr><td colspan="7" class="text-center py-8">Nenhuma execução encontrada para os filtros selecionados.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $recent->links() }}
</section>
