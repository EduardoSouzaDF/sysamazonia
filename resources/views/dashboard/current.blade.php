<div class="space-y-6 pt-6">
    @if(!$statistics['edition'])
        <h2 class="text-xl font-semibold">Edição Atual</h2><p class="dashboard-empty">Nenhuma edição disponível. Os dados aparecerão após o cadastro de uma edição.</p>
    @else
        <div class="dashboard-heading"><div><h2 class="text-xl font-semibold">{{ $statistics['edition']['title'] }}</h2>
            @if($statistics['fallback'])<p class="dashboard-footnote">Nenhuma edição está com inscrições ativas. Exibindo a edição mais recente pela data de início das inscrições.</p>@endif
        </div><p class="dashboard-footnote">Atualizado em {{ $statistics['current']['generatedAt'] }}</p></div>
        @include('dashboard.indicators', ['data' => $statistics['current']])
        @if($statistics['current']['total'] === 0)<p class="dashboard-empty" role="status">Esta edição ainda não possui inscrições.</p>@endif
        <section aria-labelledby="edition-summary-title">
            <h3 id="edition-summary-title" class="text-lg font-semibold">Resumo por categoria</h3>
            <div class="overflow-x-auto"><table class="kt-table">
                <caption class="sr-only">Participação de cada categoria no total da edição</caption>
                <thead><tr><th scope="col">Categoria · Modalidade</th><th scope="col">Inscrições</th><th scope="col">Percentual da edição</th></tr></thead>
                <tbody>@forelse($statistics['current']['categories'] as $row)<tr><th scope="row">{{ $row['label'] }}</th><td>{{ number_format($row['total'], 0, ',', '.') }}</td><td>{{ number_format($row['percentage'], 2, ',', '.') }}%</td></tr>@empty<tr><td colspan="3">Nenhuma categoria disponível.</td></tr>@endforelse</tbody>
                <tfoot><tr><th scope="row">Total da edição</th><td>{{ number_format($statistics['current']['total'], 0, ',', '.') }}</td><td>{{ $statistics['current']['total'] ? '100,00%' : '0,00%' }}</td></tr></tfoot>
            </table></div>
        </section>
        @include('dashboard.map')
        <div class="dashboard-chart-grid">
            @foreach(['modalities' => 'Modalidade', 'categories' => 'Categoria', 'sex' => 'Sexo', 'ages' => 'Faixa etária', 'education' => 'Escolaridade'] as $dimension => $title)
                @include('dashboard.chart', ['id' => 'current-'.$dimension, 'scope' => 'current', 'dimension' => $dimension, 'title' => $title, 'rows' => $statistics['current'][$dimension], 'horizontal' => true])
            @endforeach
        </div>
    @endif
</div>
