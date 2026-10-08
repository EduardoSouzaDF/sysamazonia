@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed flex min-w-0 flex-col gap-5 lg:gap-7.5">
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Agraciados da edição</h1>
            <span class="text-sm font-medium text-secondary-foreground">{{ $edition->title }}</span>
        </div>
        <div class="flex gap-2 print:hidden">
            <button type="button" class="kt-btn kt-btn-outline" onclick="window.print()">Imprimir</button>
            <a href="{{ route('admin.acompanhamento.index', ['edition' => $edition->id]) }}" class="kt-btn kt-btn-outline">Voltar</a>
        </div>
    </div>

    @include('admin.acompanhamento.partials.messages')

    @unless ($isComplete)
        <x-messages.alert message="Ainda há categorias aguardando a confirmação dos agraciados." type="warning" />
    @endunless

    <div class="grid gap-5 md:grid-cols-2">
        @foreach ($categories as $category)
            @php $awardees = $awardeesByCategory[$category->id]; @endphp
            <section class="kt-card break-inside-avoid">
                <div class="kt-card-header">
                    <h2 class="kt-card-title">
                        {{ $category->title }}@if ($category->acronym) - {{ $category->acronym }}@endif
                    </h2>
                </div>
                <div class="kt-card-content">
                    @if (! $category->areAwardeesConfirmed())
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-sm text-muted-foreground">Aguardando confirmação</span>
                            <a href="{{ route('admin.acompanhamento.awardees', $category) }}" class="kt-btn kt-btn-sm print:hidden">Confirmar agraciados</a>
                        </div>
                    @else
                        <ol class="flex flex-col gap-2">
                            @foreach ($awardees as $awardee)
                                <li class="flex items-center gap-3">
                                    <span class="kt-badge kt-badge-primary shrink-0 whitespace-nowrap">{{ $awardee->award_position ? $awardee->award_position.'º lugar' : 'Agraciado' }}</span>
                                    <span class="text-sm">
                                        <span class="font-medium text-mono">{{ trim($category->acronym.' '.$awardee->id) }}</span>
                                        — {{ $category->is_honorific ? $awardee->name : $awardee->title }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
