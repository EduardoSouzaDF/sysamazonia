@props(['iterator'])
@props(['message'])


@if ($errors->has($iterator))
    @foreach ($errors->get($iterator) as $error)
        <div class="kt-alert" role="alert" aria-labelledby="alert_heading" aria-describedby="alert_message" id="alert">
            <div class="kt-alert-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-triangle-alert size-6 text-destructive" aria-hidden="true">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                </svg>
            </div>
            <div class="kt-alert-title flex items-center gap-1.5" id="alert_heading">
                <span class="font-semibold">{{ $error }}</span>
            </div>
            <button class="kt-alert-close" data-kt-dismiss="#alert" aria-label="Close alert">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-x" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
    @endforeach
@endif
