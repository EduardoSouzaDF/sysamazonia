@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed flex min-w-0 flex-col gap-5 lg:gap-7.5">
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Acompanhamento</h1>
            @if ($edition)
                <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-secondary-foreground">
                    <span>{{ $edition->title }}</span>
                    @if ($isReadOnly)
                        <span class="kt-badge kt-badge-outline">Somente leitura</span>
                    @endif
                    @if ($edition->isVotingClosed())
                        <span class="kt-badge kt-badge-outline kt-badge-success">
                            Votação finalizada em {{ $edition->voting_closed_at->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            @endif
        </div>

        <form method="GET" action="{{ route('admin.acompanhamento.index') }}" class="flex items-center gap-2">
            <label for="edition" class="text-sm text-secondary-foreground">Edição</label>
            <select name="edition" id="edition" class="kt-select" onchange="this.form.submit()">
                @foreach ($editions as $option)
                    <option value="{{ $option->id }}" @selected($edition?->is($option))>{{ $option->title }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="kt-btn kt-btn-outline">Ver</button></noscript>
        </form>
    </div>

    @include('admin.acompanhamento.partials.messages')

    @if ($edition && $allAwardeesConfirmed)
        <div class="kt-card flex-row flex-wrap items-center justify-between gap-3 p-5">
            <p class="text-sm font-medium text-mono">Todas as categorias desta edição tiveram os agraciados confirmados.</p>
            <a href="{{ route('admin.acompanhamento.edition-awardees', $edition) }}" class="kt-btn">Agraciados da edição</a>
        </div>
    @endif

    @if (! $edition)
        <div class="kt-card">
            <div class="kt-card-content">
                <p class="py-8 text-center text-muted-foreground">Nenhuma edição em julgamento.</p>
            </div>
        </div>
    @else
        <div data-refresh-interval="10000" class="flex min-w-0 flex-col gap-5 lg:gap-7.5">
            <p class="-mb-2 text-xs text-muted-foreground">
                Atualização automática a cada 10 segundos · Atualizado às <span data-live-updated-at>{{ now()->format('H:i:s') }}</span>
            </p>

            <section class="kt-card min-w-0" aria-labelledby="comissao-title" data-live="comissao">
                <div class="kt-card-header">
                    <h2 id="comissao-title" class="kt-card-title">Acompanhamento da Comissão</h2>
                </div>
                <div class="kt-card-content p-0">
                    @if ($judges->isEmpty() || $categories->isEmpty())
                        <p class="py-8 text-center text-muted-foreground">
                            {{ $judges->isEmpty() ? 'Nenhum julgador cadastrado.' : 'Nenhuma categoria com inscrições para julgamento nesta edição.' }}
                        </p>
                    @else
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table follow-up-matrix">
                                <thead>
                                    <tr>
                                        <th class="follow-up-sticky">Julgador</th>
                                        @foreach ($categories as $category)
                                            <th data-column="{{ $category->id }}" class="text-center whitespace-nowrap" title="{{ $category->title }}">
                                                Categoria<br><span class="font-medium text-mono">{{ $category->acronym ?: $category->title }}</span>
                                            </th>
                                        @endforeach
                                        @if ($canManage)
                                            <th class="text-center">Opções</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($judges as $judge)
                                        <tr>
                                            <td class="follow-up-sticky font-medium whitespace-nowrap">{{ $judge->name }}</td>
                                            @foreach ($categories as $category)
                                                @php $status = $matrix[$judge->id.':'.$category->id]; @endphp
                                                <td class="text-center">
                                                    <span data-cell="{{ $judge->id }}:{{ $category->id }}" data-status="{{ $status }}"
                                                        class="inline-flex min-w-24 justify-center rounded-md px-3 py-1.5 text-xs font-semibold text-white {{ $status === 'finalizada' ? 'bg-green-600' : 'bg-orange-500' }}">
                                                        {{ $status === 'finalizada' ? 'Finalizada' : 'Aberta' }}
                                                    </span>
                                                </td>
                                            @endforeach
                                            @if ($canManage)
                                                <td class="text-center">
                                                    <button type="button" data-action="resetar" class="kt-btn kt-btn-sm kt-btn-ghost text-red-600"
                                                        data-dialog-open="reset-{{ $judge->id }}">Resetar</button>
                                                    <dialog id="reset-{{ $judge->id }}" class="follow-up-dialog">
                                                        <form method="POST" action="{{ route('admin.acompanhamento.reset', [$edition, $judge]) }}" class="flex flex-col gap-4">
                                                            @csrf
                                                            <h3 class="text-base font-semibold text-mono">Resetar julgador</h3>
                                                            <p class="text-sm text-secondary-foreground">
                                                                Apagar todas as seleções de <strong>{{ $judge->name }}</strong> nesta edição?
                                                                O julgador voltará a votar desde a primeira categoria.
                                                            </p>
                                                            <div class="flex justify-end gap-2">
                                                                <button type="button" class="kt-btn kt-btn-outline" data-dialog-close>Cancelar</button>
                                                                <button type="submit" class="kt-btn kt-btn-destructive">Apagar seleções</button>
                                                            </div>
                                                        </form>
                                                    </dialog>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                @if ($canManage)
                    <div class="kt-card-footer justify-end">
                        <button type="button" data-action="finalizar" class="kt-btn" data-dialog-open="close-voting">Finalizar votação</button>
                        <dialog id="close-voting" class="follow-up-dialog">
                            <form method="POST" action="{{ route('admin.acompanhamento.close', $edition) }}" class="flex flex-col gap-4">
                                @csrf
                                <h3 class="text-base font-semibold text-mono">Finalizar votação</h3>
                                <p class="text-sm text-secondary-foreground">
                                    Esta ação é <strong>definitiva</strong>: os julgadores não poderão mais votar e a votação não pode ser reaberta.
                                </p>
                                @if ($pending !== [])
                                    <div class="rounded-lg border border-orange-300 bg-orange-50 p-3 text-sm dark:bg-orange-950/30">
                                        <p class="font-medium text-orange-700 dark:text-orange-300">Ainda há {{ count($pending) }} categoria(s) em aberto:</p>
                                        <ul class="mt-2 flex max-h-48 flex-col gap-1 overflow-y-auto">
                                            @foreach ($pending as $item)
                                                <li data-pending="{{ $item }}">{{ $item }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="kt-btn kt-btn-outline" data-dialog-close>Cancelar</button>
                                    <button type="submit" class="kt-btn">Finalizar votação</button>
                                </div>
                            </form>
                        </dialog>
                    </div>
                @endif
            </section>

            <section class="flex flex-col gap-5" aria-labelledby="votos-title" data-live="votos">
                <h2 id="votos-title" class="text-lg font-medium text-mono">Acompanhamento dos votos por categoria</h2>

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($categories as $category)
                        <div class="kt-card">
                            <div class="kt-card-header">
                                <h3 class="kt-card-title">
                                    <span class="text-xs text-secondary-foreground">Categoria:</span>
                                    {{ $category->title }}@if ($category->acronym) - {{ $category->acronym }}@endif
                                </h3>
                            </div>
                            <div class="kt-card-content flex flex-col gap-3">
                                @forelse ($votes[$category->id] as $vote)
                                    <div data-vote="{{ $vote['key'] }}" class="flex flex-col gap-1">
                                        <span class="truncate text-sm" title="{{ $vote['label'] }}">{{ $vote['label'] }}</span>
                                        <div class="h-4 w-full overflow-hidden rounded-full bg-muted">
                                            <div class="flex h-full items-center rounded-full bg-primary px-2 text-[10px] font-medium whitespace-nowrap text-primary-foreground"
                                                style="width: {{ $judgesCount > 0 ? max(20, round($vote['votes'] / $judgesCount * 100)) : 0 }}%">
                                                {{ $vote['votes'] }} de {{ $judgesCount }} votos
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-muted-foreground">Nenhum voto registrado.</p>
                                @endforelse
                            </div>
                            @if ($edition->isVotingClosed())
                                <div class="kt-card-footer justify-end">
                                    <a href="{{ route('admin.acompanhamento.awardees', $category) }}" class="kt-btn kt-btn-sm {{ $category->areAwardeesConfirmed() ? 'kt-btn-outline' : '' }}">
                                        {{ $category->areAwardeesConfirmed() ? 'Ver agraciados' : 'Confirmar agraciados' }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    @endif
</div>

@include('admin.acompanhamento.partials.assets')
@endsection
