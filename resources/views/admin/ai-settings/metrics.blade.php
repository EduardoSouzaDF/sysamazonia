<section class="kt-card p-6 space-y-4" aria-labelledby="ai-progress-title">
    <h2 id="ai-progress-title" class="text-lg font-semibold">Andamento das avaliações</h2>
    <form method="GET" class="flex items-center gap-3"><label>Período<select class="kt-select" name="days">@foreach([7,30,90] as $days)<option value="{{ $days }}" @selected($metrics['days']===$days)>{{ $days }} dias</option>@endforeach</select></label><button class="kt-btn kt-btn-light">Filtrar</button></form>
    <p>Execuções criadas no período. Taxa de sucesso entre concluídas e falhas: <b>{{ $metrics['successRate'] === null ? 'Sem resultados' : $metrics['successRate'].'%' }}</b>.</p>
    <div class="grid md:grid-cols-2 gap-6">
        <div><h3>Status das execuções</h3><div id="ai-status-chart" aria-hidden="true"></div>
            <ul>@foreach(['pending'=>'Aguardando','processing'=>'Em processamento','completed'=>'Concluídas','failed'=>'Falhas'] as $status=>$label)<li>{{ $label }} ({{ $status }}): <b>{{ $metrics['counts'][$status] ?? 0 }}</b></li>@endforeach</ul>
            @if(array_sum($metrics['counts'])===0)<p>Nenhuma execução criada no período.</p>@endif
        </div>
        <div><h3>Conclusões por dia</h3><p class="text-sm">Data de conclusão em {{ config('app.timezone') }}. Inclui execuções criadas antes do período.</p><div id="ai-daily-chart" aria-hidden="true"></div>
            <details><summary>Ver números por dia (tabela acessível)</summary><div class="overflow-x-auto"><table class="kt-table"><caption>Conclusões diárias</caption><thead><tr><th>Dia</th><th>Técnica</th><th>Estratégica</th></tr></thead><tbody>@foreach($metrics['labels'] as $i=>$day)<tr><th scope="row">{{ $day }}</th><td>{{ $metrics['series']['technical_evaluation'][$i] }}</td><td>{{ $metrics['series']['strategic_selection'][$i] }}</td></tr>@endforeach</tbody></table></div></details>
        </div>
    </div>
    <p class="text-sm">Atualização em até 30 segundos. Os números e a tabela permanecem disponíveis sem JavaScript.</p>
    <script type="application/json" id="ai-metrics-data">{!! json_encode($metrics, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</section>
