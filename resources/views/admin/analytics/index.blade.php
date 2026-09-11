@extends('admin.content')
@section('maincontent')
<div class="container-fixed" id="analytics-chat" data-query="{{ route('admin.analytics.query') }}" data-clear="{{ route('admin.analytics.clear') }}" data-token="{{ csrf_token() }}">
    <script id="analytics-context" type="application/json">@json($analyticsContext)</script>
    <h1 class="text-xl font-semibold mb-4">Dashboard Analítico / Assistente de Dados</h1>
    <p>Dados agregados das inscrições. Escolha uma pergunta ou descreva sua análise.</p>
    <div class="flex flex-wrap gap-2 my-4" id="analytics-examples">
        @foreach(['Mostre inscrições por estado.', 'Quais categorias receberam mais inscrições?', 'Mostre inscrições por faixa etária.', 'Qual o perfil de escolaridade dos inscritos?', 'Quantas inscrições foram avaliadas?', 'Quantas inscrições foram indicadas?', 'Qual estado tem maior taxa de indicação?', 'Existem datas de nascimento inválidas?'] as $example)
        <button type="button" class="kt-btn kt-btn-outline">{{ $example }}</button>
        @endforeach
    </div>
    <div id="analytics-filters" class="flex flex-wrap gap-2 mb-4" aria-label="Filtros ativos"></div>
    <div id="analytics-history" role="log" aria-label="Histórico da conversa" aria-live="polite"></div>
    <form id="analytics-form" class="kt-card p-4 my-4">
        <label for="analytics-question">Sua pergunta</label>
        <input id="analytics-question" class="kt-input my-2" maxlength="1000" required autocomplete="off" placeholder="Quantas inscrições tivemos em 2026?">
        <div class="flex gap-2"><button class="kt-btn kt-btn-primary" type="submit">Analisar</button><button class="kt-btn kt-btn-outline" type="button" id="analytics-clear">Limpar conversa</button></div>
        <p id="analytics-status" role="status"></p>
    </form>
</div>
@endsection
