@php
    $object = $category ?? null;
    $titulo = isset($object) ? 'Gerenciamento de Categoria' : 'Criação de Categoria';
    $route = isset($object) ? route('admin.categories.update', $object->id) : route('admin.categories.store');
    $titleBtn = isset($object) ? 'Salvar Alterações' : 'Criar Categoria';
    $deleteRoute = isset($object) ? route('admin.categories.destroy', ['modality' => $object->id]) : '';
    $checked = isset($object) ? $object->is_active : false;

    $modalities = $modalities
        ->mapWithKeys(function ($modality) {
            return [$modality->id => $modality->title];
        })
        ->toArray();

    $tabs = [
        [
            'buttonId' => 'inf_btn',
            'tabId' => 'tab_inf',
            'titulo' => 'Dados da Categoria',
            'active' => true,
            'include' => 'admin.categories.partial.info',
            'includeData' => [
                'modalities' => $modalities,
                'errors' => $errors->getMessages(),
                'object' => $object,
            ],
        ],
        [
            'buttonId' => 'config_btn',
            'tabId' => 'tab_config',
            'titulo' => 'Configurações',
            'active' => false,
            'hidden' => false,
            'include' => 'admin.categories.partial.config',
            'includeData' => [
                'errors' => $errors->getMessages(),
            ],
        ],
    ];

@endphp


@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create :titulo="$titulo" form-id='create-category-form' form-action="{{ $route }}" use-tabs=true
        :tabs="$tabs" space={{ false }} btn-cancel-title='Cancelar'
        btn-cancel-route="{{ route('admin.categories.index') }}" :btn-submit-title="$titleBtn" :btn-delete=isset($object)
        btn-delete-route="{{ $deleteRoute }}" :put="isset($category)" btn-delete-id='btnDeleteEdition'>


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
