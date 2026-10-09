<?php

namespace Tests\Feature;

use App\Enum\RegistrationStatusEnum;
use App\Models\User;
use App\Services\MenuBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_monitoring_requires_admin_and_its_menu_uses_the_new_route(): void
    {
        $this->get(route('monitoring'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('monitoring'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()->assertSee('Nenhuma edição disponível.');
        $menu = collect(MenuBuilder::getAdminMenu())->firstWhere('title', 'Monitoramento');
        $this->assertSame('monitoring', $menu['route']);
    }

    public function test_each_category_has_its_own_top_twenty_and_chart_counts_every_evaluated_entry(): void
    {
        $this->seedReportRecords();
        $record = (array) DB::table('registrations')->first();
        unset($record['id']);
        DB::table('registrations')->delete();
        $second = DB::table('categories')->insertGetId([
            'modality_id' => DB::table('modalities')->value('id'), 'title' => 'Segunda categoria',
            'acronym' => 'SEG', 'is_honorific' => false,
        ]);
        foreach ([$record['category_id'], $second] as $category) {
            for ($i = 0; $i < 23; $i++) {
                DB::table('registrations')->insert(array_replace($record, [
                    'category_id' => $category, 'title' => 'Obra '.$category.'-'.$i,
                    'status' => RegistrationStatusEnum::Avaliado->value, 'evaluation_avg' => 100 - $i,
                ]));
            }
        }
        $response = $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()
            ->assertSee('Autora Principal')->assertSee('AM')->assertDontSee('PERS — Personalidade');
        $this->assertCount(2, $response->viewData('categories'));
        foreach ($response->viewData('categories') as $category) {
            $this->assertCount(20, $category->registrations);
            $this->assertSame('Obra '.$category->id.'-0', $category->registrations->first()->title);
            $this->assertSame('Obra '.$category->id.'-19', $category->registrations->last()->title);
            $this->assertSame(['Recomendada' => 20, 'Meritória' => 3, 'Não recomendada' => 0], $response->viewData('distributions')[$category->id]);
        }
        $response->assertSee('data-series="[20,3,0]"', false);
    }

    public function test_distribution_respects_existing_rounded_score_boundaries_and_excludes_ineligible_entries(): void
    {
        $this->seedReportRecords();
        $record = (array) DB::table('registrations')->first();
        unset($record['id']);
        DB::table('registrations')->delete();
        foreach ([0, 60, 61, 80, 81, 100] as $score) {
            DB::table('registrations')->insert(array_replace($record, [
                'status' => $score === 100 ? 5 : 4, 'evaluation_avg' => $score,
                'title' => 'Nota '.$score,
            ]));
        }
        foreach ([[2, 100], [1, 100], [3, 100], [4, null], [4, 101], [4, -1]] as [$status, $score]) {
            DB::table('registrations')->insert(array_replace($record, ['status' => $status, 'evaluation_avg' => $score, 'title' => 'Excluída']));
        }
        $response = $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()->assertDontSee('Excluída');
        $category = $response->viewData('categories')->first();
        $this->assertCount(6, $category->registrations);
        $this->assertSame(['Recomendada' => 2, 'Meritória' => 2, 'Não recomendada' => 2], $response->viewData('distributions')[$category->id]);
        $this->assertSame([100, 81, 80, 61, 60, 0], $category->registrations->pluck('evaluation_avg')->map(fn ($score) => (int) $score)->all());
    }

    public function test_equal_scores_use_registration_id_and_titles_are_escaped(): void
    {
        $this->seedReportRecords();
        DB::table('registrations')->update(['status' => 4, 'evaluation_avg' => 90, 'title' => '<script>alert(1)</script>']);
        $record = (array) DB::table('registrations')->first();
        unset($record['id']);
        DB::table('registrations')->insert(array_replace($record, ['title' => 'Segunda obra']));
        $response = $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $ids = $response->viewData('categories')->first()->registrations->pluck('id')->all();
        $this->assertSame(DB::table('registrations')->orderBy('id')->pluck('id')->all(), $ids);
    }

    public function test_ranking_displays_authorship_and_safe_summary_below_the_chart(): void
    {
        $this->seedReportRecords();
        DB::table('registrations')->update([
            'status' => RegistrationStatusEnum::Avaliado->value,
            'evaluation_avg' => 90,
            'resumo' => '<p>Resumo do projeto.</p><img src=x onerror=alert(1)>',
        ]);
        $response = $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()
            ->assertSee('Coautora Um; Coautor Dois')
            ->assertSee('data-monitoring-view=', false)->assertSee('Resumo do projeto.')
            ->assertSee('aria-haspopup="dialog"', false)->assertSee('id="monitoring-project-dialog"', false)
            ->assertSee('<dt>Autor</dt>', false)->assertSee('<dt>Estado</dt>', false)
            ->assertSee('Ver inscrição completa')->assertDontSee('onerror=alert(1)', false);
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'monitoring-ranking'), strpos($html, 'monitoring-distribution'));
    }

    public function test_edition_filter_defaults_to_active_edition_and_validates_selection(): void
    {
        $this->seedReportRecords();
        $active = DB::table('editions')->value('id');
        DB::table('editions')->where('id', $active)->update(['is_registration_active' => true]);
        $record = (array) DB::table('editions')->first();
        unset($record['id']);
        $other = DB::table('editions')->insertGetId(array_replace($record, [
            'title' => 'Outra edição', 'is_registration_active' => false, 'registration_start' => now()->addYear(),
        ]));
        $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()
            ->assertViewHas('edition', fn ($edition) => $edition->id === $active)
            ->assertSee('Nenhuma inscrição avaliada nesta categoria.');
        $this->get(route('monitoring', ['edition' => $other]))->assertOk()
            ->assertViewHas('edition', fn ($edition) => $edition->id === $other)
            ->assertSee('Nenhuma categoria não honorífica nesta edição.');
        $this->getJson(route('monitoring', ['edition' => 99999]))->assertUnprocessable()->assertJsonValidationErrors('edition');
    }

    public function test_general_chart_includes_other_editions_and_rankings_start_collapsed(): void
    {
        $this->seedReportRecords();
        DB::table('registrations')->update(['status' => 4, 'evaluation_avg' => 100]);
        $firstEdition = DB::table('editions')->value('id');
        DB::table('editions')->where('id', $firstEdition)->update(['registration_start' => '2025-01-01']);
        $editionRecord = (array) DB::table('editions')->first();
        unset($editionRecord['id']);
        $otherEdition = DB::table('editions')->insertGetId(array_replace($editionRecord, ['title' => 'Edição anterior', 'registration_start' => '2024-01-01']));
        $modality = DB::table('modalities')->insertGetId(['title' => 'Outra modalidade', 'edition_id' => $otherEdition, 'is_active' => true]);
        $category = DB::table('categories')->insertGetId(['title' => 'Outra categoria', 'acronym' => 'OUT', 'modality_id' => $modality, 'is_honorific' => false]);
        $record = (array) DB::table('registrations')->first();
        unset($record['id']);
        DB::table('registrations')->insert(array_replace($record, ['category_id' => $category, 'title' => 'Projeto anterior', 'evaluation_avg' => 70]));

        foreach ([$firstEdition, $otherEdition] as $editionId) {
            $response = $this->actingAs($this->admin())->get(route('monitoring', ['edition' => $editionId]))
                ->assertOk()->assertSee('Panorama geral — todas as edições')
                ->assertSee('monitoring-charts-grid', false)
                ->assertSee('<details class="kt-card kt-card-grid monitoring-category">', false);
            $this->assertSame(['Recomendada' => 1, 'Meritória' => 1, 'Não recomendada' => 0], $response->viewData('generalDistribution'));
            $this->assertSame(['Edição anterior (2024)', 'Edição (2025)'], $response->viewData('qualityLabels'));
            $this->assertSame([35.0, 50.0], $response->viewData('qualitySeries')[0]['data']);
            $this->assertSame([35.0, 50.0], $response->viewData('qualitySeries')[1]['data']);
            $this->assertCount(1, $response->viewData('categories'));
            $this->assertSame(1, array_sum($response->viewData('distributions')[$response->viewData('categories')->first()->id]));
        }
    }

    public function test_popup_shows_final_grade_evaluation_criteria_and_indications(): void
    {
        $this->seedReportRecords();
        DB::table('registrations')->update(['status' => 4, 'evaluation_avg' => 90]);
        $registration = DB::table('registrations')->first();
        $reviewer = User::factory()->create(['name' => 'Avaliador do projeto']);
        $criterion = DB::table('evaluation_criteria')->insertGetId(['category_id' => $registration->category_id, 'name' => 'Impacto social']);
        $opinion = DB::table('opinions')->insertGetId(['registration_id' => $registration->id, 'user_id' => $reviewer->id, 'created_at' => now()]);
        DB::table('scores')->insert(['opinion_id' => $opinion, 'evaluation_criterion_id' => $criterion, 'valor' => 9, 'descricao' => '<p>Parecer favorável.</p>']);
        DB::table('indications')->insert(['registration_id' => $registration->id, 'user_id' => $reviewer->id, 'descricao' => '<p>Indicação pelo impacto.</p>', 'created_at' => now()]);

        $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()
            ->assertSee('Nota final')->assertSee('45 / 50')->assertSee('Avaliações (1)')
            ->assertSee('Indicações (1)')->assertSee('Avaliador do projeto')
            ->assertSee('Impacto social: 9')->assertSee('Parecer favorável.')
            ->assertSee('Indicação pelo impacto.')->assertDontSee('&lt;p&gt;Parecer');
    }

    public function test_quality_trend_uses_edition_year_and_excludes_ineligible_entries(): void
    {
        $this->seedReportRecords();
        DB::table('editions')->update(['registration_start' => '2024-01-01']);
        DB::table('registrations')->update(['status' => 4, 'evaluation_avg' => 100]);
        $record = (array) DB::table('registrations')->first();
        unset($record['id']);
        foreach ([[4, 70], [4, 20], [2, 100], [4, null]] as [$status, $score]) {
            DB::table('registrations')->insert(array_replace($record, ['status' => $status, 'evaluation_avg' => $score]));
        }
        $response = $this->actingAs($this->admin())->get(route('monitoring'))->assertOk()
            ->assertSee('data-monitoring-line', false)
            ->assertSee('Monitoramento da qualidade das inscrições apresentadas.')
            ->assertSee('Lista de inscrições enviadas ao julgamento.');
        $this->assertSame(['Edição (2024)'], $response->viewData('qualityLabels'));
        $this->assertSame([
            ['name' => 'Qualidade — nota média', 'data' => [31.67]],
            ['name' => 'Tendência da qualidade', 'data' => [null]],
        ], $response->viewData('qualitySeries'));
    }

    private function seedReportRecords(): void
    {
        $candidateId = DB::table('candidates')->insertGetId([
            'nome' => 'Autora Principal', 'cpf' => '00000000001', 'dt_nascimento' => '1990-01-01',
            'rg' => '12345', 'rg_expeditor' => 'SSP', 'rg_uf' => 'AM', 'sexo' => 'F', 'cep' => '69000-000',
            'ufendereco' => 'AM', 'cidade' => 'Manaus', 'endereco' => 'Rua Teste', 'numero' => '1',
            'ddd' => '92', 'celular' => '999999999', 'whatsapp' => false,
            'email' => 'autora@example.test', 'resumo_curricular' => 'Currículo',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento', 'registration_start' => now(),
            'registration_end' => now(), 'grant_date' => now(), 'judgment_date' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $modality = DB::table('modalities')->insertGetId([
            'title' => 'Artes', 'edition_id' => $edition, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $regularCategory = DB::table('categories')->insertGetId([
            'modality_id' => $modality, 'title' => 'Projetos', 'acronym' => 'PROJ', 'is_honorific' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $honorificCategory = DB::table('categories')->insertGetId([
            'modality_id' => $modality, 'title' => 'Personalidade', 'acronym' => 'PERS', 'is_honorific' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('registrations')->insert([
            'candidate_id' => $candidateId, 'category_id' => $regularCategory, 'title' => 'Obra *Amazônia*',
            'coautores' => 'Coautora Um; Coautor Dois', 'resumo' => '<p>Primeiro parágrafo.</p><p>Segundo parágrafo.</p>',
            'desenvolvimento' => 'Texto', 'objetivo' => 'Texto', 'conclusao' => 'Texto',
            'status' => RegistrationStatusEnum::Habilitado->value, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('nominees')->insert([
            'candidate_id' => $candidateId, 'category_id' => $honorificCategory, 'name' => 'Pessoa Indicada',
            'state' => 'PA', 'contact_data' => 'contato-secreto@example.test', 'presentation' => 'Apresentação',
            'activities' => 'Atividades', 'justification' => 'Justificativa cadastrada',
            'status' => RegistrationStatusEnum::Habilitado->value, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $roleId = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $roleId, 'created_at' => now(), 'updated_at' => now()]);

        return $user->fresh();
    }
}
