<section class="dashboard-chart-section" aria-labelledby="{{ $id }}-title">
    <h3 id="{{ $id }}-title" class="text-lg font-semibold">{{ $title }}</h3>
    @if(array_sum(array_column($rows, 'total')) === 0)
        <p class="dashboard-empty">Nenhuma inscrição disponível para esta distribuição.</p>
    @else
        <div class="dashboard-chart-scroll"><div id="{{ $id }}" data-dashboard-chart="{{ $dimension }}" data-dashboard-scope="{{ $scope }}" data-chart-horizontal="{{ ($horizontal ?? true) ? 'true' : 'false' }}" aria-hidden="true"></div></div>
    @endif
    <details class="dashboard-table-details">
        <summary>Consultar dados de {{ mb_strtolower($title) }}</summary>
        <div class="overflow-x-auto"><table class="kt-table">
            <caption class="sr-only">{{ $title }} — quantidades e percentual sobre todas as inscrições desta aba</caption>
            <thead><tr><th scope="col">Grupo</th><th scope="col">Inscrições</th><th scope="col">Percentual</th></tr></thead>
            <tbody>@forelse($rows as $row)<tr><th scope="row">{{ $row['label'] }}</th><td>{{ number_format($row['total'], 0, ',', '.') }}</td><td>{{ number_format($row['percentage'], 2, ',', '.') }}%</td></tr>@empty<tr><td colspan="3">Nenhum dado disponível.</td></tr>@endforelse</tbody>
        </table></div>
    </details>
</section>
