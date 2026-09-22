<section class="space-y-6" aria-labelledby="ai-progress-title">
    <div class="ai-heading"><div><h2 id="ai-progress-title" class="text-xl font-semibold">Andamento das avaliações</h2><p class="text-sm text-secondary-foreground">Visão do fluxo do prêmio, combinando trabalho humano e IA.</p></div>
        <form method="GET" class="flex items-center gap-3"><input type="hidden" name="tab" value="progress"><label>Período<select class="kt-select" name="days">@foreach([7,30,90] as $days)<option value="{{ $days }}" @selected($metrics['days']===$days)>{{ $days }} dias</option>@endforeach</select></label><button class="kt-btn kt-btn-light">Filtrar</button></form>
    </div>
    <div class="grid md:grid-cols-4 gap-4">
        <div class="kt-card ai-stat" data-status="completed"><span class="text-sm text-secondary-foreground">Avaliações concluídas</span><strong>{{ $workflow['technical']['completed'] }}</strong></div>
        <div class="kt-card ai-stat"><span class="text-sm text-secondary-foreground">Pendentes humanas</span><strong>{{ $workflow['technical']['awaiting_human'] }}</strong></div>
        <div class="kt-card ai-stat"><span class="text-sm text-secondary-foreground">Pendentes IA</span><strong>{{ $workflow['technical']['awaiting_ai'] }}</strong></div>
        <a class="kt-card ai-stat" data-status="failed" href="{{ route('admin.ai-settings.index', ['tab' => 'problems']) }}"><span class="text-sm text-secondary-foreground">Problemas</span><strong>{{ $workflow['problems']['total'] }}</strong></a>
    </div>
    <div class="grid md:grid-cols-3 gap-4">
        <div class="kt-card p-5"><span class="text-sm text-secondary-foreground">Indicações concluídas</span><strong class="block text-2xl mt-2">{{ $workflow['selection']['completed'] }}</strong></div>
        <div class="kt-card p-5"><span class="text-sm text-secondary-foreground">Indicadas</span><strong class="block text-2xl mt-2">{{ $workflow['selection']['indicated'] }}</strong></div>
        <div class="kt-card p-5"><span class="text-sm text-secondary-foreground">Não indicadas</span><strong class="block text-2xl mt-2">{{ $workflow['selection']['not_indicated'] }}</strong></div>
    </div>
    <section class="kt-card p-6 space-y-4"><h3 class="font-semibold">Fluxo por categoria</h3><div class="overflow-x-auto"><table class="kt-table"><thead><tr><th>Categoria</th><th>Habilitadas</th><th>Avaliadas</th><th>Pendente humano</th><th>Pendente IA</th><th>Indicação pendente</th></tr></thead><tbody>
        @forelse($workflow['categories'] as $row)<tr><td>{{ $row['category'] }}</td><td>{{ $row['enabled'] }}</td><td>{{ $row['evaluated'] }}</td><td>{{ $row['awaiting_human'] }}</td><td>{{ $row['awaiting_ai'] }}</td><td>{{ $row['selection_pending'] }}</td></tr>
        @empty<tr><td colspan="6">Nenhuma inscrição em avaliação.</td></tr>@endforelse
    </tbody></table></div></section>
    <div class="grid md:grid-cols-2 gap-6">
        <section class="kt-card p-6"><h3 class="font-semibold">Situação das execuções IA</h3><p class="text-sm text-secondary-foreground">Taxa de sucesso no período: <b>{{ $metrics['successRate'] === null ? 'Sem resultados' : $metrics['successRate'].'%' }}</b></p><div id="ai-status-chart" aria-hidden="true"></div><ul>@foreach(['pending'=>'Aguardando','processing'=>'Em processamento','completed'=>'Concluídas','failed'=>'Falhas'] as $status=>$label)<li>{{ $label }}: <b>{{ $metrics['counts'][$status] ?? 0 }}</b></li>@endforeach</ul></section>
        <section class="kt-card p-6"><h3 class="font-semibold">Conclusões por dia</h3><div id="ai-daily-chart" aria-hidden="true"></div><details><summary>Ver tabela</summary><div class="overflow-x-auto"><table class="kt-table"><thead><tr><th>Dia</th><th>Técnica</th><th>Estratégica</th></tr></thead><tbody>@foreach($metrics['labels'] as $i=>$day)<tr><td>{{ $day }}</td><td>{{ $metrics['series']['technical_evaluation'][$i] }}</td><td>{{ $metrics['series']['strategic_selection'][$i] }}</td></tr>@endforeach</tbody></table></div></details></section>
    </div>
    <script type="application/json" id="ai-metrics-data">{!! json_encode($metrics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</section>
