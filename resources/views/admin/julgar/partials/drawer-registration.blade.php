<template data-drawer-template data-for-key="{{ $card['key'] }}">
    <div class="space-y-4">
        <sl-details summary="Dados da Inscrição" open>
            <dl class="grid grid-cols-3 gap-3 text-sm sm:grid-cols-3">
                 <div>
                    <dt class="text-muted-foreground">Edição</dt> 
                    <dd>{{ $inscription->category?->modality?->edition?->title }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Categoria</dt>
                    <dd>{{ $inscription->category?->title }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Modalidade</dt>
                    <dd>{{ $inscription->category?->modality?->title }}</dd>
                </div>
                <div class=" ">
                    <dt class="text-muted-foreground">Título</dt>
                    <dd class="text-xl">{{ $inscription->title }}</dd>
                </div>

                <div class=" ">
                    <dt class="text-muted-foreground">Avaliação Final:</dt>
                    <dd class="text-xl">{{ $inscription->evaluation_avg }}</dd>
                </div>
                 <div class=" ">
                    <dt class="text-muted-foreground">Indicações:</dt>
                    <dd class="text-xl">{{ $inscription->indications()->count() }}</dd>
                </div>
                
                
                
                
            </dl>
        </sl-details>

        <sl-details summary="Conteúdo">
            <div class="space-y-3 text-sm">
                <div>
                    <p class="font-medium">Resumo</p>
                    <p class="whitespace-pre-line text-muted-foreground">{{ $inscription->resumo }}</p>
                </div>
                <div>
                    <p class="font-medium">Objetivos</p>
                    <p class="whitespace-pre-line text-muted-foreground">{{ $inscription->objetivo }}</p>
                </div>
                <div>
                    <p class="font-medium">Desenvolvimento</p>
                    <p class="max-h-40 overflow-y-auto whitespace-pre-line text-muted-foreground">{{ $inscription->desenvolvimento }}</p>
                </div>
                <div>
                    <p class="font-medium">Conclusão</p>
                    <p class="whitespace-pre-line text-muted-foreground">{{ $inscription->conclusao }}</p>
                </div>
            </div>
        </sl-details>

        <sl-details summary="Anexos ({{ $inscription->files->count() }})">
            @if ($inscription->files->isEmpty())
                <p class="text-sm text-muted-foreground">Nenhum anexo.</p>
            @else
                <ul class="list-disc space-y-1 pl-5 text-sm">
                    @foreach ($inscription->files as $file)
                        <li>
                          
                            @if ($file->document_type)
                                <a target="_blank" href="{{ route('admin.registration.file', ['file' => $file->id]) }}" class="text-primary hover:underline">
                                   {{ $file->file_name }} - ({{ $file->document_type }})
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </sl-details>

        <sl-details summary="Avaliações e indicações" open>
            @php($opinions = $inscription->opinions->sortByDesc('created_at'))
            @if ($opinions->isEmpty() && $inscription->indications->isEmpty())
                <p class="text-sm text-muted-foreground">Nenhuma avaliação ou indicação registrada.</p>
            @else
                <div class="space-y-3">
                    @foreach ($opinions as $opinion)
                        <sl-details summary="Avaliado em {{ $opinion->created_at?->format('d/m/Y') }} por: {{ $opinion->user?->name }}">
                            <div class="space-y-2 text-sm">
                                @foreach ($opinion->scores as $score)
                                    <p>
                                        <b>{{ $score->evaluationCriterion?->name }}: {{ (int) $score->valor }}</b>
                                        @if ($score->descricao)
                                            <span class="block text-xs text-muted-foreground">{{ $score->descricao }}</span>
                                        @endif
                                    </p>
                                @endforeach
                            </div>
                        </sl-details>
                    @endforeach
                    @foreach ($inscription->indications as $indication)
                        <sl-details summary="Indicado em {{ $indication->created_at?->format('d/m/Y') }} por: {{ $indication->user?->name }}">
                            <p class="text-xs text-muted-foreground">{{ $indication->descricao }}</p>
                        </sl-details>
                    @endforeach
                </div>
            @endif
        </sl-details>

        <sl-button variant="primary" data-drawer-confirm class="w-full">Confirmar</sl-button>
    </div>
</template>
