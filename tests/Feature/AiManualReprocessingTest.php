<?php

namespace Tests\Feature;

use App\Enum\AiExecutionStatus;
use App\Enum\AiExecutionType;
use App\Enum\RegistrationStatusEnum;
use App\Jobs\EvaluateRegistrationWithAi;
use App\Models\AiExecution;
use App\Models\Opinion;
use App\Models\Registration;
use App\Models\Role;
use App\Models\User;
use App\Services\Ai\AiExecutionDispatcher;
use App\Services\Ai\AiManualReprocessing;
use App\Services\Ai\AiPendingOperations;
use App\Services\Ai\AiSettingsService;
use App\Services\Ai\TechnicalEvaluationProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiManualReprocessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Http::preventStrayRequests();
        config(['queue.default' => 'database', 'queue.connections.database.connection' => null]);
        Queue::fake();
    }

    public function test_new_round_preserves_parent_and_enqueues_only_the_child(): void
    {
        [$source, $admin] = $this->failedExecution();
        $child = $this->service()->request($source->id, $admin, 'current', 'Indisponibilidade temporária corrigida.');
        $this->assertSame($source->id, $child->parent_execution_id);
        $this->assertSame(1, $child->retry_round);
        $this->assertSame(0, (int) $child->attempts);
        $this->assertSame(AiExecutionStatus::Pending, $child->status);
        $this->assertSame(3, $source->fresh()->attempts);
        $this->assertSame('PROVIDER_UNAVAILABLE', $source->fresh()->error_code);
        $this->assertNotNull($source->fresh()->superseded_at);
        Queue::assertPushed(EvaluateRegistrationWithAi::class, fn ($job) => $job->executionId === $child->id);
        $this->assertDatabaseCount('opinions', 0);
    }

    public function test_duplicate_request_cannot_create_another_child(): void
    {
        [$source, $admin] = $this->failedExecution();
        $this->service()->request($source->id, $admin, 'current', 'Falha externa corrigida para teste.');
        try {
            $this->service()->request($source->id, $admin, 'current', 'Segundo clique na mesma execução.');
            $this->fail('Duplicate request should be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ai_executions', 2);
            Queue::assertPushed(EvaluateRegistrationWithAi::class, 1);
        }
    }

    public function test_current_and_previous_configuration_have_distinct_version_behavior(): void
    {
        [$source, $admin, $payload] = $this->failedExecution();
        app(AiSettingsService::class)->update(array_replace($payload, ['model' => 'gemini-new']), $admin);
        $child = $this->service()->request($source->id, $admin, 'previous', 'Manter configuração anterior deliberadamente.');
        $resolved = app(AiSettingsService::class)->forCorrelation($child->correlation_id);
        $this->assertSame('gemini-original', $resolved->model);
        $this->assertSame($source->ai_setting_version_id, $child->ai_setting_version_id);
    }

    public function test_current_configuration_uses_new_model_and_blocks_scheduler_after_exhaustion(): void
    {
        [$source, $admin, $payload] = $this->failedExecution();
        app(AiSettingsService::class)->update(array_replace($payload, ['model' => 'gemini-new']), $admin);
        $child = $this->service()->request($source->id, $admin, 'current', 'Usar novo modelo por indisponibilidade.');
        $this->assertSame('gemini-new', app(AiSettingsService::class)->forCorrelation($child->correlation_id)->model);
        $child->update(['status' => AiExecutionStatus::Failed, 'attempts' => 3, 'error_code' => 'PROVIDER_UNAVAILABLE']);
        $this->assertCount(0, app(AiPendingOperations::class)->candidates(AiExecutionType::TechnicalEvaluation));
        $this->assertSame($child->id, app(AiExecutionDispatcher::class)->create($source->registration, $source->type)->id);
        $this->assertDatabaseCount('ai_executions', 2);
    }

    public function test_previous_configuration_round_can_retry_after_panel_model_changes(): void
    {
        [$source, $admin, $payload] = $this->failedExecution();
        app(AiSettingsService::class)->update(array_replace($payload, ['model' => 'gemini-new', 'technical_prompt_version' => 'technical_evaluator_v2']), $admin);
        $child = $this->service()->request($source->id, $admin, 'previous', 'Manter modelo anterior para nova tentativa.');
        $child->update(['status' => AiExecutionStatus::Failed, 'attempts' => 1, 'error_code' => 'PROVIDER_UNAVAILABLE', 'updated_at' => now()->subMinutes(2)]);
        AiExecution::query()->whereKey($child->id)->update(['updated_at' => now()->subMinutes(2)]);

        $this->assertCount(1, app(AiPendingOperations::class)->candidates(AiExecutionType::TechnicalEvaluation));
        $this->assertSame($child->id, app(AiExecutionDispatcher::class)->create($source->registration, $source->type)->id);
        $this->assertSame('gemini-original', app(AiSettingsService::class)->forCorrelation($child->correlation_id)->model);
        $this->assertDatabaseCount('ai_executions', 2);
    }

    public function test_route_requires_confirmation_and_a_valid_reason(): void
    {
        [$source, $admin] = $this->failedExecution();
        $token = 'manual-reprocessing-test-token';
        $this->actingAs($admin)->withSession(['_token' => $token])->post(route('admin.ai-settings.reprocess'), [
            '_token' => $token, 'execution_ids' => [$source->id],
            'configuration' => 'current', 'reason' => 'curto',
        ])->assertSessionHasErrors(['reason', 'confirmed']);
        $this->assertDatabaseCount('ai_executions', 1);
        Queue::assertNothingPushed();
    }

    public function test_non_exhausted_round_and_sync_connection_are_rejected(): void
    {
        [$source, $admin] = $this->failedExecution();
        $source->update(['attempts' => 2]);
        try {
            $this->service()->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
            $this->fail('Remaining automatic attempts should be respected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ai_executions', 1);
        }
        $source->update(['attempts' => 3]);
        config(['queue.default' => 'sync']);
        $this->expectException(ValidationException::class);
        $this->service()->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
    }

    public function test_changed_status_and_existing_opinion_prevent_reprocessing(): void
    {
        [$source, $admin] = $this->failedExecution();
        $source->registration->updateQuietly(['status' => RegistrationStatusEnum::Avaliado]);
        try {
            $this->service()->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
            $this->fail('Changed registration must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ai_executions', 1);
        }
        $source->registration->updateQuietly(['status' => RegistrationStatusEnum::Habilitado]);
        Opinion::query()->create(['registration_id' => $source->registration_id, 'user_id' => $source->evaluator_id, 'source' => 'ai']);
        $this->expectException(ValidationException::class);
        $this->service()->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
    }

    public function test_processing_lock_and_superseded_jobs_are_respected(): void
    {
        [$source, $admin] = $this->failedExecution();
        $lock = Cache::lock('ai-registration-process-'.$source->registration_id.'-'.$source->type->value, 120);
        $this->assertTrue($lock->get());
        try {
            $this->service()->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
            $this->fail('Concurrent processing must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('ai_executions', 1);
        } finally {
            $lock->release();
        }
        $this->service()->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
        $this->assertFalse(app(TechnicalEvaluationProcessor::class)->process($source->id));
        Http::assertNothingSent();
    }

    public function test_route_is_admin_only_and_renders_single_and_bulk_controls(): void
    {
        [$source, $admin] = $this->failedExecution();
        $token = 'manual-reprocessing-test-token';
        $this->actingAs(User::factory()->create())->withSession(['_token' => $token])
            ->post(route('admin.ai-settings.reprocess'), ['_token' => $token])->assertForbidden();
        $this->withoutExceptionHandling();
        $this->actingAs($admin)->get(route('admin.ai-settings.index', ['tab' => 'executions']))
            ->assertOk()->assertSee('Reprocessar selecionadas')->assertSee('Reprocessar avaliação / indicação');
        $this->actingAs($admin)->withSession(['_token' => $token])->post(route('admin.ai-settings.reprocess'), [
            '_token' => $token,
            'execution_ids' => [$source->id], 'configuration' => 'current',
            'reason' => 'Provedor recuperado após indisponibilidade.', 'confirmed' => '1',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('ai_executions', 2);
    }

    public function test_enqueue_failure_rolls_back_round_and_preserves_source(): void
    {
        [$source, $admin] = $this->failedExecution();
        $dispatcher = \Mockery::mock(AiExecutionDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        $service = new AiManualReprocessing(app(AiSettingsService::class), app(\App\Services\Ai\EvaluationConfigurationFingerprint::class), $dispatcher);
        try {
            $service->request($source->id, $admin, 'current', 'Motivo válido para reprocessamento.');
            $this->fail('Queue failure should propagate.');
        } catch (\RuntimeException) {
            $this->assertDatabaseCount('ai_executions', 1);
            $this->assertNull($source->fresh()->superseded_at);
        }
    }

    public function test_database_queue_receives_the_new_round_atomically(): void
    {
        [$source, $admin] = $this->failedExecution();
        // Use the real database queue in the in-memory test DB; never a production queue.
        Queue::swap(Queue::getFacadeRoot()->queue);
        $child = $this->service()->request($source->id, $admin, 'current', 'Provedor recuperado para nova rodada.');
        $this->assertDatabaseCount('jobs', 1);
        $payload = json_decode(DB::table('jobs')->value('payload'), true);
        $this->assertSame('ai-evaluations', DB::table('jobs')->value('queue'));
        $job = unserialize($payload['data']['command']);
        $this->assertSame($child->id, $job->executionId);
    }

    public function test_selection_round_is_enqueued_and_does_not_edit_scores(): void
    {
        [$source, $admin] = $this->failedExecution();
        $registration = $source->registration;
        $registration->updateQuietly(['status' => RegistrationStatusEnum::Avaliado]);
        $selection = app(AiExecutionDispatcher::class)->create($registration, AiExecutionType::StrategicSelection);
        $selection->update(['status' => AiExecutionStatus::Failed, 'attempts' => 3]);
        $child = $this->service()->request($selection->id, $admin, 'current', 'Reprocessar indicação após falha externa.');
        $this->assertSame(AiExecutionType::StrategicSelection, $child->type);
        Queue::assertPushed(\App\Jobs\SelectRegistrationWithAi::class, fn ($job) => $job->executionId === $child->id);
        $this->assertDatabaseCount('scores', 0);
        $this->assertDatabaseCount('indications', 0);
    }

    public function test_previous_destination_cannot_receive_a_different_current_key(): void
    {
        [$source, $admin, $payload] = $this->failedExecution();
        app(AiSettingsService::class)->update(array_replace($payload, ['provider' => 'openai', 'api_key' => 'different-test-key']), $admin);
        $this->expectException(ValidationException::class);
        $this->service()->request($source->id, $admin, 'previous', 'Tentar destino anterior após mudança.');
    }

    public function test_late_failure_callback_does_not_modify_superseded_parent(): void
    {
        [$source, $admin] = $this->failedExecution();
        $this->service()->request($source->id, $admin, 'current', 'Nova rodada após falha temporária.');
        app(\App\Services\Ai\AiExecutionManager::class)->fail($source, new \RuntimeException('Late error'));
        $this->assertSame('PROVIDER_UNAVAILABLE', $source->fresh()->error_code);
    }

    public function test_batch_preserves_success_when_another_item_is_not_eligible(): void
    {
        [$source, $admin] = $this->failedExecution();
        $registration = $source->registration->replicate();
        $registration->saveQuietly();
        $notExhausted = app(AiExecutionDispatcher::class)->create($registration, $source->type);
        $notExhausted->update(['status' => AiExecutionStatus::Failed, 'attempts' => 1]);
        $token = 'batch-reprocessing-test-token';
        $this->actingAs($admin)->withSession(['_token' => $token])->post(route('admin.ai-settings.reprocess'), [
            '_token' => $token, 'execution_ids' => [$source->id, $notExhausted->id],
            'configuration' => 'current', 'reason' => 'Reprocessar lote após correção do provedor.', 'confirmed' => '1',
        ])->assertRedirect()->assertSessionHas('success')->assertSessionHas('error');
        $this->assertDatabaseCount('ai_executions', 3);
        $this->assertNull($notExhausted->fresh()->superseded_at);
        Queue::assertPushed(EvaluateRegistrationWithAi::class, 1);
    }

    private function service(): AiManualReprocessing
    {
        return app(AiManualReprocessing::class);
    }

    private function failedExecution(): array
    {
        [$registration, $evaluator, , $selector] = $this->domain();
        $admin = User::factory()->create(['is_judge' => false, 'is_organizer' => false]);
        $admin->roles()->attach(Role::firstOrCreate(['name' => 'admin'], ['active' => true]));
        $payload = [
            'provider' => 'gemini', 'model' => 'gemini-original', 'api_key' => 'test-only-key',
            'technical_prompt' => null, 'selection_prompt' => null,
            'evaluation_enabled' => true, 'selection_enabled' => true,
            'technical_evaluator_id' => $evaluator->id, 'selection_evaluator_id' => $selector->id,
            'connect_timeout' => 5, 'timeout' => 60, 'tries' => 3, 'knowledge_version' => null,
        ];
        app(AiSettingsService::class)->update($payload, $admin);
        $registration->updateQuietly(['status' => RegistrationStatusEnum::Habilitado]);
        $source = app(AiExecutionDispatcher::class)->create($registration, AiExecutionType::TechnicalEvaluation);
        $source->update(['status' => AiExecutionStatus::Failed, 'attempts' => 3, 'error_code' => 'PROVIDER_UNAVAILABLE', 'error_message' => 'Provedor indisponível.']);

        return [$source, $admin, $payload];
    }

    /** @return array{Registration, User, int, User} */
    private function domain(int $requiredOpinions = 1): array
    {
        $evaluator = User::factory()->create();
        $selector = User::factory()->create();
        $now = now();
        $edition = DB::table('editions')->insertGetId([
            'title' => 'Edição', 'regulation' => 'Regulamento', 'regulation_file_path' => '',
            'registration_start' => $now, 'registration_end' => $now, 'grant_date' => $now,
            'judgment_date' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $modality = DB::table('modalities')->insertGetId([
            'title' => 'Modalidade', 'edition_id' => $edition, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $category = DB::table('categories')->insertGetId([
            'title' => 'Categoria', 'acronym' => 'CAT', 'modality_id' => $modality,
            'evaluation_mode' => $requiredOpinions > 1 ? 'human_only' : 'hybrid',
            'human_evaluations_required' => $requiredOpinions,
            'indication_mode' => 'ai_only', 'human_indications_required' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $candidate = DB::table('candidates')->insertGetId([
            'nome' => 'Candidato', 'cpf' => '12345678901', 'dt_nascimento' => '1990-01-01',
            'rg' => '1', 'rg_expeditor' => 'SSP', 'rg_uf' => 'AM', 'sexo' => 'X', 'cep' => '69000-000',
            'ufendereco' => 'AM', 'cidade' => 'Manaus', 'endereco' => 'Rua', 'numero' => '1',
            'ddd' => '92', 'celular' => '999999999', 'email' => 'candidate@example.test',
            'resumo_curricular' => 'Currículo', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $registration = Registration::query()->create([
            'candidate_id' => $candidate, 'category_id' => $category, 'title' => 'Proposta teste',
            'resumo' => 'Resumo teste', 'objetivo' => 'Objetivo teste',
            'desenvolvimento' => 'Desenvolvimento teste', 'conclusao' => 'Conclusão teste',
            'status' => RegistrationStatusEnum::Inscrito,
        ]);
        $criterion = DB::table('evaluation_criteria')->insertGetId([
            'category_id' => $category, 'name' => 'Impacto regional', 'description' => 'Impacto demonstrado',
            'weight' => 2, 'min_score' => 0, 'max_score' => 10,
            'rubric' => json_encode(['4' => 'Atende bem']), 'rubric_version' => 'v1',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('evaluators')->insert([
            'user_id' => $evaluator->id,
            'category_id' => $category,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('indicators')->insert([
            'user_id' => $selector->id,
            'category_id' => $category,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$registration, $evaluator, $criterion, $selector];
    }
}
