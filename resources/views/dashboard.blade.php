@extends('admin.content')

@section('maincontent')
<div id="statistics-dashboard" class="kt-container-fixed py-8 space-y-6">
    <header class="dashboard-heading">
        <div><h1 class="text-2xl font-semibold">Dashboard estatístico</h1><p class="text-secondary-foreground mt-2">Inscrições regulares e indicações honoríficas, em uma visão consolidada.</p></div>
        <span class="kt-badge kt-badge-outline">Dados agregados</span>
    </header>
    <x-elements.tabs :tabs="[
        ['titulo' => 'Estatísticas Globais', 'tabId' => 'dashboard-panel-global', 'buttonId' => 'dashboard-tab-global', 'active' => true, 'include' => 'dashboard.global', 'includeData' => ['statistics' => $statistics]],
        ['titulo' => 'Edição Atual', 'tabId' => 'dashboard-panel-current', 'buttonId' => 'dashboard-tab-current', 'active' => false, 'include' => 'dashboard.current', 'includeData' => ['statistics' => $statistics, 'mapPaths' => $mapPaths]],
    ]" />
    <p class="dashboard-footnote">Cada registro representa uma inscrição, inclusive quando o mesmo candidato participa mais de uma vez. Dados demográficos são do responsável pela inscrição. A idade é calculada na data da inscrição.</p>
    <noscript><style>#statistics-dashboard #dashboard-panel-current { display: block !important; }</style><p>JavaScript desativado: consulte os indicadores, o mapa e as tabelas de dados abaixo de cada gráfico.</p></noscript>
</div>
@endsection

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush
@push('scripts')
    <script>window.dashboardStatistics = {{ Illuminate\Support\Js::from($statistics) }};</script>
    @vite('resources/js/dashboard.js')
@endpush
