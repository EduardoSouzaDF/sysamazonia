@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed" data-julgar data-judge-id="{{ auth()->id() }}"
    @if ($category)
        data-category-id="{{ $category->id }}"
        data-effective-quota="{{ $effectiveQuota }}"
        data-selected-ids="{{ json_encode($selectedIds) }}"
    @endif
>
    @if ($category || ! $summary)
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Iniciar julgamento</h1>
            @if ($category)
                <div class="flex items-center gap-2 font-medium">
                    <span class="text-sm text-secondary-foreground">
                        Edição: {{ $category->modality?->edition?->title }}
                    </span>
                </div>
            @endif
        </div>
    </div>
    @endif

    @if (session('success'))
        <x-messages.alert :message="session('success')" type="success" />
    @endif

    @if ($category)
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card min-w-full">
                <div class="kt-card-header min-h-16 w-full flex flex-row items-center justify-between gap-4">
                    <div class="flex flex-col gap-2 py-3">
                        <h2 class="judging-current-category">Categoria: {{ $category->title }} ({{ $category->acronym }})</h2>
                        <div class="text-sm leading-relaxed">
                            <p class="font-medium">Descrição:</p>
                            <div class="judging-category-description">{!! $category->description !!}</div>
                        </div>
                        <h3 class="kt-card-title">
                            {{ $category->is_honorific ? 'Indicação para esta categoria' : 'Iniciativas selecionadas para esta categoria' }}
                        </h3>
                    </div>
                    <p class="judging-selection-count text-muted-foreground">
                        Selecionados: <span data-selected-count class="font-bold text-mono">0</span>
                        de {{ $effectiveQuota }}
                        · Restam: <span data-remaining class="font-medium text-mono">{{ $remainingQuota }}</span>
                    </p>
                </div>
                <div class="kt-card-content">
                    <div class="judging-options-grid">
                        @foreach ($cards as $card)
                            <x-julgar.card :card="$card" />
                        @endforeach
                    </div>
                </div>
                <div class="kt-card-footer justify-between gap-4">
                    <button type="button" data-clear-btn class="kt-btn kt-btn-outline">Limpar</button>
                    <button type="submit" data-confirm-btn form="julgar-form" class="kt-btn" disabled>Confirmar votos</button>
                </div>
            </div>
        </div>
    @elseif ($summary)
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card min-w-full judging-completed">
                <div class="kt-card-content">
                    <x-julgar.summary :summary="$summary" />
                </div>
            </div>
        </div>
    @else
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card min-w-full">
                <div class="kt-card-content">
                    <p data-guide-judge-empty class="py-8 text-center text-muted-foreground">
                        Nenhuma categoria disponível para julgamento.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($confirmedVotes->isNotEmpty())
        <section class="flex flex-col gap-5 pt-5 lg:pt-7.5" aria-labelledby="votos-title">
            <h2 id="votos-title" class="text-lg font-medium text-mono">Acompanhamento dos votos</h2>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($confirmedVotes as $item)
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">
                                <span class="text-xs text-secondary-foreground">Categoria:</span>
                                {{ $item['category']->title }}@if ($item['category']->acronym) - {{ $item['category']->acronym }}@endif
                            </h3>
                        </div>
                        <div class="kt-card-content flex flex-col gap-3">
                            <x-julgar.votes :votes="$item['votes']" :judges-count="$judgesCount" />
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (! $category && $summary)
        <div class="flex justify-end pt-5 lg:pt-7.5">
            <a href="{{ route('sair') }}" class="kt-btn judging-exit-button">Sair do sistema</a>
        </div>
    @endif

    @if ($category)
        <form method="POST" action="{{ route('panel.julgar.store') }}" id="julgar-form"
              data-julgar-form class="hidden">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <div data-inscription-fields></div>
        </form>

        <sl-dialog data-drawer label="Detalhes da Inscrição" class="judging-dialog" style="--width: min(960px, calc(100vw - 2rem));">
            <div data-drawer-label slot="label" class="font-medium"></div>
            <div data-drawer-body></div>
            @if (auth()->user()->isJudge())
                <button type="button" slot="footer" class="kt-btn kt-btn-outline" data-guide-drawer-help>Rever orientações</button>
            @endif
            <sl-button slot="footer" data-drawer-close>Fechar</sl-button>
        </sl-dialog>

        @foreach ($cards as $card)
            @include('admin.julgar.partials.drawer-'.$card['type'], ['card' => $card, 'inscription' => $card['inscription']])
        @endforeach
    @endif

    </div>

    <script src="{{ asset('js/julgar.js') . '?v=' . filemtime(public_path('js/julgar.js')) }}" defer></script>
