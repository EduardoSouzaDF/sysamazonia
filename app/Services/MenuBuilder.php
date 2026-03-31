<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class MenuBuilder
{
    public static function getMenuStructure(): array
    {
        $user = Auth::user();
        if(!$user){
            return [];
        }

        $user->loadMissing('roles');
        $menus = [];

        if ($user->hasRole(Role::ADMIN)) {
            $menus = array_merge($menus, self::getAdminMenu());
        }

        if ($user->hasRole(Role::LEITOR)) {
            $menus = array_merge($menus, self::getLeitorMenu());
        }

        return $menus;
    }

    public static function getJuradoMenu(): array
    {
        return [
            [
                'heading' => 'Julgamento',
            ],
            [
                'title' => 'Incrições',
                'icon' => 'ki-profile-circle',
                'route' => 'dashboard',
            ],
        ];
    }

    public static function getAdminMenu(): array
    {
        return [
            [
                'heading' => 'Relatórios',
            ],
            [
                'title' => 'Dashboard',
                'icon' => 'ki-abstract-45',
                'route' => 'dashboard',
            ],
            [
                'heading' => 'Administração Prêmios',
            ],
            [
                'title' => 'Edições',
                'icon' => 'ki-archive',
                'route' => 'admin.editions.index',
            ],
            [
                'title' => 'Modalidades',
                'icon' => 'ki-binance',
                'route' => 'admin.modalities.index',
            ],
            [
                'title' => 'Categorias',
                'icon' => 'ki-category',
                'route' => 'admin.categories.index',
            ],
            [
                'title' => 'Critérios de Avaliação',
                // 'icon' => 'ki-abstract-26',
                'icon' => 'ki-square-brackets',
                'route' => 'admin.criteria.index',
            ],
            [
                'heading' => 'Tarefas Sincronizadas',
            ],
            [
                'title' => 'Agente IA',
                'icon' => 'ki-abstract-45',
                'route' => 'dashboard',
            ],
            [
                'heading' => 'Cache Sistema',
            ],
            [
                'title' => 'Monitoramento',
                'icon' => 'ki-abstract-45',
                'route' => 'dashboard',
            ],
            [
                'heading' => 'Administração Sitema',
            ],

            [
                'title' => 'Usuários',
                'icon' => 'ki-profile-circle',
                'route' => 'admin.users.index',
            ],

        ];
    }

    public static function getLeitorMenu(): array
    {
        return [
            [
                'heading' => 'Relatórios',
            ],
            [
                'title' => 'Dashboard',
                'icon' => 'ki-abstract-45',
                'route' => 'dashboard',
            ],


        ];
    }
}
