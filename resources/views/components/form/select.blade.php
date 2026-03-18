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
        <label class="kt-form-label">{{ $label }} {{ $required ? '*' : '' }}</label>
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
    <script type="text/javascript">
        $('document').ready(function() {
            const selectElement = document.getElementById('{{ $id }}');
            const instance = KTSelect.getInstance(selectElement) ?? KTSelect.getOrCreateInstance(selectElement);
            if (value !== '') {
                const options = ['9'].map((v) => selectElement.querySelector(`option[value="${v}"]`)).filter(
                    Boolean);
                instance.setSelectedOptions([9]);
            }
        });
    </script>
@endpush
