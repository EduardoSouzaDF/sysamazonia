@php
    use App\Enum\RolesEnum;
    $tabs = [
        [
            'buttonId' => 'inf_btn',
            'tabId' => 'tab_inf',
            'titulo' => 'Informações Pessoais',
            'active' => true,
            'include' => 'admin.users.partial.form-personal',
            'includeData' => [
                'roles' => $roles,
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
            ],
        ],
    ];
@endphp

@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create titulo='Criação de Usuário' form-action="{{ route('admin.users.store') }}" use-tabs=true
        btn-cancel-title='Cancelar' btn-cancel-route="{{ route('admin.users.index') }}" btn-submit-title="Salvar"
        :tabs="$tabs" />
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            checktabs = function() {
                var selectedRoles = $('#roles').val();
                if (selectedRoles.includes('2')) {
                    $('#comission_btn').removeClass('hidden');
                } else {
                    $('#comission_btn').addClass('hidden');
                }
            }
            $('#roles').on('change', function() {
                checktabs();
            });

            checktabs();
        });
    </script>
@endpush
