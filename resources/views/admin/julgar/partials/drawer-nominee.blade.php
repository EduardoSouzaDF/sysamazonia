<template data-drawer-template data-for-key="{{ $card['key'] }}">
    <div class="space-y-4">
        <sl-details summary="Dados da Indicação" open>
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-muted-foreground">Indicado</dt>
                    <dd>{{ $inscription->candidate?->nome ?? $inscription->name }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Estado</dt>
                    <dd>{{ $inscription->state ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-muted-foreground">Contato</dt>
                    <dd>{{ $inscription->contact_data ?? '—' }}</dd>
                </div>
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
                            {{ $file->file_name }}
                            @if ($file->document_type)
                                <span class="text-muted-foreground">({{ $file->document_type }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </sl-details>

        <sl-button variant="primary" data-drawer-confirm class="w-full">Confirmar</sl-button>
    </div>
</template>
