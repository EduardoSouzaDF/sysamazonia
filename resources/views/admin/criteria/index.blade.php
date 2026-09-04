<?php
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

$columns = [
    // 'Edição' => 'edition',
    'Modalidade' => 'modality',
    'Categoria' => 'category',
    'Nome' => 'name',
    'Peso' => 'weight',
    'Mínimo' => 'min_score',
    'Máximo' => 'max_score',
];

$actions = [
    'Editar' => fn($criteria) => route('admin.criteria.edit', ['criterion' => $criteria['id']]),
];

$formattedData = array_map(function ($criteria) {
    return [
        'id' => $criteria['id'],
        'edition' => $criteria['category']['modality']['edition']['title'],
        'modality' => $criteria['category']['modality']['title'],
        'category' => $criteria['category']['title'],
        'name' => $criteria['name'],
        'weight' => $criteria['weight'],
        'min_score' => $criteria['min_score'],
        'max_score' => $criteria['max_score'],
    ];
}, $criterias->toArray()['data']);

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Critérios de Avaliação do sistema" subtitulo="Gerenciamento de critérios avaliativos"
        searchPlaceholder="Procurar por título ..." titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button"
        routeCreatenew="{{ route('admin.criteria.create') }}" routeSearch="{{ route('admin.criteria.index') }}"
        textCreateNew="Criar Novo Critério de Avaliação" :columns="$columns" :actions="$actions" :data="$formattedData"
        :paginator="$criterias" />
@endsection
