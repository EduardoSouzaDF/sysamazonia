@props([
    'message' => 'Mensagem de alerta',
    'type' => 'warning', // primary , success , info , destructive , warning
    'id' => uniqid(),
])


<div class="kt-alert kt-alert-mono kt-alert-{{ $type }}" id="alert_{{ $id }}">
    <div class="kt-alert-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="lucide lucide-info" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 16v-4"></path>
            <path d="M12 8h.01"></path>
        </svg>
    </div>
    <div class="kt-alert-title">{{ $message }}</div>
    <div class="kt-alert-toolbar">
        <div class="kt-alert-actions">
           <button class="kt-alert-close" data-kt-dismiss="#alert_{{ $id }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-x" aria-hidden="true">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
    </div>
</div>
