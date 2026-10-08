@props(['votes', 'judgesCount'])

@forelse ($votes as $vote)
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
