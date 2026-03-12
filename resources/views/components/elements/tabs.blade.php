@props([
    'tabs' => [],
])

<div class="kt-tabs kt-tabs-line" data-kt-tabs="true">
    @foreach ($tabs as $tab)
        <button
            @class([
                'kt-tab-toggle',
                'active' => $tab['active']  ,
                'hidden' => $tab['hidden'] ?? false,
            ]) 
            data-kt-tab-toggle="#{{ $tab['tabId'] }}" 
            id="{{ $tab['buttonId'] }}"> {{ $tab['titulo'] }}</button>
    @endforeach
</div>

<div class="text-sm">
    @foreach ($tabs as $tab)
        @if ($tab['include'])
            <div
                @class([
                    'hidden' => !$tab['active'],
                    'transition-opacity duration-700'
                ]) 
                id="{{  $tab['tabId'] }}">
                @include($tab['include'], $tab['includeData'] ?? [])
            </div>
        @endif
    @endforeach
</div> 