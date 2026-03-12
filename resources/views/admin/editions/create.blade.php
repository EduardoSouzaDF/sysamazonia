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

    $titulo = isset($edition) ? 'Gerenciamento de Edição' : 'Criação de Edição';
    $route = isset($edition) ? route('admin.editions.update', $edition->id) : route('admin.editions.store');
    $titleBtn = isset($edition) ? 'Salvar Alterações' : 'Criar Edição';
@endphp

@extends('admin.content')
@section('maincontent')
    <x-pages.crud.create titulo={{ $titulo }} form-id='create-edition-form' form-action="{{ $route }}"
        space={{ false }} btn-cancel-title='Cancelar' btn-cancel-route="{{ route('admin.editions.index') }}"
        btn-submit-title={{ $titleBtn }} btn-delete={{isset($edition)}}  
        btn-delete-route="{{ route('admin.editions.destroy', ['edition' => $edition->id]) }}" >

        <div class="w-full">
            <x-form.input type="text" class="mb-4" label="Título" placeholder="Ex: Edição 2025" name="title"
                :value="isset($edition) ? $edition->title : null" required="true" description="Informe o link do site da instituição" />
        </div>
        <div class="w-full">

            <div class="kt-form-item  ">
                <label class="kt-form-label">Regulamento *</label>

                <div class="kt-form-control">
                    <input type="hidden" value="{{ isset($edition) ? $edition->regulation : old('regulation') }}"
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

                        @if (isset($edition) && $edition->regulation_file_path)
                            @php
                                $fileName = basename($edition->regulation_file_path);
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
                <x-form.input type="date" :value="isset($edition) && $edition->grant_date
                    ? \Carbon\Carbon::parse($edition->grant_date)->format('Y-m-d')
                    : old('grant_date')" name="grant_date" label="Data de Outorga"
                    :required="true" />
            </div>
            <div class="w-1/2">
                <!-- Data de julgamento -->
                <x-form.input type="date" :value="isset($edition) && $edition->judgment_date
                    ? \Carbon\Carbon::parse($edition->judgment_date)->format('Y-m-d')
                    : old('judgment_date')" name="judgment_date" label="Data de Julgamento"
                    :required="true" />
            </div>
        </div>

        <div class="flex w-full gap-4 mt-4">
            <div class="w-1/2">
                <!-- Data de início de inscrição -->
                <x-form.input :value="isset($edition) && $edition->registration_start
                    ? \Carbon\Carbon::parse($edition->registration_start)->format('Y-m-d')
                    : old('registration_start')" type="date" placeholder="__/__/____" name="registration_start"
                    label="Início das Inscrições" :required="true" />
            </div>
            <div class="w-1/2">
                <!-- Data de término de inscrição -->
                <x-form.input :value="isset($edition) && $edition->registration_end
                    ? \Carbon\Carbon::parse($edition->registration_end)->format('Y-m-d')
                    : old('registration_end')" type="date" name="registration_end" label="Término das Inscrições"
                    :required="true" />
            </div>
        </div>


        <div class="flex w-full gap-4 mt-4">
            <div class="w-1/2">
                <x-form.input
                    value="{{ isset($edition) ? $edition->applications_per_candidate : old('applications_per_candidate') }}"
                    type="number" name="applications_per_candidate" label="Máximo de Inscrições por Candidato"
                    placeholder="Ex: 3" :required="false" min="1" />
            </div>
            <div class="w-1/2">
                <div class="kt-form-group">
                    <div class="flex items-center gap-2">
                        <input type="hidden" name="is_registration_active">
                        <input class="kt-switch" type="checkbox" id="switch">
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

    </x-pages.crud.create>
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
                console.log(markupStr);
            });
        });
    </script>
@endpush
