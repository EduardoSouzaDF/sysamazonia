@extends('admin.content')

@section('maincontent')
<div id="ai-panel" class="kt-container-fixed py-8 space-y-6">
    <header class="ai-heading">
        <div><h1 class="text-2xl font-semibold">Configurações de IA</h1><p class="text-secondary-foreground mt-2">Acompanhe as avaliações, configure o modelo e gerencie as operações.</p></div>
        <div class="ai-provider"><span class="kt-badge kt-badge-outline">{{ config('ai_evaluation.providers.'.$settings->provider, $settings->provider) }}</span><span class="text-sm">{{ $settings->model ?: 'Modelo não configurado' }}</span></div>
    </header>
    <nav class="kt-tabs kt-tabs-line ai-tabs" aria-label="Seções de configurações de IA">
        @foreach(['progress' => ['Andamento', 'chart-simple'], 'evaluations' => ['Avaliações', 'notepad-edit'], 'indications' => ['Indicações', 'award'], 'problems' => ['Problemas', 'information-2'], 'configuration' => ['Configuração IA', 'setting-2'], 'executions' => ['Auditoria', 'time']] as $key => [$label, $icon])
            <a class="kt-tab-toggle {{ $tab === $key ? 'active' : '' }}" href="{{ route('admin.ai-settings.index', ['tab' => $key]) }}" @if($tab === $key) aria-current="page" @endif><i class="ki-filled ki-{{ $icon }}" aria-hidden="true"></i>{{ $label }}</a>
        @endforeach
    </nav>
    @if(session('success')) <div class="kt-alert kt-alert-success" role="status">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="kt-alert kt-alert-danger" role="alert">{{ session('error') }}</div> @endif
    @if($errors->any()) <div class="kt-alert kt-alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
    @include('admin.ai-settings.'.['progress' => 'metrics', 'evaluations' => 'evaluations', 'indications' => 'indications', 'problems' => 'problems', 'configuration' => 'configuration', 'executions' => 'executions'][$tab])
</div>
@endsection

@push('styles')
<style>
    body:has(#ai-panel) > .flex.grow, .kt-wrapper:has(#ai-panel), #content:has(#ai-panel) { min-width: 0; }
    #ai-panel .grid > *, #ai-panel .grow { min-width: 0; }
    #ai-panel .ai-heading, #ai-panel .ai-provider, #ai-panel .ai-save-bar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    #ai-panel .ai-provider { justify-content: flex-start; overflow-wrap: anywhere; }
    #ai-panel .ai-tabs { overflow-x: auto; flex-wrap: nowrap; }
    #ai-panel .ai-tabs a { white-space: nowrap; gap: .5rem; padding: 1rem; }
    #ai-panel .ai-tabs a[aria-current="page"] { color: var(--primary); border-bottom-color: var(--primary); }
    #ai-panel .ai-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; }
    #ai-panel .ai-stat { padding: 1.25rem; border-top: 3px solid var(--color-primary, #2563eb); }
    #ai-panel .ai-stat[data-status="failed"] { border-color: var(--color-danger, #dc2626); }
    #ai-panel .ai-stat[data-status="completed"] { border-color: var(--color-success, #16a34a); }
    #ai-panel .ai-stat strong { display: block; font-size: 1.75rem; margin-top: .5rem; }
    #ai-panel .ai-prompt { border: 1px solid var(--color-border, #e5e7eb); border-radius: .75rem; padding: 1rem; }
    #ai-panel summary { cursor: pointer; }
    #ai-panel .ai-prompt textarea { min-height: 22rem; resize: vertical; line-height: 1.6; }
    #ai-panel .ai-save-bar { position: sticky; bottom: 1rem; background: var(--color-background, white); padding: 1rem; border: 1px solid var(--color-border, #e5e7eb); border-radius: .75rem; z-index: 2; }
    #ai-panel .ai-error { white-space: normal; overflow-wrap: anywhere; max-width: 28rem; }
    #ai-panel .ai-settings-form label { display: block; }
    #ai-panel .ai-settings-form [hidden] { display: none; }
    #ai-panel .ai-settings-form label:has(.kt-switch) { display: flex; }
    #ai-panel .ai-settings-form small { display: block; margin-top: .35rem; color: var(--color-secondary-foreground); }
    #ai-status-chart, #ai-daily-chart { max-width: 100%; overflow: hidden; }
    @media (max-width: 640px) { #ai-panel .ai-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } #ai-panel .ai-save-bar { position: static; } }
</style>
@endpush
