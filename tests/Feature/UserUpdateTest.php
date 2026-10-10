<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_creates_missing_extra_data_and_updates_existing_record_without_duplicates(): void
    {
        $admin = User::factory()->create();
        $role = Role::create(['name' => 'admin', 'active' => true]);
        $admin->roles()->attach($role);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $other->extraData()->create(['empresa' => 'Outra empresa']);
        $this->assertNull($user->extraData);

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => 'Usuário atualizado',
            'email' => $user->email,
            'roles' => [$role->id],
            'julgador' => '1',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $this->assertTrue((bool) $user->fresh()->is_judge);
        $this->assertTrue($user->fresh()->roles->contains($role));
        $extra = $user->fresh()->extraData;
        $this->assertNotNull($extra);

        $this->put(route('admin.users.update', $user), [
            'name' => 'Usuário atualizado',
            'email' => $user->email,
            'roles' => [$role->id],
            'empresa' => 'Empresa atualizada',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $this->assertSame($extra->id, $user->fresh()->extraData->id);
        $this->assertDatabaseHas('user_extra_data', ['user_id' => $user->id, 'empresa' => 'Empresa atualizada']);
        $this->assertSame(1, $user->extraData()->count());
        $this->assertSame('Outra empresa', $other->fresh()->extraData->empresa);
    }
}
