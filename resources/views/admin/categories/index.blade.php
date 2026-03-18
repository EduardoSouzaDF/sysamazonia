<?php
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

$columns = [
    'Edição' => 'edition',
    'Modalidade' => 'modality_id',
    'Nome' => 'title',
    'Sigla' => 'acronym',
    'Hoonorífica ?' => 'is_honorific',
    'Aberta para Parecer ?' => 'is_open_for_submissions',
];

$actions = [
    'Editar' => fn($category) => route('admin.categories.edit', ['category' => $category['id']]),
];

$formattedData = array_map(function ($category) {
    return [
        'edition' => $category['modality']['edition']['title'],
        'title' => $category['title'],
        'modality_id' => $category['modality']['title'],
        'acronym' => $category['acronym'],
        'is_honorific' => $category['is_honorific'] ? 'Sim' : 'Não',
        'is_open_for_submissions' => $category['is_open_for_submissions'] ? 'Sim' : 'Não',
        'id' => $category['id'],
    ];
}, $categories->toArray()['data']);

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Categorias do sistema" subtitulo="Gerenciamento de categorias"
        searchPlaceholder="Procurar por título ..." titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button"
        routeCreatenew="{{ route('admin.categories.create') }}" routeSearch="{{ route('admin.categories.index') }}"
        textCreateNew="Criar Nova Categoria" :columns="$columns" :actions="$actions" :data="$formattedData" :paginator="$categories" />
@endsection
