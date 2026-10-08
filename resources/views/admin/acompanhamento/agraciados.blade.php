@extends('admin.content')

@php
    $labelOf = fn ($inscription) => $type === 'nominee' ? $inscription->name : $inscription->title;
    $codeOf = fn ($inscription) => trim(($category->acronym ?? '').' '.$inscription->id);
@endphp

@section('maincontent')
<div class="kt-container-fixed flex flex-col gap-5 lg:gap-7.5">
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Agraciados</h1>
            <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-secondary-foreground">
                <span>{{ $category->title }}@if ($category->acronym) ({{ $category->acronym }})@endif</span>
                <span class="size-0.75 rounded-full bg-mono/50"></span>
                <span>{{ $edition->title }}</span>
            </div>
            <p class="text-sm font-semibold text-mono">Quantidade a ser agraciada: {{ $category->recipients_count }}</p>
        </div>
        <a href="{{ route('admin.acompanhamento.index', ['edition' => $edition->id]) }}" class="kt-btn kt-btn-outline">Voltar</a>
    </div>

    @include('admin.acompanhamento.partials.messages')

    @if ($category->areAwardeesConfirmed())
        <section class="kt-card">
            <div class="kt-card-header">
                <h2 class="kt-card-title">Agraciados confirmados</h2>
                <span class="text-sm text-secondary-foreground">em {{ $category->awardees_confirmed_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="kt-card-content">
                <ol class="flex flex-col gap-2">
                    @foreach ($awardees as $awardee)
                        <li class="flex items-center gap-4 rounded-lg border border-border p-3">
                            <span class="kt-badge kt-badge-primary whitespace-nowrap">{{ $awardee->award_position ? $awardee->award_position.'º lugar' : 'Agraciado' }}</span>
                            <span class="grow"><span class="font-medium text-mono">{{ $codeOf($awardee) }}</span> — {{ $labelOf($awardee) }}</span>
                            <span class="text-sm text-secondary-foreground whitespace-nowrap">{{ $awardee->judge_selections_count }} de {{ $judgesCount }} votos</span>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @else
        <form method="POST" action="{{ route('admin.acompanhamento.awardees.store', $category) }}" id="awardees-form"
            data-awardees data-limit="{{ $category->recipients_count }}" class="flex flex-col gap-5 lg:gap-7.5">
            @csrf

            <section class="kt-card">
                <div class="kt-card-header flex-wrap gap-3">
                    <h2 class="kt-card-title">Classificação</h2>
                    <p class="text-sm text-secondary-foreground" aria-live="polite">
                        Selecionados: <span data-awardees-count class="font-medium text-mono">0</span> de {{ $category->recipients_count }}
                    </p>
                </div>
                <div class="kt-card-content">
                    <p class="mb-3 text-sm text-secondary-foreground">
                        A ordem de seleção define a posição. Use as setas para reordenar.
                    </p>
                    <ol class="flex flex-col gap-2" data-ranking>
                        @for ($position = 1; $position <= $category->recipients_count; $position++)
                            <li data-slot="{{ $position }}" class="flex items-center gap-3 rounded-lg border border-dashed border-border p-3">
                                <span class="kt-badge kt-badge-outline whitespace-nowrap">{{ $position }}º lugar</span>
                                <span class="grow text-sm text-muted-foreground" data-slot-content>Selecione uma inscrição abaixo</span>
                            </li>
                        @endfor
                    </ol>
                    <div data-ranking-inputs hidden></div>
                </div>
                <div class="kt-card-footer justify-end">
                    <button type="button" class="kt-btn" data-dialog-open="confirm-awardees" data-awardees-submit disabled>Confirmar agraciados</button>
                </div>
            </section>

            <section class="kt-card">
                <div class="kt-card-header">
                    <h2 class="kt-card-title">Mais votadas</h2>
                    <span class="text-sm text-secondary-foreground">Destaque das {{ $top->count() }} inscrições com mais votos</span>
                </div>
                <div class="kt-card-content">
                    @forelse ($top as $inscription)
                        <label data-top="{{ $type }}:{{ $inscription->id }}" class="flex cursor-pointer items-center gap-3 border-b border-border py-2 last:border-b-0">
                            <input type="checkbox" class="kt-checkbox" data-mirror="{{ $inscription->id }}">
                            <span class="grow"><span class="font-medium text-mono">{{ $codeOf($inscription) }}</span> — {{ $labelOf($inscription) }}</span>
                            <span class="text-sm text-secondary-foreground whitespace-nowrap">{{ $inscription->judge_selections_count }} de {{ $judgesCount }} votos</span>
                        </label>
                    @empty
                        <p class="text-sm text-muted-foreground">Nenhuma inscrição recebeu votos nesta categoria.</p>
                    @endforelse
                </div>
            </section>

            <section class="kt-card">
                <div class="kt-card-header flex-wrap gap-3">
                    <h2 class="kt-card-title">Todas as inscrições</h2>
                    <input type="search" class="kt-input w-full sm:w-72" placeholder="Pesquisar por código ou título..." data-search aria-label="Pesquisar inscrições">
                </div>
                <div class="kt-card-content">
                    @foreach ($options as $inscription)
                        <label data-option="{{ $inscription->id }}" data-search-text="{{ mb_strtolower($codeOf($inscription).' '.$labelOf($inscription)) }}"
                            class="flex cursor-pointer items-center gap-3 border-b border-border py-2 last:border-b-0">
                            <input type="checkbox" class="kt-checkbox" value="{{ $inscription->id }}" data-pick
                                data-code="{{ $codeOf($inscription) }}" data-label="{{ $labelOf($inscription) }}">
                            <span class="grow"><span class="font-medium text-mono">{{ $codeOf($inscription) }}</span> — {{ $labelOf($inscription) }}</span>
                            <span class="text-sm text-secondary-foreground whitespace-nowrap">{{ $inscription->judge_selections_count }} de {{ $judgesCount }} votos</span>
                        </label>
                    @endforeach
                    <p class="hidden py-4 text-center text-sm text-muted-foreground" data-search-empty>Nenhuma inscrição encontrada.</p>
                </div>
            </section>

            <dialog id="confirm-awardees" class="follow-up-dialog">
                <div class="flex flex-col gap-4">
                    <h3 class="text-base font-semibold text-mono">Confirmar agraciados</h3>
                    <p class="text-sm text-secondary-foreground">
                        As inscrições selecionadas passarão a <strong>Agraciado</strong> na ordem da classificação e a categoria ficará travada. Esta ação não pode ser desfeita.
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="kt-btn kt-btn-outline" data-dialog-close>Cancelar</button>
                        <button type="submit" class="kt-btn" form="awardees-form">Confirmar</button>
                    </div>
                </div>
            </dialog>
        </form>
    @endif
</div>

@include('admin.acompanhamento.partials.assets')
@endsection
