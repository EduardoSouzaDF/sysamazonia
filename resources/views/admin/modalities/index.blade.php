<?php
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

$columns = [
    'Nome' => 'title',
    'Edição' => 'edition_id',
    'Qtd Candidatos por Modalidade' => 'candidacy_limit_per_modality',
    'Ativa ?' => 'is_active',
];

$actions = [
    'Editar' => fn($modality) => route('admin.modalities.edit', ['modality' => $modality['id']]),
];

$formattedData = array_map(function ($modality) {
    return [
        'title' => $modality['title'],
        'edition_id' => $modality['edition']['title'],
        'candidacy_limit_per_modality' => $modality['candidacy_limit_per_modality'],
        'is_active' => $modality['is_active'] ? 'Sim' : 'Não',
        'id' => $modality['id'],
    ];
}, $modalities->toArray()['data']);

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Modalidades do sistema" subtitulo="Gerenciamento de modalidades"
        searchPlaceholder="Procurar por título ..." titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button"
        routeCreatenew="{{ route('admin.modalities.create') }}" routeSearch="{{ route('admin.modalities.index') }}"
        textCreateNew="Criar Nova Modalidade" :columns="$columns" :actions="$actions" :data="$formattedData" :paginator="$modalities" />
@endsection
