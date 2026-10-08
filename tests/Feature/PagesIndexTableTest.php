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

    /**
     * DADO QUE todas as listagens compartilham o mesmo id de tabela
     * ENTÃO o datatable do KTUI não guarda estado no navegador, para uma
     * listagem nunca exibir as linhas em cache de outra.
     */
    public function test_datatable_nao_guarda_estado_no_navegador(): void
    {
        $admin = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $admin->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin->fresh())->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-kt-datatable="true" data-kt-datatable-state-save="false"', false);
    }

    /**
     * DADO QUE a coluna de Ações é fixa e pinta o fundo com a cor da linha
     * ENTÃO a camada de cor fica atrás do botão e não intercepta cliques,
     * sem depender de classes do build do Tailwind.
     */
    public function test_camada_de_cor_da_coluna_acoes_nao_bloqueia_clique(): void
    {
        $admin = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $admin->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        $content = $this->actingAs($admin->fresh())->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('<span class="pages-actions-tint ', false)
            ->assertDontSee('-z-10', false)
            ->getContent();

        $rule = (string) str($content)->after('.pages-table .pages-actions-tint {')->before('}');
        $this->assertStringContainsString('pointer-events: none;', $rule);
        $this->assertStringContainsString('z-index: -1;', $rule);
    }
}
