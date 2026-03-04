<?php
namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class MenuBuilder
{
    public static function getMenuStructure(): array
    {
        $user = Auth::user()->with('roles')->get()->first();
        $menus = [];

        if ($user->hasRole(Role::ADMIN)) {
            $menus = array_merge($menus, self::getAdminMenu());
        }

        if ($user->hasRole(Role::JURADO)) {
            $menus = array_merge($menus, self::getJuradoMenu());
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
                'title' => 'Dashboard',
                'icon' => 'ki-abstract-45',
                'route' => 'dashboard',
            ],
            [
                'heading' => 'Administração',
            ],
            [
                'title' => 'Usuários',
                'icon' => 'ki-profile-circle',
                'route' => 'admin.users.index',
            ],
        ];
    }
}
