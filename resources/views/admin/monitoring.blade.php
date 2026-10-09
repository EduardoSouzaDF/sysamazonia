@extends('admin.content')

@section('maincontent')
<div id="monitoring" class="w-full px-10 space-y-6">
    <header class="monitoring-heading">
        <div><h1 class="text-xl font-medium leading-none text-mono">Monitoramento das inscrições</h1><p class="text-secondary-foreground mt-2">Monitoramento da qualidade das inscrições apresentadas.</p></div>
        <form method="GET" action="{{ route('monitoring') }}" class="monitoring-filter">
            <label for="monitoring-edition">Edição</label>
            <select id="monitoring-edition" name="edition" class="kt-select">
                @foreach ($editions as $option)
                    <option value="{{ $option->id }}" @selected($edition?->id === $option->id)>{{ $option->title }}</option>
                @endforeach
            </select>
            <button type="submit" class="kt-btn kt-btn-outline">Filtrar</button>
        </form>
    </header>
    <p class="text-sm text-secondary-foreground">Ranking por nota decrescente; empates seguem o menor número de inscrição. Os gráficos incluem todas as inscrições avaliadas da categoria, inclusive agraciadas. Rejeitadas e avaliações incompletas não participam.</p>
    <div class="kt-card">
        <div class="kt-card-header"><h2 class="kt-card-title">Panorama geral — todas as edições</h2></div>
        <div class="kt-card-content p-5">
            <p class="text-sm text-secondary-foreground mb-3">{{ array_sum($generalDistribution) }} inscrições avaliadas em todas as edições. Este gráfico independe do filtro de edição.</p>
            <p class="text-sm text-secondary-foreground mb-3">Qualidade das inscrições em todas as edições dos Prêmios: nota média das inscrições avaliadas, de 0 a 50. A linha tracejada mostra a tendência de aumento ou diminuição da qualidade (regressão linear), disponível a partir de duas edições avaliadas. Edições sem avaliações ficam sem nota.</p>
            @if ($hasQualityData)
                <div class="monitoring-line" data-monitoring-line data-editions="{{ json_encode($qualityLabels) }}" data-series="{{ json_encode($qualitySeries) }}" role="img" aria-label="Qualidade das inscrições por edição e linha de tendência"></div>
            @else
                <p class="monitoring-empty text-secondary-foreground">O gráfico estará disponível após as avaliações.</p>
            @endif
        </div>
    </div>
    @if (!$edition)
        <div class="kt-card p-6">Nenhuma edição disponível.</div>
    @else
        <h2 class="text-lg font-semibold">{{ $edition->title }}</h2>
        <div class="monitoring-charts-grid">
            @foreach ($categories as $category)
                <section class="kt-card">
                    <header class="kt-card-header"><h3 class="kt-card-title">{{ $category->acronym }} — {{ $category->title }}</h3></header>
                    <div class="kt-card-content p-5">
                        <p class="text-sm text-secondary-foreground mb-3">{{ array_sum($distributions[$category->id]) }} inscrições avaliadas · {{ $edition->title }}</p>
                        @include('admin.partials.monitoring-chart', ['distribution' => $distributions[$category->id]])
                    </div>
                </section>
            @endforeach
        </div>
        <p class="text-xs text-secondary-foreground">Faixas da nota exibida: não recomendada até 30; meritória acima de 30 até 40; recomendada acima de 40 até 50.</p>
        <p class="text-lg font-semibold">Lista de inscrições enviadas ao julgamento.</p>
        <h2 class="text-lg font-semibold">20 melhores projetos por categoria</h2>
        @forelse ($categories as $category)
            @php($distribution = $distributions[$category->id])
            <details class="kt-card kt-card-grid monitoring-category">
                <summary class="monitoring-category-title"><span>{{ $category->acronym }} — {{ $category->title }}</span></summary>
                <div class="kt-card-content">
                    <div class="monitoring-ranking">
                        <h4 class="font-semibold p-5">20 melhores inscrições</h4>
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border pages-table">
                                <caption class="sr-only">Ranking de {{ $category->title }}</caption>
                                <thead><tr><th scope="col">Posição</th><th scope="col">Título</th><th scope="col">Autoria</th><th scope="col">Estado</th><th scope="col">Nota</th><th scope="col">Visualizar</th></tr></thead>
                                <tbody>
                                @forelse ($category->registrations as $registration)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $registration->title }}</td>
                                        <td>{{ $registration->candidate?->nome ?? 'Não informado' }}
                                            @if ($registration->coautores)
                                                <span class="block text-sm text-secondary-foreground">{{ $registration->coautores }}</span>
                                            @endif
                                        </td>
                                        <td>{{ strtoupper(trim($registration->candidate?->ufendereco ?? '')) ?: 'Não informado' }}</td>
                                        @php($classification = match ($registration->getTextEvaluationAvg()) {
                                            'Recomendado' => 'recommended',
                                            'Meritório' => 'meritorious',
                                            default => 'not-recommended',
                                        })
                                        <td class="registration-status-{{ $classification }}"><strong class="registration-note registration-note-{{ $classification }}">{{ number_format($registration->getEvaluationAvgPercentage(), 0, ',', '.') }}</strong></td>
                                        <td>
                                            <button type="button" class="kt-btn kt-btn-primary" data-monitoring-view="monitoring-project-{{ $registration->id }}" aria-haspopup="dialog">Visualizar</button>
                                            <template id="monitoring-project-{{ $registration->id }}">
                                                <h2 class="monitoring-dialog-title" data-project-title>{{ $registration->title }}</h2>
                                                <dl class="monitoring-project-meta">
                                                    <div><dt>Autor</dt><dd>{{ $registration->candidate?->nome ?? 'Não informado' }}</dd></div>
                                                    @if ($registration->coautores)
                                                        <div><dt>Coautores</dt><dd>{{ $registration->coautores }}</dd></div>
                                                    @endif
                                                    <div><dt>Estado</dt><dd>{{ strtoupper(trim($registration->candidate?->ufendereco ?? '')) ?: 'Não informado' }}</dd></div>
                                                </dl>
                                                <h3 class="font-semibold mt-5">Resumo da inscrição</h3>
                                                <div class="monitoring-summary-text">{{ trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|div|li)>|<br\s*\/?\s*>/i', "\n", $registration->resumo ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: 'Resumo não informado.' }}</div>
                                                <section class="mt-5 p-4 rounded-lg registration-status-{{ $classification }}" aria-label="Nota final">
                                                    <h3 class="font-semibold">Nota final</h3>
                                                    <p class="text-xl registration-note registration-note-{{ $classification }}">{{ number_format($registration->getEvaluationAvgPercentage(), 0, ',', '.') }} / 50</p>
                                                    <p>{{ $registration->getTextEvaluationAvg() }}</p>
                                                </section>
                                                <section class="mt-5">
                                                    <h3 class="font-semibold">Avaliações ({{ $registration->opinions->count() }})</h3>
                                                    @forelse ($registration->opinions->sortByDesc('created_at') as $opinion)
                                                        <details class="mt-3 rounded-lg border p-3">
                                                            <summary class="cursor-pointer font-medium">{{ $opinion->user?->name ?? 'Avaliador não informado' }} · {{ $opinion->created_at?->format('d/m/Y') ?? 'Data não informada' }}</summary>
                                                            <div class="mt-3 space-y-3">
                                                                @forelse ($opinion->scores as $score)
                                                                    <div>
                                                                        <p class="font-semibold">{{ $score->evaluationCriterion?->name ?? 'Critério' }}: {{ (int) $score->valor }}</p>
                                                                        @if ($score->descricao)
                                                                            <p class="monitoring-summary-text"><x-julgar.plain-text :text="$score->descricao" /></p>
                                                                        @endif
                                                                    </div>
                                                                @empty
                                                                    <p>Sem notas por critério registradas.</p>
                                                                @endforelse
                                                            </div>
                                                        </details>
                                                    @empty
                                                        <p class="text-secondary-foreground mt-2">Nenhuma avaliação registrada.</p>
                                                    @endforelse
                                                </section>
                                                <section class="mt-5">
                                                    <h3 class="font-semibold">Indicações ({{ $registration->indications->count() }})</h3>
                                                    @forelse ($registration->indications->sortByDesc('created_at') as $indication)
                                                        <details class="mt-3 rounded-lg border p-3">
                                                            <summary class="cursor-pointer font-medium">{{ $indication->user?->name ?? 'Responsável não informado' }} · {{ $indication->created_at?->format('d/m/Y') ?? 'Data não informada' }}</summary>
                                                            <p class="monitoring-summary-text"><x-julgar.plain-text :text="$indication->descricao" /></p>
                                                        </details>
                                                    @empty
                                                        <p class="text-secondary-foreground mt-2">Nenhuma indicação registrada.</p>
                                                    @endforelse
                                                </section>
                                                <a href="{{ route('admin.registration.show', ['id' => $registration->id, 'type' => get_class($registration)]) }}" class="kt-btn kt-btn-outline mt-5">Ver inscrição completa</a>
                                            </template>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6">Nenhuma inscrição avaliada nesta categoria.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </details>
        @empty
            <div class="kt-card p-6">Nenhuma categoria não honorífica nesta edição.</div>
        @endforelse
    @endif
    <dialog id="monitoring-project-dialog" class="monitoring-dialog" aria-labelledby="monitoring-dialog-title">
        <div class="monitoring-dialog-header">
            <span class="text-sm text-secondary-foreground">Inscrição</span>
            <button type="button" class="kt-btn kt-btn-outline" data-monitoring-close aria-label="Fechar detalhes da inscrição">Fechar</button>
        </div>
        <div data-monitoring-dialog-content></div>
    </dialog>
</div>
@endsection

@push('styles')
    @vite(['resources/css/pages-table.css', 'resources/css/registrations.css', 'resources/css/monitoring.css'])
@endpush
@push('scripts')
    @vite('resources/js/monitoring.js')
@endpush
