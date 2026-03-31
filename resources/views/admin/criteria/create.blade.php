@php
    $object = $criterion ?? null;
    $titulo = isset($object) ? 'Gerenciamento de Critérios ' : 'Criação de Critério';
    $route = isset($object) ? route('admin.criteria.update', $object->id) : route('admin.criteria.store');
    $titleBtn = isset($object) ? 'Salvar Alterações' : 'Criar Critério';
    $deleteRoute = isset($object) ? route('admin.criteria.destroy', ['criterion' => $object->id]) : '';

    $categories = $categories
        ->mapWithKeys(function ($modality) {
            return [$modality->id => $modality->title];
        })
        ->toArray();
@endphp




@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create :titulo="$titulo" form-id='create-criteria-form' form-action="{{ $route }}"
        space={{ false }} btn-cancel-title='Cancelar' btn-cancel-route="{{ route('admin.criteria.index') }}"
        :btn-submit-title="$titleBtn" :btn-delete=isset($object) btn-delete-route="{{ $deleteRoute }}" :put="isset($criterion)"
        btn-delete-id='btnDeleteCriteria'>

        <div class="flex w-full gap-4 mt-4  ">
            <div class="w-1/2">
                <x-form-select label="Categoria" nameOld="category_id" name="category_id" id="category_id"
                    value="{{ isset($object) ? $object->category_id : '' }}" :multiple="false" required="true"
                    :options="$categories" />
            </div>


            <div class="w-1/2">
                <x-form.input type="text" class="mb-4" label="Nome" placeholder="Ex: Clareza" name="name"
                    :value="isset($object) ? $object->name : null" required="true" />
            </div>
        </div>

        <div class="w-full">
            <x-form.input type="text" class="mb-4" label="Descrição" name="description" :value="isset($object) ? $object->description : null"
                required="true" />
        </div>

        <div class="flex w-full gap-4 mt-4  ">

            <div class="w-4/12">
                <x-form.input type="number" class="mb-4" label="Peso" step="1" name="weight" :value="isset($object) ? $object->weight : null"
                    required="true" />
            </div>

            <div class="w-4/12">
                <x-form.input type="number" class="mb-4" label="Nota Mínima" name="min_score" :value="isset($object) ? $object->min_score : null"
                    required="true" />
            </div>

            <div class="w-4/12">
                <x-form.input type="number" class="mb-4" label="Nota Máxima" name="max_score" :value="isset($object) ? $object->max_score : null"
                    required="true" />
            </div>
        </div>





        <sl-alert class="hidden w-full alter-success" variant="success" open>
            <sl-icon slot="icon" name="check2-circle"></sl-icon>
            <strong>Operação realizada.</strong><br />
            Critério Excluído
        </sl-alert>

    </x-pages.crud.create>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <sl-dialog label="Antenção!" class="dialog-overview">
        Deseja realmente excluir o Critério ?
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
                                "{{ route('admin.criteria.index') }}";

                        }, 2000); // 2000ms = 2 segundos
                    },
                    error: function(xhr) {
                        // Fechar o modal
                        dialog.hide();
                        alert('Erro ao excluir a edição. Por favor, tente novamente.');
                    }
                });

            });
            $('#btnDeleteCriteria').on('click', function() {
                dialog.show();

            });
        });
    </script>
@endpush
