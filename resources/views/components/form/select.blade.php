<!-- resources/views/components/form/select.blade.php -->

@props([
    'class' => '',
    'options' => [],
    'label' => '',
    'placeholder' => 'Selecione uma opção...',
    'name' => '',
    'id' => '',
    'search' => false,
    'multiple' => true,
    'maxSelections' => 1,
    'required' => true,
])

<div class="kt-form-item {{ $class }}">
    @if ($label)
        <label class="kt-form-label">{{ $label }} {{ $required ? '*' : '' }}</label>
    @endif

    <select class="kt-select {{ $class }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        multiple="{{ $multiple ? 'true' : 'false' }}"
        data-kt-select="true"
        data-kt-select-multiple="{{ $multiple ? 'true' : 'false' }}" data-kt-select-max-selections="{{ $maxSelections }}"
        data-kt-select-config='{
			"displaySeparator": " | "
		}'
        data-kt-select-enable-search="{{ $search ? 'true' : 'false' }}"
        data-kt-select-search-placeholder="{{ $placeholder }} ..." data-kt-select-placeholder="{{ $placeholder }}"
        data-kt-select-config='{
			"optionsClass": "kt-scrollable overflow-auto max-h-[250px]"
		}'
        name="{{ $name }}" id="{{ $id }}">
        @foreach ($options as $key => $option)
            <option value="{{ $key }}">{{ $option }}</option>
        @endforeach


    </select>

    @if($errors->has($name))
        @foreach ($errors->get($name) as $key => $value)
            <div class="kt-form-message text-danger">{{ $value }}</div>
        @endforeach
     @endif


</div>

