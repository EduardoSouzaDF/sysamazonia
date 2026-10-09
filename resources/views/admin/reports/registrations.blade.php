@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed py-8 space-y-6">
    <header>
        <h1 class="text-2xl font-semibold">Relatórios de inscrições</h1>
        <p class="text-secondary-foreground mt-2">Gere e confira os relatórios de habilitados (incluindo avaliados), rejeitados ou agraciados antes de baixar o arquivo Markdown.</p>
    </header>

    @if($errors->any())
        <div class="kt-alert kt-alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <section class="kt-card p-6 space-y-5" aria-labelledby="report-options-title">
        <div>
            <h2 id="report-options-title" class="font-semibold text-lg">Gerar relatório sincronamente</h2>
            <p class="text-sm text-secondary-foreground mt-1">A geração é feita no momento do clique e consulta o banco somente para leitura.</p>
        </div>
        <form method="POST" action="{{ route('admin.registration-reports.generate') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <label class="space-y-2">
                <span class="block font-medium">Status</span>
                <select name="status" class="kt-select min-w-56" required>
                    <option value="3" @selected(old('status', $report['filename'] ?? '') === 'relatorio-habilitados.md' || old('status') == 3)>Habilitados</option>
                    <option value="2" @selected(old('status', $report['status'] ?? null) == 2)>Rejeitados</option>
                    <option value="5" @selected(old('status') == 5 || ($report['filename'] ?? '') === 'relatorio-agraciados.md')>Agraciados</option>
                </select>
            </label>
            <button class="kt-btn kt-btn-primary" name="action" value="preview"><i class="ki-filled ki-eye"></i> Gerar e visualizar</button>
            <button class="kt-btn kt-btn-light" name="action" value="download"><i class="ki-filled ki-file-down"></i> Gerar e baixar</button>
        </form>
    </section>

    @isset($report)
        <section class="grid sm:grid-cols-3 gap-4" aria-label="Resumo do relatório">
            <div class="kt-card p-5"><span class="text-sm text-secondary-foreground">Registros</span><strong class="block text-2xl mt-2">{{ $report['total'] }}</strong></div>
            <div class="kt-card p-5"><span class="text-sm text-secondary-foreground">Modalidades</span><strong class="block text-2xl mt-2">{{ count($report['by_modality']) }}</strong></div>
            <div class="kt-card p-5"><span class="text-sm text-secondary-foreground">Campos não informados</span><strong class="block text-2xl mt-2">{{ $report['missing'] }}</strong></div>
        </section>

        <section class="kt-card p-6 space-y-4" aria-labelledby="report-preview-title">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div><h2 id="report-preview-title" class="font-semibold text-lg">{{ $report['title'] }}</h2><p class="text-sm text-secondary-foreground">Prévia completa do arquivo {{ $report['filename'] }}</p></div>
                <form method="POST" action="{{ route('admin.registration-reports.generate') }}">@csrf<input type="hidden" name="status" value="{{ $report['status'] }}"><button class="kt-btn kt-btn-primary" name="action" value="download"><i class="ki-filled ki-file-down"></i> Baixar Markdown</button></form>
            </div>
            <pre class="report-preview rounded-lg p-5 overflow-auto whitespace-pre-wrap text-sm" tabindex="0">{{ $report['markdown'] }}</pre>
        </section>
    @endisset
</div>
@endsection

@push('styles')
<style>
.report-preview{max-height:65vh;overflow-wrap:anywhere;color:#fff;background:#111827}
@media print{.report-preview{color:#000 !important;background:#fff !important;max-height:none;overflow:visible}}
</style>
@endpush
