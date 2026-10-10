<div class="space-y-6">
    <div class="flex flex-col items-center gap-3 text-center">
        <span class="flex size-14 items-center justify-center rounded-full bg-success/15 text-success">
            <svg class="size-7" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 aria-hidden="true">
                <path d="M20 6 9 17l-5-5"></path>
            </svg>
        </span>
        <h2 class="judging-completed-title text-lg font-semibold text-mono">Seu julgamento foi concluído com sucesso</h2>
        <div class="space-y-3 leading-relaxed">
            <p class="font-medium">Obrigado por fazer parte desta história!</p>
            <p>Sua dedicação e seu conhecimento contribuem para reconhecer iniciativas que transformam a Amazônia e promovem um futuro mais sustentável, inovador e socialmente justo.</p>
            <p>Sua participação faz a diferença para o futuro da Amazônia!</p>
        </div>
    </div>

    @foreach ($summary as $group)
        <div class="kt-card p-4">
            <p class="font-medium text-mono">
                {{ $group['category']->acronym }} — {{ $group['category']->title }}
            </p>
            <ul class="mt-3 grid list-disc grid-cols-1 gap-1 pl-5 text-sm sm:grid-cols-2">
                @foreach ($group['inscriptions'] as $item)
                    <li>
                        <span>{{ $item['label'] }} - {{ $item['year'] }}</span>
                        <span class="judging-summary-name">{{ $item['type'] === 'nominee' ? $item['inscription']->name : $item['inscription']->title }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
