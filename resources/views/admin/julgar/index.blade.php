@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed" data-julgar
    @if ($category)
        data-category-id="{{ $category->id }}"
        data-effective-quota="{{ $effectiveQuota }}"
        data-selected-ids="{{ json_encode($selectedIds) }}"
    @endif
>
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Julgar</h1>
            @if ($category)
                <div class="flex items-center gap-2 font-medium">
                    <span class="text-sm text-secondary-foreground">
                        {{ $category->title }} ({{ $category->acronym }})
                    </span>
                    <span class="size-0.75 bg-mono/50 rounded-full"></span>
                    <span class="text-sm text-secondary-foreground">
                        Edição: {{ $category->modality?->edition?->title }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    @if (session('success'))
        <x-messages.alert :message="session('success')" type="success" />
    @endif

    @if ($category)
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card min-w-full">
                <div class="kt-card-header min-h-16 w-full flex flex-row items-center justify-between gap-4">
                    <h3 class="kt-card-title">
                        {{ $category->is_honorific ? 'Indicação para esta categoria' : 'Iniciativas selecionadas para esta categoria' }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Selecionados: <span data-selected-count class="font-medium text-mono">0</span>
                        de {{ $effectiveQuota }}
                        · Restam: <span data-remaining class="font-medium text-mono">{{ $remainingQuota }}</span>
                    </p>
                </div>
                <div class="kt-card-content">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($cards as $card)
                            <x-julgar.card :card="$card" />
                        @endforeach
                    </div>
                </div>
                <div class="kt-card-footer justify-between gap-4">
                    <button type="button" data-clear-btn class="kt-btn kt-btn-outline">Limpar</button>
                    <button type="submit" data-confirm-btn form="julgar-form" class="kt-btn" disabled>Confirmar</button>
                </div>
            </div>
        </div>
    @elseif ($summary)
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card min-w-full">
                <div class="kt-card-content">
                    <x-julgar.summary :summary="$summary" />
                </div>
            </div>
        </div>
    @else
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card min-w-full">
                <div class="kt-card-content">
                    <p class="py-8 text-center text-muted-foreground">
                        Nenhuma categoria disponível para julgamento.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($category)
        <form method="POST" action="{{ route('panel.julgar.store') }}" id="julgar-form"
              data-julgar-form class="hidden">
            @csrf
            <input type="hidden" name="category_id" value="{{ $category->id }}">
            <div data-inscription-fields></div>
        </form>

        <sl-drawer data-drawer label="Detalhes da Inscrição" class="..." style="--size: 60vw;">
            <div data-drawer-label slot="label" class="font-medium"></div>
            <div data-drawer-body></div>
            <sl-button slot="footer" data-drawer-close>Fechar</sl-button>
        </sl-drawer>

        @foreach ($cards as $card)
            @include('admin.julgar.partials.drawer-'.$card['type'], ['card' => $card, 'inscription' => $card['inscription']])
        @endforeach
    @endif

    </div>

    <script src="{{ asset('js/julgar.js') }}" defer></script>
@endsection

