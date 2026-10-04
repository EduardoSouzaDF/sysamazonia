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
    <div class="overflow-x-auto"><table class="kt-table">
        <caption class="sr-only">Execuções de IA e detalhes de processamento</caption>
        <thead><tr><th scope="col">Execução</th><th scope="col">Inscrição</th><th scope="col">Etapa</th><th scope="col">Situação</th><th scope="col">Atualização</th><th scope="col">Detalhes</th></tr></thead>
        <tbody>
        @forelse($recent as $execution)
            <tr>
                <td>#{{ $execution->id }}</td><td>#{{ $execution->registration_id }}</td><td>{{ $typeLabels[$execution->type->value] }}</td>
                <td><span class="kt-badge {{ $statusClasses[$execution->status->value] }}">{{ $statusLabels[$execution->status->value] }}</span></td>
                <td>{{ $execution->updated_at?->format('d/m/Y H:i') }}</td>
                <td><details class="ai-error"><summary>Ver detalhes <span class="sr-only">da execução #{{ $execution->id }}</span></summary><div class="space-y-2 mt-2 text-sm">
                    <p>Tentativas: {{ $execution->attempts }} · HTTP: {{ $execution->service_http_status ?? '—' }}</p>
                    @if($execution->error_code)<p class="font-semibold">{{ $execution->error_code }}</p><p>{{ $execution->error_message }}</p>
                    @else<p>{{ $execution->status->value === 'completed' ? 'Processamento concluído.' : 'Sem erro registrado.' }}</p>@endif
                </div></details></td>
            </tr>
        @empty<tr><td colspan="6" class="text-center py-8">Nenhuma execução encontrada para os filtros selecionados.</td></tr>@endforelse
        </tbody>
    </table></div>
    {{ $recent->links() }}
</section>
