@php
    use App\Enum\RolesEnum;
@endphp
@extends('admin.content')
@section('maincontent')
    <div class="kt-container-fixed">
        <div class="kt-card min-w-full">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Criação de Usuário
                </h3>
            </div>
            <form method="post" action="{{ route('admin.users.store') }}" class="kt-form">
                <div class="kt-card-content" data-kt-tabs-hidden-class="hidden" data-kt-tabs-active-class="active">
                    <div class="space-y-3">
                        <div class="kt-tabs kt-tabs-line" data-kt-tabs="true">
                            <button class="kt-tab-toggle active" data-kt-tab-toggle="#tab_1_1">
                                Informações Pessoais</button>
                            <button class="kt-tab-toggle hidden" id='tab_1_2_button' data-kt-tab-toggle="#tab_1_2">Dados
                                da instituição</button>
                        </div>

                        @csrf
                        <div class="text-sm">
                            <div class="" id="tab_1_1">
                                @include('admin.users.partial.form-personal', [
                                    'roles' => $roles,
                                    'errors' => $errors->getMessages(),
                                ])
                            </div>
                            <div class="hidden" id="tab_1_2">
                                Tab
                                <!-- -->2<!-- -->
                                content.
                            </div>

                        </div>

                    </div>
                </div>
                <div class="kt-card-footer justify-end gap-4">
                    <a href="{{ route('admin.users.index') }}" class="kt-btn kt-btn-outline">Cancelar</a>
                    <button type="submit" class="kt-btn">Salvar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#roles').on('change', function() {
                var selectedRoles = $(this).val();

                if (selectedRoles.includes('4')) {
                    $('#tab_1_2_button').removeClass('hidden');
                } else {
                    $('#tab_1_2_button').addClass('hidden');
                }



            });
        });
    </script>
@endpush
