<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DashboardStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class DashboardStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();
        config(['dashboard.cache_ttl' => 0]);
        $this->withoutVite();
    }

    public function test_dashboard_requires_authentication_and_admin_or_reader_role(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
        foreach (['admin', 'leitor'] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('dashboard'))->assertOk()
                ->assertSee('Estatísticas Globais')->assertSee('Edição Atual')->assertSee('Nenhuma edição disponível')
                ->assertDontSee('data-dashboard-pdf', false);
        }
    }

    public function test_active_edition_wins_over_a_newer_inactive_edition(): void
    {
        $active = $this->edition('2025-01-01', true);
        $this->edition('2026-01-01');
        $stats = $this->statistics();
        $this->assertSame($active, $stats['edition']['id']);
        $this->assertFalse($stats['fallback']);
    }

    public function test_latest_start_date_is_fallback_and_interface_explains_it(): void
    {
        $latest = $this->edition('2026-01-01');
        $this->edition('2025-01-01');
        $stats = $this->statistics();
        $this->assertSame($latest, $stats['edition']['id']);
        $this->assertTrue($stats['fallback']);
        $this->actingAs($this->userWithRole('leitor'))->get(route('dashboard'))->assertOk()
            ->assertSee('Nenhuma edição está com inscrições ativas')->assertSee('data-dashboard-pdf', false)->assertSee('Gerar PDF');
    }

    public function test_multiple_active_editions_select_latest_and_log_warning(): void
    {
        $this->edition('2025-01-01', true);
        $latest = $this->edition('2026-01-01', true);
        Log::shouldReceive('warning')->once()->withArgs(fn ($message, $context) => $context['edition_id'] === $latest && $context['active_count'] === 2);
        $this->assertSame($latest, $this->statistics()['edition']['id']);
    }

    public function test_registration_and_nominee_counts_reconcile_across_all_dimensions(): void
    {
        $previous = $this->edition('2025-01-01');
        $current = $this->edition('2026-01-01', true);
        $oldCategory = $this->category($previous);
        $category = $this->category($current);
        $honorary = $this->category($current, true);
        $this->category($current); // Categories with zero inscriptions must remain visible.
        $candidate = $this->candidate(['ufendereco' => 'am', 'sexo' => 'Mulher', 'escolaridade' => 'Superior']);
        $this->inscription($oldCategory, $candidate);
        $this->inscription($category, $candidate);
        $this->inscription($category, $candidate);
        $this->inscription($honorary, $candidate, true);
        $stats = $this->statistics();
        $this->assertSame(4, $stats['global']['total']);
        $this->assertSame(3, $stats['current']['total']);
        $this->assertSame(2, $stats['current']['regular']);
        $this->assertSame(1, $stats['current']['honorary']);
        $this->assertSame([1, 3], array_column($stats['global']['editions'], 'total'));
        $this->assertSame([2, 1, 0], array_column($stats['current']['categories'], 'total'));
        foreach (['global', 'current'] as $scope) {
            foreach (['modalities', 'categories', 'states', 'ages', 'regions'] as $dimension) {
                $this->assertSame($stats[$scope]['total'], array_sum(array_column($stats[$scope][$dimension], 'total')), "$scope.$dimension");
            }
        }
        $this->assertSame(66.67, $stats['current']['categories'][0]['percentage']);
        $this->assertCount(27, $stats['current']['map']);
        $this->assertSame(3, $this->countFor($stats['current']['states'], 'AM'));
        $this->assertSame(3, $this->countFor($stats['current']['regions'], 'Norte'));
        $this->assertSame(0, $this->countFor($stats['current']['regions'], 'Sudeste')); // nominees.state is SP, deliberately ignored.
        $this->assertSame(3, $this->countFor($stats['current']['sex'], 'Mulher'));
        $this->assertSame(3, $this->countFor($stats['current']['education'], 'Superior'));
    }

    public function test_age_boundaries_use_inscription_date_and_include_under_eighteen(): void
    {
        $category = $this->category($this->edition('2020-01-01', true));
        foreach ([17, 18, 30, 31, 40, 41, 59, 60] as $age) {
            $candidate = $this->candidate(['dt_nascimento' => (2020 - $age).'-06-15']);
            $this->inscription($category, $candidate, $age % 2 === 0, '2020-06-15 12:00:00');
        }
        $stats = $this->statistics()['current'];
        $this->assertSame([1, 2, 2, 2, 1, 0], array_column($stats['ages'], 'total'));
        $this->assertSame(8, array_sum(array_column($stats['ages'], 'total')));
        $this->travelTo(now()->addYears(10));
        $this->assertSame($stats['ages'], $this->statistics()['current']['ages']);
        $this->travelBack();
    }

    public function test_birthday_has_not_occurred_and_leap_dates_are_validated(): void
    {
        $category = $this->category($this->edition('2026-01-01', true));
        $this->inscription($category, $this->candidate(['dt_nascimento' => '1995-06-16']), false, '2026-06-15 12:00:00');
        $this->inscription($category, $this->candidate(['dt_nascimento' => '2000-02-29']), true, '2026-06-15 12:00:00');
        $this->inscription($category, $this->candidate(['dt_nascimento' => '1900-02-29']), true, '2026-06-15 12:00:00');
        $stats = $this->statistics()['current'];
        $this->assertSame(2, $this->countFor($stats['ages'], '18 a 30'));
        $this->assertSame(1, $this->countFor($stats['ages'], 'Não informado'));
    }

    public function test_missing_and_invalid_demographics_remain_in_totals(): void
    {
        $category = $this->category($this->edition('2026-01-01', true));
        foreach (['', 'invalid', '2000-02-30', '2030-01-01', '0000-00-00'] as $birth) {
            $candidate = $this->candidate(['dt_nascimento' => $birth, 'ufendereco' => 'ZZ', 'sexo' => '', 'escolaridade' => null]);
            $this->inscription($category, $candidate);
        }
        $this->inscription($category, $this->candidate(['ufendereco' => '', 'sexo' => 'Prefiro não informar', 'escolaridade' => '']), true, null);
        $this->inscription($category, $this->candidate(['ufendereco' => 'DF', 'sexo' => 'Outra opção cadastrada', 'escolaridade' => 'Mestrado']));
        $stats = $this->statistics()['current'];
        $this->assertSame(7, $stats['total']);
        $this->assertSame(6, $this->countFor($stats['ages'], 'Não informado'));
        $this->assertSame(5, $this->countFor($stats['states'], 'UF inválida'));
        $this->assertSame(1, $this->countFor($stats['states'], 'Não informado'));
        $this->assertSame(1, $this->countFor($stats['regions'], 'Centro-Oeste'));
        $this->assertSame(6, $stats['unmapped']);
        $this->assertSame(5, $this->countFor($stats['sex'], 'Não informado'));
        $this->assertSame(1, $this->countFor($stats['sex'], 'Outra opção cadastrada'));
        $this->assertSame(6, $this->countFor($stats['education'], 'Não informado'));
        $this->assertSame(7, array_sum(array_column($stats['regions'], 'total')));
    }

    public function test_every_valid_state_is_counted_in_its_region(): void
    {
        $category = $this->category($this->edition('2026-01-01', true));
        foreach (array_keys(\App\Support\BrazilStates::STATES) as $uf) {
            $this->inscription($category, $this->candidate(['ufendereco' => strtolower($uf)]), true);
        }
        $stats = $this->statistics()['current'];
        $this->assertSame(27, $stats['total']);
        $this->assertSame([7, 9, 4, 4, 3], array_column($stats['regions'], 'total'));
        $this->assertSame(array_fill(0, 27, 1), array_column($stats['map'], 'total'));
        $this->assertSame(0, $stats['unmapped']);
    }

    public function test_changing_selected_edition_uses_a_different_global_cache_key(): void
    {
        config(['dashboard.cache_ttl' => 30]);
        $old = $this->edition('2025-01-01', true);
        $category = $this->category($old);
        $this->assertSame(0, $this->statistics()['global']['total']);
        $this->inscription($category, $this->candidate());
        DB::table('editions')->where('id', $old)->update(['is_registration_active' => false]);
        $current = $this->edition('2026-01-01', true);
        $stats = $this->statistics();
        $this->assertSame($current, $stats['edition']['id']);
        $this->assertSame(1, $stats['global']['total']);
        $this->assertSame(0, $stats['current']['total']);
    }

    public function test_map_contains_all_states_and_regions_with_zero_counts(): void
    {
        $this->edition('2026-01-01', true);
        $stats = $this->statistics()['current'];
        $this->assertSame(0, $stats['total']);
        $this->assertCount(27, $stats['map']);
        $this->assertCount(5, $stats['regions']);
        $this->assertSame(0, array_sum(array_column($stats['map'], 'total')));
        $this->assertContains('DF', array_column($stats['map'], 'uf'));
        $response = $this->actingAs($this->userWithRole('admin'))->get(route('dashboard'))->assertOk()
            ->assertSee('Esta edição ainda não possui inscrições')->assertSee('data-uf="DF"', false);
        $this->assertSame(27, substr_count($response->getContent(), 'class="map-state '));
        $paths = json_decode(file_get_contents(resource_path('maps/brazil-states.json')), true);
        $this->assertEqualsCanonicalizing(array_column($stats['map'], 'uf'), array_keys($paths));
    }

    public function test_dashboard_excludes_personal_data_and_safely_serializes_labels(): void
    {
        $edition = $this->edition('2026-01-01', true);
        $category = $this->category($edition);
        DB::table('categories')->where('id', $category)->update(['title' => '</script><script>alert("unsafe")</script>']);
        $this->inscription($category, $this->candidate(['nome' => 'PRIVATE CANDIDATE NAME', 'cpf' => 'PRIVATE-CPF', 'email' => 'private-person@example.test']));
        $this->actingAs($this->userWithRole('admin'))->get(route('dashboard'))->assertOk()
            ->assertDontSee('PRIVATE CANDIDATE NAME')->assertDontSee('PRIVATE-CPF')->assertDontSee('private-person@example.test')
            ->assertDontSee('</script><script>alert("unsafe")</script>', false)
            ->assertSee('dashboardStatistics', false);
    }

    public function test_cache_is_short_and_current_edition_is_always_fresh(): void
    {
        config(['dashboard.cache_ttl' => 30]);
        $edition = $this->edition('2026-01-01', true);
        $category = $this->category($edition);
        $candidate = $this->candidate();
        $this->assertSame(0, $this->statistics()['global']['total']);
        $this->inscription($category, $candidate);
        $stats = $this->statistics();
        $this->assertSame(0, $stats['global']['total']);
        $this->assertSame(1, $stats['current']['total']);
        $this->travel(31)->seconds();
        $this->assertSame(1, $this->statistics()['global']['total']);
        $this->travelBack();
        Cache::flush();
    }

    public function test_query_count_does_not_grow_with_inscription_count(): void
    {
        $category = $this->category($this->edition('2026-01-01', true));
        $candidate = $this->candidate();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->statistics();
        $before = count(DB::getQueryLog());
        DB::disableQueryLog();
        for ($i = 0; $i < 30; $i++) {
            $this->inscription($category, $candidate, $i % 2 === 0);
        }
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->statistics();
        $this->assertSame($before, count(DB::getQueryLog()));
        $this->assertLessThanOrEqual(19, $before);
        DB::disableQueryLog();
    }

    private function statistics(): array
    {
        return app(DashboardStatisticsService::class)->statistics();
    }

    private function countFor(array $rows, string $label): int
    {
        return array_column($rows, 'total', 'label')[$label] ?? 0;
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $id = DB::table('roles')->insertGetId(['name' => $role, 'active' => true]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $id]);

        return $user->fresh();
    }

    private function edition(string $start, bool $active = false): int
    {
        return DB::table('editions')->insertGetId(['title' => 'Edição '.$start, 'regulation' => 'Regulamento', 'regulation_file_path' => '', 'registration_start' => $start, 'registration_end' => '2026-12-31', 'grant_date' => '2027-01-01', 'judgment_date' => '2026-12-31', 'is_registration_active' => $active]);
    }

    private function category(int $edition, bool $honorary = false): int
    {
        $modality = DB::table('modalities')->insertGetId(['title' => 'Modalidade', 'edition_id' => $edition]);

        return DB::table('categories')->insertGetId(['title' => 'Categoria', 'acronym' => 'CAT'.$modality, 'modality_id' => $modality, 'is_honorific' => $honorary]);
    }

    private function candidate(array $overrides = []): int
    {
        $this->sequence++;

        return DB::table('candidates')->insertGetId($overrides + ['nome' => 'Nome privado', 'cpf' => 'cpf-'.$this->sequence, 'dt_nascimento' => '1990-06-15', 'rg' => 'rg-privado', 'rg_expeditor' => 'SSP', 'rg_uf' => 'RJ', 'sexo' => 'Homem', 'cep' => '69000-000', 'ufendereco' => 'AM', 'cidade' => 'Manaus', 'endereco' => 'Endereço privado', 'numero' => '1', 'ddd' => '92', 'celular' => '999999999', 'email' => 'candidate'.$this->sequence.'@example.test', 'escolaridade' => 'Superior', 'resumo_curricular' => 'Conteúdo privado']);
    }

    private function inscription(int $category, int $candidate, bool $honorary = false, ?string $createdAt = '2026-06-15 12:00:00'): void
    {
        $data = ['category_id' => $category, 'candidate_id' => $candidate, 'created_at' => $createdAt];
        if ($honorary) {
            DB::table('nominees')->insert($data + ['name' => 'Nome privado do indicado', 'state' => 'SP', 'contact_data' => 'Contato privado', 'presentation' => 'Texto', 'activities' => 'Texto', 'justification' => 'Texto']);
        } else {
            DB::table('registrations')->insert($data + ['title' => 'Proposta privada', 'resumo' => 'Texto', 'objetivo' => 'Texto', 'desenvolvimento' => 'Texto', 'conclusao' => 'Texto']);
        }
    }
}
