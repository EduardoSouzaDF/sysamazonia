@php($guideState = app(\App\Services\CommissionGuides::class)->state(auth()->user()))
@if ($guideState)
    <div data-commission-guides>
        <button type="button" class="kt-btn kt-btn-outline" data-guide-help>Rever orientações</button>
        <script type="application/json" data-guide-config>{!! json_encode([...$guideState, 'url' => route('commission-guides.update'), 'csrf' => csrf_token(), 'page' => request()->route()?->getName()], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    </div>
@endif
