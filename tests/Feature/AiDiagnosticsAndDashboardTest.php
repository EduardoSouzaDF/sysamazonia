<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\AiDashboardMetrics;
use App\Services\Ai\AiServiceDiagnostics;
use App\Services\Ai\AiSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiDiagnosticsAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ai_evaluation.token' => str_repeat('t', 48), 'ai_evaluation.service_url' => 'http://127.0.0.1:8000', 'ai_evaluation.technical_evaluator_id' => null, 'ai_evaluation.selection_evaluator_id' => null]);
        Http::preventStrayRequests();
    }

    public function test_internal_auth_offline_timeout_and_provider_errors_are_distinct(): void
    {
        $service = app(AiServiceDiagnostics::class);
        $settings = app(AiSettingsService::class)->current();
        foreach ([401 => 'AI_UNAUTHORIZED', 403 => 'AI_FORBIDDEN', 502 => 'PROVIDER_RATE_LIMIT'] as $status => $code) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake(['*' => Http::response(['code' => 'PROVIDER_RATE_LIMIT', 'detail' => 'untrusted-secret'], $status)]);
            $result = $service->check($settings, 'test');
            $this->assertSame($code, $result['code']);
            $this->assertTrue($result['online']);
            $this->assertStringNotContainsString('untrusted-secret', $result['message']);
        }
        foreach (['cURL error 7' => 'AI_OFFLINE', 'cURL error 28' => 'AI_TIMEOUT'] as $message => $code) {
            Http::swap(new \Illuminate\Http\Client\Factory);
            Http::fake(fn () => throw new ConnectionException($message));
            $this->assertSame($code, $service->check($settings)['code']);
        }
    }

    public function test_configured_token_sent_and_missing_token_does_not_make_request(): void
    {
        Http::fake(['*' => Http::response(['status' => 'ok'])]);
        $settings = app(AiSettingsService::class)->current();
        $this->assertTrue(app(AiServiceDiagnostics::class)->check($settings)['ok']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer '.str_repeat('t', 48)));
        config(['ai_evaluation.token' => null]);
        $this->assertSame('AI_CONFIGURATION_ERROR', app(AiServiceDiagnostics::class)->check($settings)['code']);
        Http::assertSentCount(1);
    }

    public function test_endpoint_versioning_and_provider_change_never_reuse_key(): void
    {
        $admin = User::factory()->create();
        $service = app(AiSettingsService::class);
        $service->update(['provider' => 'openai', 'model' => 'test-model', 'api_key' => 'test-provider-secret'], $admin);
        $setting = $service->update(['provider' => 'local', 'model' => 'test-model', 'base_url' => 'http://127.0.0.1:11434', 'local_type' => 'ollama'], $admin);
        $this->assertNull($setting->api_key);
        $this->assertSame('http://127.0.0.1:11434', $setting->versions()->latest('id')->first()->base_url);
        $this->assertNull(Cache::get('ai.settings.resolved'));
        $this->assertNull(Cache::get('ai.settings.resolved.v2'));
    }

    public function test_dashboard_empty_state_and_date_boundaries_use_two_aggregate_queries(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 10)->setTime(0, 5));
        $metrics = app(AiDashboardMetrics::class)->forDays(7);
        $this->assertNull($metrics['successRate']);
        $this->assertCount(7, $metrics['labels']);
        $this->assertSame('2026-09-04', $metrics['labels'][0]);
        $this->assertSame('2026-09-10', $metrics['labels'][6]);
        Cache::flush();
        $this->createExecutionFixtures();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $metrics = app(AiDashboardMetrics::class)->forDays(7);
        $this->assertCount(2, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame(50.0, $metrics['successRate']);
        $this->assertSame(1, $metrics['series']['technical_evaluation'][5]);
        $this->assertSame(1, $metrics['series']['strategic_selection'][6]);
        $this->assertSame(1, $metrics['counts']['failed']);
        $this->travelBack();
    }

    public function test_ui_smoke_has_provider_options_separate_automation_blocks_and_chart_order(): void
    {
        $admin = $this->admin();
        Http::fake(['*' => Http::response(['status' => 'ok'])]);
        $this->actingAs($admin)->get(route('admin.ai-settings.index'))->assertOk()
            ->assertSeeInOrder(['Avaliador IA', 'Avaliação técnica por IA', 'Indicador IA', 'Indicação estratégica por IA'])
            ->assertSeeInOrder(['Pendentes por status', 'Andamento das avaliações', 'Execuções recentes'])
            ->assertSee('Anthropic Claude')->assertSee('Groq')->assertSee('Base URL')
            ->assertDontSee(str_repeat('t', 48));
        $this->actingAs(User::factory()->create())->withSession(['_token' => 'csrf-test'])->post(route('admin.ai-settings.models'), ['_token' => 'csrf-test'])->assertForbidden();
    }

    public function test_invalid_settings_never_flash_api_key(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->withSession(['_token' => 'csrf-test'])
            ->put(route('admin.ai-settings.update'), ['_token' => 'csrf-test', 'provider' => 'invalid', 'api_key' => 'private-test-secret'])
            ->assertSessionHasErrors('provider')->assertSessionMissing('_old_input.api_key');
    }

    public function test_provider_settings_validation_local_optional_key_and_model_cache(): void
    {
        $admin = $this->admin();
        $payload = ['_token' => 'csrf-test', 'provider' => 'local', 'model' => 'test-model', 'base_url' => 'http://127.0.0.1:11434',
            'local_type' => 'ollama', 'technical_evaluator_id' => $admin->id, 'selection_evaluator_id' => $admin->id,
            'connect_timeout' => 5, 'timeout' => 60, 'tries' => 3, 'evaluation_enabled' => 1, 'selection_enabled' => 0];
        $this->actingAs($admin)->withSession(['_token' => 'csrf-test'])->put(route('admin.ai-settings.update'), $payload)
            ->assertSessionHasNoErrors();
        $resolved = app(AiSettingsService::class)->current();
        $this->assertNull($resolved->apiKey);
        $this->assertTrue($resolved->evaluationEnabled);
        $this->assertFalse($resolved->selectionEnabled);
        $this->actingAs($admin)->put(route('admin.ai-settings.update'), array_replace($payload, ['model' => "invalid\nmodel"]))
            ->assertSessionHasErrors('model');
        $this->actingAs($admin)->put(route('admin.ai-settings.update'), array_replace($payload, ['base_url' => 'file:///etc/passwd']))
            ->assertSessionHasErrors('base_url');
        $this->actingAs($admin)->put(route('admin.ai-settings.update'), array_replace($payload, ['technical_evaluator_id' => 999999]))
            ->assertSessionHasErrors('technical_evaluator_id');
        Http::fake(['*' => Http::response(['models' => ['test-model']])]);
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($admin)->post(route('admin.ai-settings.models'), ['_token' => 'csrf-test'])->assertSessionHas('success');
        }
        Http::assertSentCount(1);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $role = DB::table('roles')->insertGetId(['name' => 'admin', 'active' => true]);
        DB::table('user_role')->insert(['user_id' => $user->id, 'role_id' => $role]);

        return $user->fresh();
    }

    private function createExecutionFixtures(): void
    {
        $user = User::factory()->create();
        $now = now();
        $edition = DB::table('editions')->insertGetId(['title' => 'Teste', 'regulation' => 'Teste', 'regulation_file_path' => '', 'registration_start' => $now, 'registration_end' => $now, 'grant_date' => $now, 'judgment_date' => $now]);
        $modality = DB::table('modalities')->insertGetId(['title' => 'Teste', 'edition_id' => $edition]);
        $category = DB::table('categories')->insertGetId(['title' => 'Teste', 'acronym' => 'T', 'modality_id' => $modality]);
        $candidate = DB::table('candidates')->insertGetId(['nome' => 'Teste', 'cpf' => '12345678901', 'dt_nascimento' => '1990-01-01', 'rg' => '1', 'rg_expeditor' => 'SSP', 'rg_uf' => 'AM', 'sexo' => 'X', 'cep' => '69000-000', 'ufendereco' => 'AM', 'cidade' => 'Manaus', 'endereco' => 'Rua', 'numero' => '1', 'ddd' => '92', 'celular' => '999999999', 'email' => 'test@example.test', 'resumo_curricular' => 'Teste']);
        $registration = DB::table('registrations')->insertGetId(['candidate_id' => $candidate, 'category_id' => $category, 'title' => 'Teste', 'resumo' => 'Teste', 'objetivo' => 'Teste', 'desenvolvimento' => 'Teste', 'conclusao' => 'Teste', 'status' => 1]);
        foreach ([['completed', 'technical_evaluation', '2026-09-09 23:59:59', '2026-09-09 23:59:59'], ['completed', 'strategic_selection', '2026-09-10 00:00:00', '2026-09-01 10:00:00'], ['failed', 'technical_evaluation', '2026-09-10 00:00:01', '2026-09-10 00:00:00']] as $i => [$status, $type, $completed, $created]) {
            DB::table('ai_executions')->insert(['registration_id' => $registration, 'evaluator_id' => $user->id, 'type' => $type, 'status' => $status, 'correlation_id' => (string) str()->uuid(), 'prompt_version' => 'test_v'.$i, 'completed_at' => $completed, 'created_at' => $created]);
        }
    }
}
