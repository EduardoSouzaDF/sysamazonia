@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        .note-editable {
            background-color: white;
        }
    </style>
@endpush
@php
    use Carbon\Carbon;
    $object = $edition ?? null;
    $titulo = isset($object) ? 'Gerenciamento de Edição' : 'Criação de Edição';
    $route = isset($object) ? route('admin.editions.update', $object->id) : route('admin.editions.store');
    $titleBtn = isset($object) ? 'Salvar Alterações' : 'Criar Edição';
    $deleteRoute = isset($object) ? route('admin.editions.destroy', ['edition' => $object->id]) : '';
    $checked = isset($object) ? $object->is_registration_active : false;

@endphp

@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create :titulo="$titulo" form-id='create-edition-form' form-action="{{ $route }}"
        space={{ false }} btn-cancel-title='Cancelar' btn-cancel-route="{{ route('admin.editions.index') }}"
        :btn-submit-title="$titleBtn" :btn-delete=isset($object) btn-delete-route="{{ $deleteRoute }}" :put="isset($edition)"
        btn-delete-id='btnDeleteEdition'>

        <div class="w-full">
            <x-form.input type="text" class="mb-4" label="Título" placeholder="Ex: Edição 2025" name="title"
                :value="isset($object) ? $object->title : null" required="true" description="Informe o link do site da instituição" />
        </div>
        <div class="w-full">

            <div class="kt-form-item  ">
                <label class="kt-form-label">Regulamento *</label>

                <div class="kt-form-control">
                    <input type="hidden" value="{{ isset($object) ? $object->regulation : old('regulation') }}"
                        name="regulation" />
                    <div id="editor" class="kt-input kt-input-textarea" style="height: 300px;">
                        {!! old('regulation') !!}
                    </div>
                </div>

                @if ($errors->has('regulation'))
                    @foreach ($errors->get('regulation') as $key => $value)
                        <x-messages.alert message="{{ $value }}" />
                    @endforeach
                @endif

            </div>

        </div>
        </div>


        <div class="flex w-full gap-4 mt-4">
            <div class="w-1/2">
                <div class="kt-form-item">
                    <label class="kt-form-label">Regulamento (PDF)</label>
                    <div class="kt-form-control">
                        <input type="file" name="regulation_file" class="kt-input" accept=".pdf" />

                        @if (isset($object) && $object->regulation_file_path)
                            @php
                                $fileName = basename($object->regulation_file_path);
                            @endphp
                            <a target="_blank" href="{{ route('editions.regulations.show', ['file' => $fileName]) }}"
                                class="kt-btn kt-btn-outline">
                                Ver/Fazer Download do Arquivo PDF
                            </a>
                        @endif
                    </div>
                    <div class="kt-form-description">
                        Arquivo PDF com no máximo 5MB.
                    </div>
                    @if ($errors->has('regulation_file'))
                        @foreach ($errors->get('regulation_file') as $key => $value)
                            <x-messages.alert message="{{ $value }}" />
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <div class="flex w-full gap-4 mt-4">
            <div class="w-1/2">
                <!-- Data de outorga -->
                <x-form.input type="date" :value="isset($object) && $object->grant_date
                    ? \Carbon\Carbon::parse($object->grant_date)->format('Y-m-d')
                    : old('grant_date')" name="grant_date" label="Data de Outorga"
                    :required="true" />
            </div>
            <div class="w-1/2">
                <!-- Data de julgamento -->
                <x-form.input type="date" :value="isset($object) && $object->judgment_date
                    ? \Carbon\Carbon::parse($object->judgment_date)->format('Y-m-d')
                    : old('judgment_date')" name="judgment_date" label="Data de Julgamento"
                    :required="true" />
            </div>
        </div>

        <div class="flex w-full gap-4 mt-4">
            <div class="w-1/2">
                <!-- Data de início de inscrição -->
                <x-form.input :value="isset($object) && $object->registration_start
                    ? \Carbon\Carbon::parse($object->registration_start)->format('Y-m-d')
                    : old('registration_start')" type="date" placeholder="__/__/____" name="registration_start"
                    label="Início das Inscrições" :required="true" />
            </div>
            <div class="w-1/2">
                <!-- Data de término de inscrição -->
                <x-form.input :value="isset($object) && $object->registration_end
                    ? \Carbon\Carbon::parse($object->registration_end)->format('Y-m-d')
                    : old('registration_end')" type="date" name="registration_end" label="Término das Inscrições"
                    :required="true" />
            </div>
        </div>


        <div class="flex w-full gap-4 mt-4">
            <div class="w-1/2">
                <x-form.input
                    value="{{ isset($object) ? $object->applications_per_candidate : old('applications_per_candidate') }}"
                    type="number" name="applications_per_candidate" label="Máximo de Inscrições por Candidato"
                    placeholder="Ex: 3" :required="false" min="1" />
            </div>
            <div class="w-1/2">
                <div class="kt-form-group">
                    <div class="flex items-center gap-2">

                        <input type="checkbox" value='1' name="is_registration_active" @checked(isset($object) && $object->is_registration_active)
                            id="switch">
                        <label class="kt-label" for="switch">
                            Inscrições Ativas
                        </label>
                    </div>
                    <div class="kt-form-description mt-2">
                        Habilitar novas inscrições para esta edição.
                    </div>

                </div>

            </div>

        </div>

        <sl-alert class="hidden w-full alter-success" variant="success" open>
            <sl-icon slot="icon" name="check2-circle"></sl-icon>
            <strong>Operação realizada.</strong><br />
            Edição Excluída
        </sl-alert>

    </x-pages.crud.create>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <sl-dialog label="Antenção!" class="dialog-overview">
        Deseja realmente excluir a edição ?
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

            var oldRegulation = $("input[name='regulation']").val();

            $('#editor').summernote({
                placeholder: 'Hello stand alone ui',
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


            $('#create-edition-form').on('submit', function() {
                var markupStr = $('#editor').summernote('code');
                $('input[name="regulation"]').val(markupStr);
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
                                "{{ route('admin.editions.index') }}";

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
