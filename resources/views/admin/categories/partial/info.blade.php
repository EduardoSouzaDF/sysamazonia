@props([
    'errors' => null,
    'modalities' => [],
    'object' => null,
])

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        .note-editable {
            background-color: white;
        }
    </style>
@endpush
<div class="flex w-full gap-4 mt-4  ">
    <div class="w-4/12">
        <x-form-select label="Modalidade" nameOld="modality_id" name="modality_id" id="modality_select"
            value="{{ isset($object) ? $object->modality_id : '' }}" :multiple="false" required="true"
            :options="$modalities" />
    </div>


    <div class="w-4/12">
        <x-form.input type="text" class="mb-4" label="Título" name="title" :value="isset($object) ? $object->title : null" required="true" />
    </div>

    <div class="w-4/12">
        <x-form.input type="text" class="mb-4" label="Sigla" name="acronym" :value="isset($object) ? $object->acronym : null" required="true" />
    </div>
</div>

<div class="w-full gap-4 mt-4  ">
    <div class="kt-form-item  ">
        <label class="kt-form-label">Descrição *</label>

        <div class="kt-form-control">
            <input type="hidden" value="{{ isset($object) ? $object->description : old('description') }}"
                name="description" />
            <div id="editor" class="kt-input kt-input-textarea" style="height: 300px;">
                {!! old('description') !!}
            </div>
        </div>

        @if ($errors)
            @foreach ($errors as $key => $value)
                <x-messages.alert message="{{ $value }}" />
            @endforeach
        @endif

    </div>

</div>






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


        });
    </script>
@endpush
