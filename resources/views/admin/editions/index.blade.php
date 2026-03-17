<?php
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

$columns = [
    'Edição' => 'title',
    'Início' => 'registration_start',
    'Fim' => 'registration_end',
    'Inscrições' => 'grant_date',
    'Inscrições Ativas ?' => 'is_registration_active',
    'Qtd Incrições' => 'applications_per_candidate',
];

$actions = [
    'Editar' => fn($edition) => route('admin.editions.edit', ['edition' => $edition['id']]),
    'Deletar' => fn($edition) => route('admin.editions.destroy', ['edition' => $edition['id']]),
];

$formattedData = array_map(function ($edition) {
    return [
        'title' => $edition['title'],
        'registration_start' => $edition['registration_start'] ? Carbon::parse($edition['registration_start'])->format('d/m/Y') : '',
        'registration_end' => $edition['registration_end'] ? Carbon::parse($edition['registration_end'])->format('d/m/Y') : '',
        'grant_date' => $edition['grant_date'] ? Carbon::parse($edition['grant_date'])->format('d/m/Y') : '',
        'applications_per_candidate' => $edition['applications_per_candidate'],
        'is_registration_active' => $edition['is_registration_active'] ? 'Sim' : 'Não',
        'id' => $edition['id'],
    ];
}, $editions->toArray()['data']);

?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Edições do sistema" subtitulo="Gerenciamento de edições" searchPlaceholder="Procurar por título ..."
        titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button" routeCreatenew="{{ route('admin.editions.create') }}"
        routeSearch="{{ route('admin.editions.index') }}" textCreateNew="Criar Nova Edição" :columns="$columns"
        :actions="$actions" :data="$formattedData" :paginator="$editions" />
@endsection
