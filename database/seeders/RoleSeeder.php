<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'admin', 'active' => true],
            ['name' => 'editor', 'active' => true],
            ['name' => 'moderator', 'active' => true],
            ['name' => 'user', 'active' => true],
            ['name' => 'guest', 'active' => true],
            ['name' => 'super_admin', 'active' => true],
            ['name' => 'content_manager', 'active' => true],
            ['name' => 'support_agent', 'active' => true],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}
