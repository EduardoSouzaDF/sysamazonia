@php
    use App\Enum\RolesEnum;
    $object = $user ?? null;

    $route = isset($object) ? route('admin.users.update', $object->id) : route('admin.users.store');
    $titulo = isset($object) ? 'Gerenciamento de Usuário' : 'Criação de Usuário';
    $deleteRoute = isset($object) ? route('admin.users.destroy', $object->id) : '';


    $tabs = [
        [
            'buttonId' => 'inf_btn',
            'tabId' => 'tab_inf',
            'titulo' => 'Informações Pessoais',
            'active' => true,
            'include' => 'admin.users.partial.form-personal',
            'includeData' => [
                'roles' => $roles,
                'object' => $object,
                'errors' => $errors->getMessages(),
            ],
        ],
        [
            'buttonId' => 'comission_btn',
            'tabId' => 'tab_comission',
            'titulo' => 'Dados da Comissão',
            'active' => false,
            'hidden' => true,
            'include' => 'admin.users.partial.form-comission',
            'includeData' => [
                'errors' => $errors->getMessages(),
                'categories' => $categories,
                'categoriesEvaluators' => $categoriesEvaluators,
                'object' => $object,
            ],
        ],
        [
            'buttonId' => 'extra_data_btn',
            'tabId' => 'tab_extra_data',
            'titulo' => 'Dados extras',
            'active' => false,
            'hidden' => true,
            'include' => 'admin.users.partial.form-extra',
            'includeData' => [
                'errors' => $errors->getMessages(),
                'object' => $object,
            ],
        ],
    ];
@endphp

@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create titulo='{{ $titulo }}' form-action="{{ $route }}" use-tabs=true
        btn-cancel-title='Cancelar' btn-cancel-route="{{ route('admin.users.index') }}" btn-submit-title="Salvar"
        :btn-delete=isset($object) btn-delete-route="{{ $deleteRoute }}" :put="isset($object)" :tabs="$tabs"
        btn-delete-id='btnDeleteUser'>


        <sl-alert class="hidden w-full alter-success" variant="success" open>
            <sl-icon slot="icon" name="check2-circle"></sl-icon>
            <strong>Operação realizada.</strong><br />
            Usuário Excluído
        </sl-alert>
    </x-pages.crud.create>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <sl-dialog label="Antenção!" class="dialog-overview">
        Deseja realmente excluir o Usuário ?
        <div slot="footer" class="flex flex-row gap-4 justify-end">
            <sl-button class="btn-close-modal" variant="primary">Cancelar</sl-button>
            <sl-button class="btn-confirm-modal" variant="danger">Confirmar</sl-button>
        </div>

    </sl-dialog>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            const select = document.querySelector("sl-select[name='roles[]']");
            select.addEventListener('sl-change', event => {
                let values = event.target.value;
                const showComissionTab = values.includes('{{ RolesEnum::COMISSAO }}');
                if (showComissionTab) {
                    $('#comission_btn').removeClass('hidden');
                    $('#extra_data_btn').removeClass('hidden');
                } else {
                    $('#comission_btn').addClass('hidden');
                    $('#extra_data_btn').addClass('hidden');
                }
            });

            // $('#comission_btn').removeClass('hidden');
        });
    </script>

    @if ($object)
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
                                    "{{ route('admin.users.index') }}";

                            }, 2000); // 2000ms = 2 segundos
                        },
                        error: function(xhr) {
                            // Fechar o modal
                            dialog.hide();
                            alert('Erro ao excluir o usuário. Por favor, tente novamente.');
                        }
                    });

                });
                $('#btnDeleteUser').on('click', function() {
                    dialog.show();

                });
            });
        </script>
    @endif
@endpush
