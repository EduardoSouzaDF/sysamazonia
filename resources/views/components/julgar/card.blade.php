@props(['card'])
@php($inscription = $card['inscription'])
<button type="button"
        data-card
        data-key="{{ $card['key'] }}"
        data-label="{{ $card['label'] }} - {{ $card['year'] }}"
        aria-pressed="false"
        class="kt-card w-full border p-4 text-left transition-colors hover:border-primary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="truncate font-medium text-mono">
                {{ $card['label'] }} <span class="font-normal text-muted-foreground">- {{ $card['year'] }}</span>
            </p>
            <p class="mt-0.5 truncate text-sm text-muted-foreground">
                {{ $inscription->candidate?->nome }}
            </p>
        </div>
        <span data-card-check
              class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full border border-border bg-transparent text-primary-foreground"
              role="checkbox"
              aria-checked="false"
              tabindex="-1">
            <svg class="hidden size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M20 6 9 17l-5-5"></path>
            </svg>
        </span>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-secondary px-2 py-0.5 text-xs text-secondary-foreground">
            {{ $card['type'] === 'nominee' ? 'Inscrição honorífica' : 'Inscrição' }}
        </span>
        @if (method_exists($inscription, 'statusName'))
            <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs text-primary">
                {{ $inscription->statusName() }}
            </span>
        @endif
    </div>
</button>