@endsection


@push('styles')
<style>
    [data-julgar] .judging-completed { background: #dcfce7; color: #14532d; }
    [data-julgar] .judging-completed .text-mono { color: #14532d; }
    [data-julgar] .judging-completed-title { font-size: calc(1.125rem + 2pt); font-weight: 600; }
    [data-julgar] .judging-exit-button { background-color: #166534; border-color: #166534; color: #fff; min-height: 3rem; padding: .875rem 1.75rem; font-size: 1rem; font-weight: 600; }
    [data-julgar] .judging-exit-button:hover { background-color: #14532d; border-color: #14532d; color: #fff; }
    [data-julgar] .judging-summary-name { display: block; font-size: calc(.875rem + 2pt); overflow-wrap: anywhere; }
    [data-julgar] .judging-current-category { font-size: calc(.875rem + 2pt); font-weight: 700; color: #166534; }
    [data-julgar] .judging-options-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; }
    [data-julgar] .judging-option { min-width: 0; border-top: 6px solid #166534; background: linear-gradient(135deg, rgb(22 101 52 / 7%), rgb(22 101 52 / 3%)), var(--background, white); box-shadow: inset 0 0 20px rgb(22 101 52 / 5%); }
    [data-julgar] .judging-option:hover { box-shadow: inset 0 0 24px rgb(22 101 52 / 12%); }
    [data-julgar] .judging-option[aria-pressed="true"] { border-color: #166534; box-shadow: inset 0 0 24px rgb(22 101 52 / 15%), 0 0 0 2px #166534; }
    [data-julgar] .judging-option[aria-pressed="true"] [data-card-check] { background-color: #166534; border-color: #166534; color: #fff; }
    [data-julgar] .judging-note { margin-top: .75rem; font-size: .875rem; font-weight: 600; color: #166534; }
    .dark [data-julgar] .judging-note { color: #86efac; }
    [data-julgar] .judging-selection-count { font-size: calc(.875rem + 2pt); }
    [data-julgar] .judging-data-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; font-size: calc(1rem + 2pt); line-height: 1.65; }
    [data-julgar] .judging-data-full { grid-column: 1 / -1; }
    [data-julgar] .judging-data-title { font-size: calc(1.5rem + 2pt); font-weight: 600; overflow-wrap: anywhere; }
    [data-julgar] [data-drawer-body] { font-size: calc(1rem + 2pt); line-height: 1.65; }
    [data-julgar] [data-drawer-body] .text-sm { font-size: calc(1rem + 2pt); }
    [data-julgar] [data-drawer-body] .text-xs { font-size: calc(.9375rem + 2pt); }
    [data-julgar] [data-drawer-body] sl-details::part(summary) { font-size: calc(1.0625rem + 2pt); font-weight: 600; }
    [data-julgar] [data-drawer-body] .judging-content { color: #000; }
    [data-julgar] [data-drawer-label] { font-size: calc(1.25rem + 2pt); }
    @media (min-width: 640px) {
        [data-julgar] .judging-options-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        [data-julgar] .judging-data-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) { [data-julgar] .judging-options-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 1280px) { [data-julgar] .judging-options-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
</style>
@endpush
