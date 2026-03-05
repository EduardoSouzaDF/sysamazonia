@extends('admin.content')

@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <style>
        nav>div {
            justify-content: space-between !important;
        }
    </style>
    <div class="kt-container-fixed">


    </div>
    <!-- Container -->
    <div class="w-full px-10">
        <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
            <div class="flex flex-col justify-center gap-2">
                <h1 class="text-xl font-medium leading-none text-mono">
                    Usuários do sistema
                </h1>
                <div class="flex items-center gap-2 font-medium">
                    <span class="text-sm text-secondary-foreground">
                        Gerenciamento de usuários e suas permissões
                    </span>
                    <span class="size-0.75 bg-mono/50 rounded-full">
                    </span>

                </div>
            </div>

        </div>
        <!-- End of Container -->
        <!-- Container -->
        <div class="w-full">
            <div class="grid gap-5 lg:gap-7.5">
                <div class="kt-card kt-card-grid min-w-full">
                    <div class="kt-card-header min-h-16 w-full flex flex-row">


                        <div class="w-1/2 flex-col flex gap-x-2">
                            <form id="search-form" class="flex-row flex gap-x-2">
                                <input type="text" placeholder="Procurar por nome, email ou perfil ..." class="kt-input  "
                                    id="search-input" />

                                <button type="button" id="search-button" class="kt-btn kt-btn-outline">
                                    Pesquisar
                                </button>
                            </form>

                        </div>
                        <div class="">
                            <a type="button" href="{{ route('admin.users.create') }}" class="kt-btn kt-btn-outline">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-plus" aria-hidden="true">
                                    <path d="M5 12h14"></path>
                                    <path d="M12 5v14"></path>
                                </svg>Criar Novo
                            </a>
                        </div>









                    </div>

                    <div class="kt-card-content">
                        <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10">
                            <div class="kt-scrollable-x-auto">
                                <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true"
                                    id="security_log_table">
                                    <thead>
                                        <tr>

                                            <th class="min-w-[200px]">
                                                <span class="kt-table-col">
                                                    <span class="kt-table-col-label">
                                                        Nome
                                                    </span>
                                                    <span class="kt-table-col-sort">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[200px]">
                                                <span class="kt-table-col">
                                                    <span class="kt-table-col-label">
                                                        Email
                                                    </span>
                                                    <span class="kt-table-col-sort">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[130px]">
                                                <span class="kt-table-col">
                                                    <span class="kt-table-col-label">
                                                        Data de Criação
                                                    </span>
                                                    <span class="kt-table-col-sort">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[130px]">
                                                <span class="kt-table-col">
                                                    <span class="kt-table-col-label">
                                                        Data de Verificação
                                                    </span>
                                                    <span class="kt-table-col-sort">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="min-w-[110px]">
                                                <span class="kt-table-col">
                                                    <span class="kt-table-col-label">
                                                        Perfis
                                                    </span>
                                                    <span class="kt-table-col-sort">
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="w-[60px]">
                                                Ações
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>
                                                    {{ $user->name }}
                                                </td>
                                                <td>
                                                    {{ $user->email }}
                                                </td>

                                                <td>
                                                    {{ $user->created_at->format('d/m/Y') }}
                                                </td>
                                                <td>
                                                    {{ $user->email_verified_at ? $user->email_verified_at->format('d/m/Y') : '' }}
                                                </td>
                                                <td>
                                                    @foreach ($user->roles()->get() as $role)
                                                        <span class="kt-badge kt-badge-outline kt-badge-warning">
                                                            {{ $role->name }}
                                                        </span>
                                                    @endforeach

                                                </td>
                                                <td>
                                                    <div data-kt-dropdown="true" data-kt-dropdown-trigger="click">
                                                        <button class="kt-btn" data-kt-dropdown-toggle="true">
                                                            Gerenciar<svg xmlns="http://www.w3.org/2000/svg" width="24"
                                                                height="24" viewBox="0 0 24 24" fill="none"
                                                                stroke="currentColor" stroke-width="2"
                                                                stroke-linecap="round" stroke-linejoin="round"
                                                                class="lucide lucide-chevron-down" aria-hidden="true">
                                                                <path d="m6 9 6 6 6-6"></path>
                                                            </svg>
                                                        </button>
                                                        <div class="kt-dropdown-menu w-52" data-kt-dropdown-menu="true">
                                                            <ul class="kt-dropdown-menu-sub">
                                                                <li>
                                                                    <a href="#" class="kt-dropdown-menu-link">
                                                                        <i class="ki-filled ki-security-user"></i>
                                                                        Logar Como</a>
                                                                </li>
                                                                <li>
                                                                    <a href="#" class="kt-dropdown-menu-link">
                                                                        <i class="ki-filled ki-user-edit"></i>
                                                                        Editar</a>
                                                                </li>
                                                                <li>
                                                                    <a href="#" class="kt-dropdown-menu-link">
                                                                        <i class="ki-filled ki-delete-folder"></i>
                                                                        Deletar</a>
                                                                </li>

                                                            </ul>
                                                        </div>
                                                    </div>

                                                </td>
                                        @endforeach


                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                                {{ $users->links() }}

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- End of Container -->
    @endsection
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#search-button').on('click', function() {
                    var searchValue = $('#search-input').val();
                    // Implementar a lógica de busca aqui, por exemplo, redirecionar para uma rota com o parâmetro de busca
                    window.location.href = "{{ route('admin.users.index') }}?search=" + encodeURIComponent(
                        searchValue);
                });
                document.getElementById('search-form').addEventListener('submit', function(event) {
                    event.preventDefault();
                    // Código para realizar a busca
                });

                document.getElementById('search-input').addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') {
                        document.getElementById('search-button').click();
                    }
                });
            });
        </script>
    @endpush
