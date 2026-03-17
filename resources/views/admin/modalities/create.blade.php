@php
    $object = $modality ?? null;
    $titulo = isset($object) ? 'Gerenciamento de Modalidade' : 'Criação de Modalidade';
    $route = isset($object) ? route('admin.modalities.update', $object->id) : route('admin.modalities.store');
    $titleBtn = isset($object) ? 'Salvar Alterações' : 'Criar Modalidade';
    $deleteRoute = isset($object) ? route('admin.modalities.destroy', ['modality' => $object->id]) : '';
    $checked = isset($object) ? $object->is_active : false;

    $editions = $editions
        ->mapWithKeys(function ($edition) {
            return [$edition->id => $edition->title];
        })
        ->toArray();

@endphp


@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create :titulo="$titulo" form-id='create-modality-form' form-action="{{ $route }}"
        space={{ false }} btn-cancel-title='Cancelar' btn-cancel-route="{{ route('admin.modalities.index') }}"
        :btn-submit-title="$titleBtn" :btn-delete=isset($object) btn-delete-route="{{ $deleteRoute }}" :put="isset($modality)"
        btn-delete-id='btnDeleteEdition'>

        <div class="flex w-full gap-4 mt-4  ">
            <div class="w-1/2">
                <x-form-select label="Edição" nameOld="edition_id" name="edition_id" id="edition_select"
                    value="{{ isset($object) ? $object->edition_id : '' }}" :multiple="false" required="true"
                    :options="$editions" />
            </div>


            <div class="w-1/2">
                <x-form.input type="text" class="mb-4" label="Título" placeholder="Ex: Personalidade Jurídica"
                    name="title" :value="isset($object) ? $object->title : null" required="true" />
            </div>
        </div>




        <div class="flex w-full gap-4 mt-2">
            <div class="w-1/2">
                <x-form.input
                    value="{{ isset($object) ? $object->candidacy_limit_per_modality : old('candidacy_limit_per_modality') }}"
                    type="number" name="candidacy_limit_per_modality" label="Máximo de Inscrições por Candidato"
                    placeholder="Ex: 3" :required="false" min="1" />
            </div>
            <div class="w-1/2 gap-2 justify-center-safe">
                <div class="kt-form-group">
                    <div class="flex items-center gap-2">

                        <input type="checkbox" value='1' name="is_active" @checked(isset($object) && $object->is_active) id="switch">
                        <label class="kt-label" for="switch">
                            Ativa ?
                        </label>
                    </div>


                </div>

            </div>

        </div>

        <sl-alert class="hidden w-full alter-success" variant="success" open>
            <sl-icon slot="icon" name="check2-circle"></sl-icon>
            <strong>Operação realizada.</strong><br />
            Modalidade Excluída
        </sl-alert>

    </x-pages.crud.create>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <sl-dialog label="Antenção!" class="dialog-overview">
        Deseja realmente excluir a Modalidade ?
        <div slot="footer" class="flex flex-row gap-4 justify-end">
            <sl-button class="btn-close-modal" variant="primary">Cancelar</sl-button>
            <sl-button class="btn-confirm-modal" variant="danger">Confirmar</sl-button>
        </div>

    </sl-dialog>
@endsection


@push('scripts')
    <script type="text/javascript">
        $('document').ready(function() {
            const dialog = document.querySelector('.dialog-overview');
            $('.btn-close-modal').on('click', function(e) {
                e.preventDefault();
                dialog.hide()
            });

            $('.btn-confirm-modal').on('click', function(e) {
                e.preventDefault();
                const deleteRoute = "{{ $deleteRoute }}";
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url: deleteRoute,
                    type: 'DELETE',
                    success: function(result) {
                        // Fechar o modal
                        dialog.hide();
                        $('.alter-success').removeClass('hidden');
                        setTimeout(function() {
                            window.location =
                                "{{ route('admin.modalities.index') }}";

                        }, 2000); // 2000ms = 2 segundos
                    },
                    error: function(xhr) {
                        // Fechar o modal
                        dialog.hide();
                        alert('Erro ao excluir a edição. Por favor, tente novamente.');
                    }
                });

            });
            $('#btnDeleteEdition').on('click', function() {
                dialog.show();

            });
        });
    </script>
@endpush
