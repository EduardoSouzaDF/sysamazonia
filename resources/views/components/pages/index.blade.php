@props([
    'titulo' => 'titulo',
    'subtitulo' => 'subtitulo',
    'searchPlaceholder' => 'Procurar  ...',
    'titleBtnPesquisar' => 'Pesquisar',
    'idBtnPesquisar' => 'search-button',
    'routeCreatenew' => '/',
    'textCreateNew' => 'Criar Novo',
    'columns',
    'data',
    'actions',
    'routeSearch',
    'paginator' => null, // Instância de LengthAwarePaginator para paginação
])
 

<style>
    nav>div {
        justify-content: space-between !important;
    }
</style>
<div class="w-full px-10">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ $titulo }}
            </h1>
            <div class="flex items-center gap-2 font-medium">
                <span class="text-sm text-secondary-foreground">
                    {{ $subtitulo }}
                </span>
                <span class="size-0.75 bg-mono/50 rounded-full">
                </span>

            </div>
        </div>

    </div>

    <div class="w-full">
        <div class="grid gap-5 lg:gap-7.5">
            <div class="kt-card kt-card-grid min-w-full">
                <div class="kt-card-header min-h-16 w-full flex flex-row">


                    <div class="w-auto flex-col flex gap-x-2">
                        <form id="search-form" class="flex-row flex gap-x-2">
                            <input type="text" placeholder="{{ $searchPlaceholder }}" class="kt-input w-80  "
                                id="search-input" name="search" style="width: 40vw;"/>


                            {{ $searchForm ?? '' }}
                             <button type="button" id="{{ $idBtnPesquisar }}" class="kt-btn kt-btn-outline">
                                {{ $titleBtnPesquisar }}
                            </button>


                        </form>

                    </div>



                    @if($routeCreatenew !== '/')
                        <div class="">
                            <a type="button" href="{{ $routeCreatenew }}" class="kt-btn kt-btn-outline">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="lucide lucide-plus" aria-hidden="true">
                                    <path d="M5 12h14"></path>
                                    <path d="M12 5v14"></path>
                                </svg>{{ $textCreateNew }}
                            </a>
                        </div>
                    @endif

                </div>

                <div class="kt-card-content">
                    <div class="grid" data-kt-datatable="true" data-kt-datatable-page-size="10">
                        <div class="kt-scrollable-x-auto">
                            <table class="kt-table table-auto kt-table-border" data-kt-datatable-table="true"
                                id="security_log_table">
                                <thead>
                                    <tr>
                                        @foreach ($columns as $label => $field)
                                            <th class="min-w-[130px] lg:min-w-[200px]">
                                                <span class="kt-table-col">
                                                    <span class="kt-table-col-label">{{ $label }}</span>
                                                </span>
                                            </th>
                                        @endforeach
                                        @if (!empty($actions))
                                            <th class="w-[60px] lg:w-[100px]">Ações</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($data  as $item)
                                        <tr class="{{$item['color'] ?? ''}}">
                                            @foreach ($columns as $label => $field)
                                                <td>
                                                    {{ $item[$field] ?? '-' }}
                                                </td>
                                            @endforeach
                                            @if (!empty($actions))
                                                @if(sizeof($actions) > 1)
                                                    <td>
                                                        <div data-kt-dropdown="true" data-kt-dropdown-trigger="click">
                                                            <button class="kt-btn" data-kt-dropdown-toggle="true">
                                                                Gerenciar<svg xmlns="http://www.w3.org/2000/svg"
                                                                    width="24" height="24" viewBox="0 0 24 24"
                                                                    fill="none" stroke="currentColor" stroke-width="2"
                                                                    stroke-linecap="round" stroke-linejoin="round"
                                                                    class="lucide lucide-chevron-down" aria-hidden="true">
                                                                    <path d="m6 9 6 6 6-6"></path>
                                                                </svg>
                                                            </button>
                                                            <div class="kt-dropdown-menu w-52" data-kt-dropdown-menu="true">
                                                                <ul class="kt-dropdown-menu-sub">
                                                                    @foreach ($actions as $actionName => $actionRoute)
                                                                        <li>
                                                                            <a href="{{ is_callable($actionRoute) ? $actionRoute($item) : $actionRoute }}"
                                                                                class="kt-dropdown-menu-link">
                                                                                @switch($actionName)
                                                                                    @case('Logar Como')
                                                                                        <i class="ki-filled ki-security-user"></i>
                                                                                    @break

                                                                                    @case('Editar')
                                                                                        <i class="ki-filled ki-setting-4"></i>
                                                                                    @break

                                                                                    @case('Deletar')
                                                                                        <i class="ki-filled ki-delete-folder"></i>
                                                                                    @break
                                                                                @endswitch
                                                                                {{ $actionName }}
                                                                            </a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                @else
                                                <td>
                                                    @foreach ($actions as $actionName => $actionRoute)
                                                            <a href="{{ is_callable($actionRoute) ? $actionRoute($item) : $actionRoute }}"
                                                                class="kt-btn">
                                                                @switch($actionName)
                                                                    @case('Logar Como')
                                                                        <i class="ki-filled ki-security-user"></i>
                                                                    @break

                                                                    @case('Editar')
                                                                        <i class="ki-filled ki-setting-4"></i>
                                                                    @break

                                                                    @case('Deletar')
                                                                        <i class="ki-filled ki-delete-folder"></i>
                                                                    @break
                                                                @endswitch
                                                                {{ $actionName }}
                                                            </a>
                                                    @endforeach
                                                </td>

                                                @endif

                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($columns) + (!empty($actions) ? 1 : 0) }}"
                                                class="py-4 text-center">
                                                Nenhum registro encontrado.
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($paginator !== null)
                                <div
                                    class="kt-card-footer justify-center md:justify-between flex-col md:flex-row gap-5 text-secondary-foreground text-sm font-medium">
                                    {{ $paginator->appends(request()->query())->links() }}
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
                @if(session('success'))
                    <div class="alert alert-success">
                        <x-messages.alert type="success" :message="session('success')"></x-messages.alert>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-destructive">
                        <x-messages.alert type="sucdestructivecess" :message="session('error')"></x-messages.alert>
                    </div>
                @endif
            </div>
        </div>
    </div>


  @push('scripts')
    <script>
        $(document).ready(function() {
            const searchForm = document.getElementById('search-form');
            const searchButton = document.getElementById(`{{ $idBtnPesquisar }}`);
            const baseRoute = `{{ $routeSearch }}`;

            /**
             * Serializa todos os campos do formulário (input, select, textarea)
             * retornando uma query string apenas com campos não vazios.
             */
            function serializeForm(form) {
                const formData = new FormData(form);
                const params = new URLSearchParams();

                for (const [name, value] of formData.entries()) {
                    // Ignora campos sem "name", vazios ou com apenas espaços
                    if (!name || value === null || value === undefined) continue;
                    const trimmed = String(value).trim();
                    if (trimmed === '') continue;

                    params.append(name, trimmed);
                }

                return params.toString();
            }

            /**
             * Redireciona para a URL com os parâmetros de busca.
             */
            function performSearch() {
                const queryString = serializeForm(searchForm);
                const separator = baseRoute.includes('?') ? '&' : '?';
                const url = queryString
                    ? `${baseRoute}${separator}${queryString}`
                    : baseRoute;

                window.location.href = url;
            }

            // Clique no botão de pesquisa
            searchButton.addEventListener('click', function(event) {
                event.preventDefault();
                performSearch();
            });

            // Submit do formulário (caso o usuário pressione Enter dentro de um input)
            searchForm.addEventListener('submit', function(event) {
                event.preventDefault();
                performSearch();
            });
        });
    </script>
@endpush
