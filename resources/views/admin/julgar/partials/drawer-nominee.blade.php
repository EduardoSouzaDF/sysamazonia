<template data-drawer-template data-for-key="{{ $card['key'] }}">
    <div class="space-y-4">
        <sl-details summary="Dados da Indicação" open>
            <dl class="grid grid-cols-3 gap-3 text-sm  ">
                <div>
                    <dt class="text-muted-foreground">Categoria</dt>
                    <dd>{{ $inscription->category?->title }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Modalidade</dt>
                    <dd>{{ $inscription->category?->modality?->title }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Edição</dt>
                    <dd>{{ $inscription->category?->modality?->edition?->title }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Indicado</dt>
                    <dd>{{   $inscription->name }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Estado</dt>
                    <dd>{{ $inscription->state ?? '—' }}</dd>
                </div>
                
            </dl>
        </sl-details>

        <sl-details summary="Apresentação" open>
            <p class="whitespace-pre-line text-sm text-muted-foreground">{{ $inscription->presentation ?? '—' }}</p>
        </sl-details>

        <sl-details summary="Atividades">
            <p class="whitespace-pre-line text-sm text-muted-foreground">{{ $inscription->activities ?? '—' }}</p>
        </sl-details>

        <sl-details summary="Justificativa">
            <p class="whitespace-pre-line text-sm text-muted-foreground">{{ $inscription->justification ?? '—' }}</p>
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
                                   {{ $file->file_name }} -  ({{ $file->document_type }})
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </sl-details>

        <sl-button variant="primary" data-drawer-confirm class="w-full">Confirmar</sl-button>
    </div>
</template>
