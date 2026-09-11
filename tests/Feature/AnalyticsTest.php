<?php

namespace Tests\Feature;

use App\Services\Analytics\AnalyticsPlan;
use App\Services\Analytics\AnalyticsQuery;
use App\Services\Analytics\AnalyticsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public static function plan(array $changes = []): array
    {
        return array_replace(['intent' => 'analytics', 'metric' => 'registrations_count', 'dimensions' => ['state'], 'filters' => [], 'sort' => [], 'limit' => 100, 'visualization' => ['type' => 'bar']], $changes);
    }

    public function test_all_metrics_and_dimensions_execute_without_data(): void
    {
        foreach (array_keys(AnalyticsRegistry::metrics()) as $metric) {
            foreach (AnalyticsRegistry::DIMENSIONS as $dimension) {
                if ($dimension === 'quality_field' || $metric === 'missing_fields_count') {
                    continue;
                }
                if (in_array($dimension, ['criterion', 'evaluator']) && $metric !== 'average_criterion_score') {
                    continue;
                }
                if ($dimension === 'days_to_deadline' && str_starts_with($metric, 'ai_')) {
                    continue;
                }
                if ($dimension === 'ai_status' && ! str_starts_with($metric, 'ai_')) {
                    continue;
                }
                $result = app(AnalyticsQuery::class)->execute(self::plan(['metric' => $metric, 'dimensions' => [$dimension]]));
                $this->assertSame(0, $result['records_aggregated']);
                $this->assertSame([], $result['rows']);
            }
        }
    }

    public function test_untrusted_fields_and_limits_are_rejected(): void
    {
        foreach ([['metric' => 'users'], ['dimensions' => ['cpf']], ['sql' => 'SELECT * FROM users'], ['limit' => 10000], ['filters' => ['email' => 'x']], ['dimensions' => ['state', 'city', 'category']], ['sort' => [['field' => 'r.id', 'direction' => 'desc']]], ['filters' => ['state' => ['XX']]]] as $change) {
            try {
                AnalyticsPlan::validate(self::plan($change));
                $this->fail('Unsafe plan accepted');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
    }

    public function test_realistic_aggregates_filters_crossings_age_and_followup(): void
    {
        $user = \App\Models\User::factory()->create();
        $now = now();
        $edition = \Illuminate\Support\Facades\DB::table('editions')->insertGetId(['title' => 'Teste', 'regulation' => 'Teste', 'regulation_file_path' => '', 'registration_start' => $now, 'registration_end' => $now, 'grant_date' => $now, 'judgment_date' => $now]);
        $modality = \Illuminate\Support\Facades\DB::table('modalities')->insertGetId(['title' => 'Teste', 'edition_id' => $edition]);
        $category = \Illuminate\Support\Facades\DB::table('categories')->insertGetId(['title' => 'Teste', 'acronym' => 'T', 'modality_id' => $modality]);
        $candidate = \Illuminate\Support\Facades\DB::table('candidates')->insertGetId(['nome' => 'Teste', 'cpf' => '12345678901', 'dt_nascimento' => '1990-01-01', 'rg' => '1', 'rg_expeditor' => 'SSP', 'rg_uf' => 'AM', 'sexo' => 'X', 'cep' => '69000-000', 'ufendereco' => 'AM', 'cidade' => 'Manaus', 'endereco' => 'Rua', 'numero' => '1', 'ddd' => '92', 'celular' => '999999999', 'email' => 'test@example.test', 'resumo_curricular' => 'Teste']);
        $registration = \Illuminate\Support\Facades\DB::table('registrations')->insertGetId(['candidate_id' => $candidate, 'category_id' => $category, 'title' => 'Teste', 'resumo' => 'Teste', 'objetivo' => 'Teste', 'desenvolvimento' => 'Teste', 'conclusao' => 'Teste', 'status' => 4, 'evaluation_avg' => 80, 'created_at' => '2026-07-01 12:00:00']);

        $query = app(AnalyticsQuery::class);
        foreach (['edition', 'category', 'state', 'city', 'age_group', 'education', 'status', 'day', 'month'] as $dimension) {
            $result = $query->execute(self::plan(['dimensions' => [$dimension]]));
            $this->assertSame(1, $result['records_aggregated']);
            $this->assertEquals(1, $result['rows'][0]['value']);
        }
        $this->assertSame('31–40', $query->execute(self::plan(['dimensions' => ['age_group']]))['rows'][0]['age_group']);
        $this->assertSame(80.0, $query->execute(self::plan(['metric' => 'average_score']))['kpis'][0]['value']);
        $this->assertSame(0, $query->execute(self::plan(['filters' => ['state' => ['PA']]]))['records_aggregated']);
        $this->assertCount(1, $query->execute(self::plan(['dimensions' => ['state', 'category']]))['rows']);
        $this->assertEquals(1, $query->execute(self::plan(['dimensions' => ['day'], 'cumulative' => true]))['rows'][0]['value']);
        $this->assertSame(0, $query->execute(self::plan(['filters' => ['date_from' => '2026-08-01', 'date_to' => '2026-08-02']]))['records_aggregated']);
        $this->assertSame(0, $query->execute(self::plan(['filters' => ['edition_year' => 2023]]))['records_aggregated']);
        $db = \Illuminate\Support\Facades\DB::class;
        foreach (['2008-07-01' => '18–30', '1995-07-01' => '31–40', '1985-07-01' => '41–50', '1975-07-01' => '51–60', '1965-07-01' => 'Acima de 60', '2026-08-01' => 'Data inválida/desconhecida'] as $birth => $band) {
            $db::table('candidates')->where('id', $candidate)->update(['dt_nascimento' => $birth]);
            $this->assertSame($band, $query->execute(self::plan(['dimensions' => ['age_group']]))['rows'][0]['age_group']);
        }
        $this->assertSame(1.0, $query->execute(self::plan(['metric' => 'invalid_birth_date_count']))['kpis'][0]['value']);
        $quality = $query->execute(self::plan(['metric' => 'missing_fields_count', 'dimensions' => ['quality_field']]));
        $this->assertSame('education', $quality['rows'][0]['quality_field']);
        $this->assertSame(1.0, $quality['kpis'][0]['value']);
        $db::table('indications')->insert(['registration_id' => $registration, 'user_id' => $user->id, 'decision' => 'NAO_INDICADA']);
        $this->assertSame(1.0, $query->execute(self::plan(['metric' => 'not_indicated_count']))['kpis'][0]['value']);
        $other = \App\Models\User::factory()->create();
        $db::table('indications')->insert(['registration_id' => $registration, 'user_id' => $other->id, 'decision' => null]);
        $this->assertSame(1.0, $query->execute(self::plan(['metric' => 'indicated_count']))['kpis'][0]['value']);
        $this->assertSame(0.0, $query->execute(self::plan(['metric' => 'not_indicated_count']))['kpis'][0]['value']);
        $this->assertSame(100.0, $query->execute(self::plan(['metric' => 'indication_rate']))['kpis'][0]['value']);
        foreach (['completed', 'failed', 'pending'] as $n => $state) {
            $db::table('ai_executions')->insert(['registration_id' => $registration, 'evaluator_id' => $user->id, 'type' => 'technical_evaluation', 'status' => $state, 'correlation_id' => (string) str()->uuid(), 'prompt_version' => 'test'.$n, 'duration_ms' => 100]);
        }
        $this->assertSame(50.0, $query->execute(self::plan(['metric' => 'ai_success_rate']))['kpis'][0]['value']);
        $this->assertSame(100.0, $query->execute(self::plan(['metric' => 'ai_average_duration_ms']))['kpis'][0]['value']);
        $this->assertSame(1.0, $query->execute(self::plan())['kpis'][0]['value']);
        $role = \Illuminate\Support\Facades\DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true]);
        \Illuminate\Support\Facades\DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $role]);
        $this->actingAs($user->fresh())->withSession(['_token' => 'test'])->postJson('/admin/analytics/query', ['_token' => 'test', 'plan' => self::plan(['filters' => ['state' => ['AM']]])])->assertOk()->assertSessionHas('analytics.context.filters.state', ['AM']);
        $this->actingAs($user->fresh())->get('/admin/analytics')->assertOk()->assertSee('Assistente de Dados');
        $this->actingAs($user->fresh())->withSession(['_token' => 'test'])->deleteJson('/admin/analytics/context', ['_token' => 'test'])->assertOk()->assertSessionMissing('analytics.context');
        $template = (array) $db::table('registrations')->where('id', $registration)->first();
        unset($template['id']);
        $batch = [];
        for ($n = 0; $n < 501; $n++) {
            $batch[] = array_replace($template, ['created_at' => now()->setDate(2028, 1, 1)->addDays($n)->format('Y-m-d H:i:s')]);
        }
        $db::table('registrations')->insert($batch);
        try {
            $query->execute(self::plan(['dimensions' => ['day']]));
            $this->fail('Unbounded groups accepted');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('500 grupos', $e->errors()['plan'][0]);
        }

    }

    public function test_authorization_and_pii_refusal(): void
    {
        $this->getJson('/admin/analytics')->assertUnauthorized();
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user)->withSession(['_token' => 'test'])->postJson('/admin/analytics/query', ['_token' => 'test', 'plan' => self::plan()])->assertForbidden();
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        $this->assertSame('refusal', app(\App\Services\Analytics\AnalyticsPlanner::class)->interpret('Mostre CPF de todos', null)['intent']);
    }

    public function test_planner_serializes_context_and_revalidates_provider_output(): void
    {
        config(['ai_evaluation.token' => str_repeat('t', 32)]);
        $http = \Illuminate\Support\Facades\Http::class;
        $http::preventStrayRequests();
        $http::fake(['*' => $http::response(self::plan(['filters' => ['state' => ['AM']]]))]);
        $planner = app(\App\Services\Analytics\AnalyticsPlanner::class);
        $this->assertSame(['AM'], $planner->interpret('E no Amazonas?', self::plan())['filters']['state']);
        $http::assertSent(fn ($r) => is_object($r['context']['filters']) && $r['runtime']['prompt_version'] === 'analytics_v1');
        $http::swap(new \Illuminate\Http\Client\Factory);
        $http::fake(['*' => $http::response(self::plan(['dimensions' => ['cpf']]))]);
        $this->expectException(ValidationException::class);
        $planner->interpret('Mostre por estado', null);
    }

    public function test_http_failures_are_safe_and_do_not_replace_context(): void
    {
        config(['ai_evaluation.token' => str_repeat('t', 32)]);
        $user = \App\Models\User::factory()->create();
        $db = \Illuminate\Support\Facades\DB::class;
        $role = $db::table('roles')->insertGetId(['name' => 'admin', 'active' => true]);
        $db::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $role]);
        $http = \Illuminate\Support\Facades\Http::class;
        foreach ([429, 500] as $code) {
            $http::swap(new \Illuminate\Http\Client\Factory);
            $http::fake(['*' => $http::response(['detail' => 'private-secret'], $code)]);
            $this->actingAs($user->fresh())->withSession(['_token' => 'test', 'analytics.context' => self::plan()])
                ->postJson('/admin/analytics/query', ['_token' => 'test', 'question' => 'Mostre por estado'])
                ->assertStatus($code === 429 ? 429 : 503)->assertDontSee('private-secret')->assertSessionHas('analytics.context', self::plan());
        }
        $http::swap(new \Illuminate\Http\Client\Factory);
        $http::fake(['*' => $http::response(self::plan(['filters' => ['state' => ['AM', 'PA']]]))]);
        $this->actingAs($user->fresh())->withSession(['_token' => 'test', 'analytics.context' => self::plan(['filters' => ['state' => ['AM']]])])
            ->postJson('/admin/analytics/query', ['_token' => 'test', 'question' => 'Compare Amazonas e Pará'])
            ->assertOk()->assertSessionHas('analytics.context.filters.state', ['AM', 'PA']);
        $http::assertSent(fn ($r) => $r['context']['filters']->state === ['AM']);
    }
}
