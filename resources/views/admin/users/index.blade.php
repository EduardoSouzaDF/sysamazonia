<?php
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

$columns = [
    'Nome' => 'name',
    'Email' => 'email',
    'Data de Criação' => 'created_at',
    'Data de Verificação' => 'email_verified_at',
    'Perfis' => 'roles',
];

$actions = [
    'Logar Como' => '/', // ou route('impersonate', $user)
    'Editar' => fn($user) => route('admin.users.edit', ['user' => $user['id']]),
    'Deletar' => fn($user) => route('admin.users.destroy', ['user' => $user['id']]),
];

$formattedData = array_map(function ($user) {
    return [
        'name' => $user['name'],
        'email' => $user['email'],
        'created_at' => $user['created_at'] ? Carbon::parse($user['created_at'])->format('d/m/Y') : '',
        'email_verified_at' => $user['email_verified_at'] ? Carbon::parse($user['email_verified_at'])->format('d/m/Y') : '',
        'id' => $user['id'],
        'roles' =>new HtmlString( implode(' ', array_map(function ($role) {
            return '<span class="kt-badge kt-badge-outline kt-badge-warning">' . e($role['name']) . '</span>';
        }, $user['roles'] ?? []))),
    ];
}, $users->toArray()['data']);
?>
@extends('admin.content')
@vite(['resources/comp_themes/apexcharts/apexcharts.min.js', 'resources/comp_themes/apexcharts/apexcharts.css'])
@section('maincontent')
    <x-pages.index titulo="Usuários do sistema" subtitulo="Gerenciamento de usuários e suas permissões"
        searchPlaceholder="Procurar por nome, email ou perfil ..." titleBtnPesquisar="Pesquisar" idBtnPesquisar="search-button"
        routeCreatenew="{{ route('admin.users.create') }}" routeSearch="{{ route('admin.users.index') }}"
        textCreateNew="Criar Novo" :columns="$columns" :actions="$actions" :data="$formattedData" :paginator="$users" />
@endsection
