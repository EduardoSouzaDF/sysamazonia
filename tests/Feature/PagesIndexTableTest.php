<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PagesIndexTableTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DADO QUE uma coluna da listagem traz HTML pronto (badges de perfis)
     * ENTÃO o `title` da célula recebe só o texto, sem quebrar o atributo.
     */
    public function test_celula_com_html_usa_texto_puro_no_title(): void
    {
        $admin = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $admin->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin->fresh())->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('title="admin"', false)
            ->assertDontSee('title="<span', false)
            ->assertSee('<span class="kt-badge kt-badge-outline kt-badge-warning">admin</span>', false);
    }
}
