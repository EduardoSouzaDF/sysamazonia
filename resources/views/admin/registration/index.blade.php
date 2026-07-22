<?php
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
$columns = [
    'Edição' => 'edition',
    'Modalidade' => 'modality',
    'Categoria' => 'category',
    'Honorífica ?' => 'is_honorific',
    'Titulo' => 'title',
    'Candidato' => 'candidate_name',
    'CPF' => 'candidate_cpf',
    'Status' => 'status',
];


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
        'modality' => $registrationModel->category->modality->title,
        'category' => $registrationModel->category->title,
        'is_honorific' => $registrationModel->category->is_honorific ? 'Sim' : 'Não',
        'title' => $registrationModel->title !== null ?: $registrationModel->name,
        'candidate_name' => $registrationModel->candidate->nome,
        'candidate_cpf' => $registrationModel->candidate->cpf,
        'status' => $registrationModel->statusName(), // ✅ Usando o método do model
        'id' => $registrationModel->id,
        'type' => get_class($registration)
    ];
}, $list->items()); // Usar items() ao invés de toArray()['data']

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Inscrições" subtitulo="Inscrições por edição ativa" searchPlaceholder="Procurar por título, cpf ..."
        titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button" routeSearch="{{ route('admin.registration.index') }}"
        :columns="$columns" :actions="$actions" :data="$formattedData" :paginator="$list" />
@endsection
