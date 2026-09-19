<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserRoleSeeder extends Seeder
{
    public function run()
    {
        // Garantir que existam roles
        $this->call(RoleSeeder::class);

        // Obter alguns usuários (ajuste conforme necessário)
        $users = User::limit(5)->get();

        if ($users->isEmpty()) {
            // Se não houver usuários, criar alguns para teste
            $users = User::factory()->count(5)->create();
        }

        // Obter roles
        $adminRole = Role::where('name', 'admin')->first();
        $comission = Role::where('name', 'comissao')->first();
        $userRole = Role::where('name', 'user')->first();

        // Associar roles aos usuários
        foreach ($users as $index => $user) {
            switch ($index) {
                case 0:
                    // Primeiro usuário como admin
                    $user->roles()->sync([$adminRole->id]);
                    break;
                case 1:
                    // Segundo usuário como editor
                    $user->roles()->sync([$comission->id]);
                    break;
                default:
                    // Demais usuários como user comum
                    $user->roles()->sync([$userRole->id]);
                    break;
            }
        }
    }
}
