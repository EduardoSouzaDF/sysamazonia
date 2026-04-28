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

$actions = [];

$formattedData = array_map(function ($registration) {
    return [
        'edition' => $registration['category']['modality']['edition']['title'],
        'modality' => $registration['category']['modality']['title'],
        'category' => $registration['category']['title'],
        'is_honorific' => $registration['category']['is_honorific'] ? 'Sim' : 'Não',
        'title' => $registration['title'],
        'candidate_name' => $registration['candidate']['nome'],
        'candidate_cpf' => $registration['candidate']['cpf'],
        'status' => $registration['status'],
        'id' => $registration['id'],
    ];
}, $list->toArray()['data']);

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Inscrições" subtitulo="Inscrições por edição ativa" searchPlaceholder="Procurar por título, cpf ..."
        titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button" routeSearch="{{ route('admin.registration.index') }}"
        :columns="$columns" :data="$formattedData" :paginator="$list" />
@endsection
