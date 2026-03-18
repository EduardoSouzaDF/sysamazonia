@props([
    'errors' => null,
    'modalities' => [],
    'object' => null,
])

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

        @if (isset($errors['description']))
            @foreach ($errors['description'] as $key => $value)
                <x-messages.alert message="{{ $value }}" />
            @endforeach
        @endif
    </div>
</div>
