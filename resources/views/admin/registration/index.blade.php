<?php

use App\Enum\RegistrationStatusEnum;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
$columns = [
    'Edição' => 'edition',
    'Categoria' => 'category',
    'Autor' => 'candidate_name',
    'Titulo' => 'title',
    'Avaliações / Nota' => 'rating',
    'Indicações' => 'nominations',
    'Status' => 'status',
];

$user = Auth::user();


$actions = [
    'Visualizar' => function($registration){
        return  route('admin.registration.show', ['id' => $registration['id'],'type' => $registration['type']]);
    },

];



$formattedData = array_map(function ($registration) {
    /** @var \App\Models\Registration $registrationModel */
    $registrationModel = $registration;
    $honorific = $registrationModel->category->is_honorific;
    $classification = !$honorific ? match ($registrationModel->getTextEvaluationAvg()) {
        'Não Recomendado' => 'not-recommended',
        'Meritório' => 'meritorious',
        'Recomendado' => 'recommended',
        default => '',
    } : '';
    $statusClass = match (true) {
        (int) $registrationModel->status === RegistrationStatusEnum::Rejeitado->value => 'registration-status-rejected',
        $honorific && (int) $registrationModel->status === RegistrationStatusEnum::Habilitado->value => 'registration-status-recommended',
        $classification !== '' => 'registration-status-'.$classification,
        default => '',
    };
    $rating = 'Não se Aplica';
    if (!$honorific) {
        $note = $registrationModel->getEvaluationAvgPercentage();
        $noteMarkup = $note !== null ? '<strong class="registration-note registration-note-'.$classification.'">'.e((string) $note).'</strong>' : '';
        $rating = new HtmlString(sizeof($registrationModel->opinions).' | '.$registrationModel->category->requiredEvaluations().' ( '.$noteMarkup.' )');
    }

    return [
        'edition' => $registrationModel->category->modality->edition->title,
        'category' => $registrationModel->category->acronym."-".$registrationModel->category->title,
        'title' => $registrationModel->title ?? $registrationModel->name,
        'candidate_name' => $registrationModel->candidate->nome,
        'rating' => $rating,
        'nominations' => !$registrationModel->category->is_honorific ? sizeof($registrationModel->indications).' | '.$registrationModel->category->requiredIndications() : 'Não se Aplica',
        'status' => $registrationModel->statusName(), // ✅ Usando o método do model
        'id' => $registrationModel->id,
        'type' => get_class($registration),
        'cell_classes' => ['status' => $statusClass],
    ];
}, $list->items()); // Usar items() ao invés de toArray()['data']

$editions = $editions
    ->mapWithKeys(function ($role) {
        return [$role->id => $role->title];
    })
    ->toArray();

$editions[''] = 'Selecione a Edição';

$status = Registration::getStatusArray();
$status[''] = 'Selecione o Status';

if(!$user->isAdmin()){

    unset($columns['Autor']);
    unset($columns['Avaliação']);
    unset($columns['Indicações']);

    $formattedData = array_map(function ($registration) {
        unset(
            $registration['candidate_name'],
            $registration['rating'],
            $registration['nominations'],
            );
        return $registration;
    }, $formattedData);

     $actions = [];
    
     if ($user->isEvaluator()) {
        $actions['Avaliar'] = function ($registration) {
            return route('admin.registration.show', [
                'id' => $registration['id'],
                'type' => $registration['type'],
            ]);
        };
    }

    if ($user->isIndicator()) {
        $actions['Indicar'] = function ($registration) {
            return route('admin.registration.show', [
                'id' => $registration['id'],
                'type' => $registration['type'],
            ]);
        };
    }
    

}

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Inscrições" subtitulo="Inscrições por edição ativa" searchPlaceholder="Procurar por categoria, autor, CPF, título, nome do indicado ou status ..."
        :searchValue="request('search', '')"
        titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button" routeSearch="{{ route('admin.registration.index') }}"
        :columns="$columns" :actions="$actions" :data="$formattedData" :paginator="$list" >

    <x-slot:summary>
        <section class="registration-summary" aria-label="Resumo das inscrições">
            <div class="registration-summary-grid">
                @foreach (['people' => 'Autores e indicadores únicos', 'total' => 'Total de inscrições', 'regular' => 'Inscrições regulares', 'honorary' => 'Indicações honoríficas'] as $key => $label)
                    <div class="kt-card registration-summary-card" data-summary="{{ $key }}">
                        <span class="text-secondary-foreground text-sm">{{ $label }}</span>
                        <strong>{{ number_format($summary[$key], 0, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>
            <p class="text-secondary-foreground text-xs mt-2">Totais dos resultados filtrados, incluindo todas as páginas. Cada responsável é contado uma única vez em autores e indicadores.</p>
        </section>
    </x-slot:summary>

    <x-slot:searchForm>

        <div class="w-full sm:w-64 min-w-0">
            <select name="category" id="registration-category" class="kt-select" aria-label="Categoria">
                <option value="">Todas as categorias</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category', '') === (string) $category->id)>{{ $category->acronym }} — {{ $category->title }}</option>
                @endforeach
            </select>
        </div>

    @if($user->isAdmin())
        <div class="registration-filter">
            <select name="edition" id="editions" class="kt-select" aria-label="Edição">
                @foreach ($editions as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('edition', '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="registration-filter">
            <select name="status" id="status" class="kt-select" aria-label="Status">
                @foreach ($status as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('status', '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endif



    </x-slot>

    </x-pages.index>
@endsection

@push('styles')
    @vite('resources/css/registrations.css')
@endpush
