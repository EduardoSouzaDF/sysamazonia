<div class="space-y-6 pt-6">
    <div class="dashboard-heading"><h2 class="text-xl font-semibold">Estatísticas Globais</h2><p class="dashboard-footnote">Todas as edições · Atualizado em {{ $statistics['global']['generatedAt'] }}</p></div>
    @include('dashboard.indicators', ['data' => $statistics['global']])
    @if($statistics['global']['total'] === 0)<p class="dashboard-empty" role="status">Ainda não há inscrições registradas no sistema.</p>@endif
    <div class="dashboard-chart-grid">
        @foreach(['editions' => 'Inscrições por edição', 'ages' => 'Faixa etária', 'states' => 'Estado de residência', 'modalities' => 'Modalidade', 'categories' => 'Categoria'] as $dimension => $title)
            @include('dashboard.chart', ['id' => 'global-'.$dimension, 'scope' => 'global', 'dimension' => $dimension, 'title' => $title, 'rows' => $statistics['global'][$dimension], 'horizontal' => $dimension !== 'editions'])
        @endforeach
    </div>
    <p class="dashboard-footnote">{{ $statistics['cacheTtl'] ? 'Estatísticas globais em cache por até '.$statistics['cacheTtl'].' segundos.' : 'Estatísticas globais sem cache.' }} Recarregue a página para atualizar. Modalidades e categorias são identificadas pela edição para distinguir títulos iguais.</p>
</div>
