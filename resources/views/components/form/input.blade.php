@props([
    'type' => 'text',
    'placeholder' => '',
    'label' => '',
    'message' => '',
    'valid' => true,
    'description' => '',
    'old' => '',
    'class' => '',
    'name' => '',
    'id' => '',
    'required' => true,
    'value' => null,
])

<div class="kt-form-item {{ $class }}">
    <label class="kt-form-label">{{ $label }} {{ $required ? '*' : '' }}</label>
    <div class="kt-form-control">
        <input
            type="{{ $type }}"
            value="{{ !is_null($value) ? $value : old($name) }}"
            name="{{ $name }}"
            id="{{ $id }}"
            aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
            class="kt-input" placeholder="{{ $placeholder }}" @required($required) />
    </div>
    <div class="kt-form-description">{{ $description }}</div>

    @if ($errors->has($name))
        @foreach ($errors->get($name) as $key => $value)
            <x-messages.alert message="{{ $value }}" />
        @endforeach
    @endif

</div>
