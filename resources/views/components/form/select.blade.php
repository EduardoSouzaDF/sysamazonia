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
    'nameOld' => '',
    'value' => '',
])
@php

    $value = $value !== '' ? $value : old($nameOld);
@endphp
<div class="kt-form-item {{ $class }}">
    @if ($label)
        <label class="kt-form-label">{{ $label }} {{ $required == 'true' ? '*' : '' }}</label>
    @endif

    <sl-select name="{{ $name }}" value="{{ $value }}" @if ($value) filled @endif
        @if ($multiple) multiple clearable @endif>
        @foreach ($options as $key => $option)
            <sl-option value="{{ $key }}">{{ $option }}</sl-option>
        @endforeach
    </sl-select>


    @if ($errors->has($name))
        @foreach ($errors->get($name) as $key => $value)
            <x-messages.alert message="{{ $value }}" />
        @endforeach
    @endif
</div>

@push('scripts')
@endpush
