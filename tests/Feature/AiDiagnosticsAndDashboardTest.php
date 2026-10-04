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

    public function test_tabs_load_only_their_content_and_diagnostics_on_demand(): void
    {
        $admin = $this->admin();
        Http::fake(['*' => Http::response(['status' => 'ok'])]);
        $this->actingAs($admin)->get(route('admin.ai-settings.index'))->assertOk()
            ->assertViewHas('tab', 'progress')->assertSee('ai-metrics-data')
            ->assertDontSee('name="technical_prompt"', false);
        $this->get(route('admin.ai-settings.index', ['tab' => 'configuration']))->assertOk()
            ->assertSeeInOrder(['Avaliador IA', 'Avaliação técnica por IA', 'Indicador IA', 'Indicação estratégica por IA'])
            ->assertSee('Anthropic Claude')->assertSee('Groq')->assertSee('URL do serviço')
            ->assertDontSee('ai-metrics-data')->assertDontSee(str_repeat('t', 48));
        $this->get(route('admin.ai-settings.index', ['tab' => 'executions']))->assertOk()
            ->assertSee('Nenhuma execução encontrada');
        Http::assertNothingSent();
        $this->get(route('admin.ai-settings.index', ['tab' => 'problems']))->assertOk()
            ->assertSee('Problemas e diagnóstico')->assertSee('Conectividade');
        Http::assertSentCount(1);
        $this->actingAs(User::factory()->create())->withSession(['_token' => 'csrf-test'])->post(route('admin.ai-settings.models'), ['_token' => 'csrf-test'])->assertForbidden();
    }

    public function test_execution_history_filters_paginates_and_escapes_errors(): void
    {
        $this->createExecutionFixtures();
        $failed = DB::table('ai_executions')->where('status', 'failed')->first();
        DB::table('ai_executions')->where('id', $failed->id)->update([
            'error_code' => 'PROVIDER_INVALID_RESPONSE', 'error_message' => '<script>alert(1)</script>',
        ]);
        for ($i = 0; $i < 16; $i++) {
            $row = (array) $failed;
            unset($row['id']);
            $row['correlation_id'] = (string) str()->uuid();
            $row['prompt_version'] = 'pagination_'.$i;
            DB::table('ai_executions')->insert($row);
        }
        $response = $this->actingAs($this->admin())->get(route('admin.ai-settings.index', [
            'tab' => 'executions', 'status' => 'failed', 'type' => 'technical_evaluation',
        ]))->assertOk()->assertViewHas('recent', fn ($recent) => $recent->total() === 17 && $recent->count() === 15);
        $this->assertStringContainsString('status=failed', $response->viewData('recent')->nextPageUrl());
        $this->assertStringContainsString('tab=executions', $response->viewData('recent')->nextPageUrl());
        $this->get(route('admin.ai-settings.index', ['tab' => 'executions', 'status' => 'failed', 'page' => 2]))
            ->assertOk()->assertSee('PROVIDER_INVALID_RESPONSE')->assertSee('<script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_invalid_tab_falls_back_and_validation_errors_open_configuration(): void
    {
        $this->actingAs($this->admin())->get(route('admin.ai-settings.index', ['tab' => 'unknown']))
            ->assertOk()->assertViewHas('tab', 'progress');
        $this->withSession(['_token' => 'csrf-test'])->from(route('admin.ai-settings.index'))
            ->put(route('admin.ai-settings.update'), ['_token' => 'csrf-test', 'provider' => 'invalid'])
            ->assertSessionHasErrors('provider');
        $this->get(route('admin.ai-settings.index'))->assertOk()->assertViewHas('tab', 'configuration');
    }

    public function test_processing_feedback_distinguishes_queue_success_and_incomplete_result(): void
    {
        $this->actingAs($this->admin())->withSession(['_token' => 'csrf-test']);
        foreach ([['sync', 1, 0, 'error'], ['sync', 1, 1, 'success'], ['async', 1, 0, 'success'], ['sync', 0, 0, 'success']] as [$mode, $started, $completed, $flash]) {
            $this->mock(\App\Services\Ai\AiPendingOperations::class, function ($mock) use ($mode, $started, $completed) {
                $mock->shouldReceive('start')->once()->with(\App\Enum\AiExecutionType::TechnicalEvaluation, $mode === 'sync', $mode === 'sync' ? 1 : null)
                    ->andReturn(compact('started', 'completed'));
            });
            $response = $this->post(route('admin.ai-settings.process', 'technical'), ['mode' => $mode, '_token' => 'csrf-test']);
            $response->assertRedirect(route('admin.ai-settings.index', ['tab' => 'evaluations']))->assertSessionHas($flash);
            $this->assertStringNotContainsString('sincronamente', $response->getSession()->get($flash));
        }
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
