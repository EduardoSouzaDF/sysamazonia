@php
    $object = $category ?? null;
    $titulo = isset($object) ? 'Gerenciamento de Categoria' : 'Criação de Categoria';
    $route = isset($object) ? route('admin.categories.update', $object->id) : route('admin.categories.store');
    $titleBtn = isset($object) ? 'Salvar Alterações' : 'Criar Categoria';
    $deleteRoute = isset($object) ? route('admin.categories.destroy', ['category' => $object->id]) : '';
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
                'object' => $object,
            ],
        ],
    ];

@endphp



@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        .note-editable {
            background-color: white;
        }
    </style>
@endpush
@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create :titulo="$titulo" form-id='create-category-form' form-action="{{ $route }}" use-tabs=true
        :tabs="$tabs" space={{ false }} btn-cancel-title='Cancelar'
        btn-cancel-route="{{ route('admin.categories.index') }}" :btn-submit-title="$titleBtn" :btn-delete=isset($object)
        btn-delete-route="{{ $deleteRoute }}" :put="isset($category)" btn-delete-id='btnDeleteEdition'>


    </x-pages.crud.create>
    <sl-alert class="hidden w-full alter-success" variant="success" open>
        <sl-icon slot="icon" name="check2-circle"></sl-icon>
        <strong>Operação realizada.</strong><br />
        Modalidade Excluída
    </sl-alert>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <sl-dialog label="Antenção!" class="dialog-overview">
        Deseja realmente excluir a Categoria ?
        <div slot="footer" class="flex flex-row gap-4 justify-end">
            <sl-button class="btn-close-modal" variant="primary">Cancelar</sl-button>
            <sl-button class="btn-confirm-modal" variant="danger">Confirmar</sl-button>
        </div>

    </sl-dialog>
@endsection


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.js"></script>
    <script type="text/javascript">
        $('document').ready(function() {
            var oldRegulation = $("input[name='description']").val();

            $('#editor').summernote({
                tabsize: 2,
                height: 120,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            $('#editor').summernote('code', oldRegulation);


            $('#create-category-form').on('submit', function() {
                var markupStr = $('#editor').summernote('code');
                $('input[name="description"]').val(markupStr);
            });


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
                                "{{ route('admin.categories.index') }}";

                        }, 2000); // 2000ms = 2 segundos
                    },
                    error: function(xhr) {
                        // Fechar o modal
                        dialog.hide();
                        alert('Erro ao excluir a categoria. Por favor, tente novamente.');
                    }
                });

            });
            $('#btnDeleteEdition').on('click', function() {
                dialog.show();

            });
        });
    </script>
@endpush
