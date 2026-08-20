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

    return [
        'edition' => $registrationModel->category->modality->edition->title,
        'category' => $registrationModel->category->acronym."-".$registrationModel->category->title,
        'title' => $registrationModel->title ?? $registrationModel->name,
        'candidate_name' => $registrationModel->candidate->nome,
        'rating' => !$registrationModel->category->is_honorific ? sizeof($registrationModel->opinions).' | '.$registrationModel->category->evaluations_count.' ( '.$registrationModel->getEvaluationAvgPercentage()." ) " : 'Não se Aplica',
        'nominations' => ' 0 | '. $registrationModel->category->modality->edition->applications_per_candidate,
        'status' => $registrationModel->statusName(), // ✅ Usando o método do model
        'id' => $registrationModel->id,
        'type' => get_class($registration),
        'color' =>  !$registrationModel->category->is_honorific ?
                  match($registrationModel->getTextEvaluationAvg()) {
            'Não Recomendado' => 'bg-red-300',
            'Meritório' => 'bg-yellow-300',
            'Recomendado' => 'bg-green-300',
            default => ''
        }: ''
    ];
}, $list->items()); // Usar items() ao invés de toArray()['data']

$editions = $editions
    ->mapWithKeys(function ($role) {
        return [$role->id => $role->title];
    })
    ->toArray();

$editions[''] = 'Selecione a Edição';

$editionValue = '';
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

    $actions = [
    'Avaliar' => function($registration){
        return  route('admin.registration.show', ['id' => $registration['id'],'type' => $registration['type']]);
    },

];

}

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Inscrições" subtitulo="Inscrições por edição ativa" searchPlaceholder="Procurar por categoria, autor, título, status ..."
        titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button" routeSearch="{{ route('admin.registration.index') }}"
        :columns="$columns" :actions="$actions" :data="$formattedData" :paginator="$list" >

    <x-slot:searchForm>

    @if($user->isAdmin())
        <div style="min-width: max-content">
            <x-form-select name="edition"   class="max-h-32" nameOld="edicao"  id="editions" value="{{ $editionValue }}"
            multiple="{{ false }}" required="false" maxSelections="99" :options="$editions" />
        </div>

        <div style="min-width: max-content">
            <x-form-select name="status"   class="max-h-32" nameOld="status"  id="status" value="{{ $editionValue }}"
            multiple="{{ false }}" required="false"  :options="$status" />
        </div>
    @endif



    </x-slot>

    </x-pages.index>
@endsection
