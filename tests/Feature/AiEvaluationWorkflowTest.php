<?php

namespace Tests\Feature;

use App\Data\Ai\EvaluationRequestData;
use App\Data\Ai\EvaluationResultData;
use App\Data\Ai\SelectionResultData;
use App\Enum\AiExecutionStatus;
use App\Enum\RegistrationStatusEnum;
use App\Jobs\EvaluateRegistrationWithAi;
use App\Jobs\SelectRegistrationWithAi;
use App\Models\AiExecution;
use App\Models\Registration;
use App\Models\User;
use App\Services\Ai\AiExecutionManager;
use App\Services\EvaluationCompletionService;
use App\Services\OpinionSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AiEvaluationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_relevant_status_transitions_dispatch_jobs(): void
    {
        [$registration, $evaluator, , $selector] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.selection_evaluator_id' => $selector->id,
            'ai_evaluation.selection_enabled' => true,
        ]);

        $registration->update(['status' => RegistrationStatusEnum::Rejeitado]);
        Queue::assertNothingPushed();
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        Queue::assertPushed(EvaluateRegistrationWithAi::class, 1);
        $registration->update(['status' => RegistrationStatusEnum::Avaliado]);
        Queue::assertPushed(SelectRegistrationWithAi::class, 1);
    }

    public function test_disabled_feature_does_not_dispatch(): void
    {
        [$registration] = $this->domain();
        Queue::fake();
        config(['ai_evaluation.enabled' => false]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        Queue::assertNothingPushed();
    }

    public function test_repeated_transition_is_idempotent(): void
    {
        [$registration, $evaluator] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $registration->update(['status' => RegistrationStatusEnum::Inscrito]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        Queue::assertPushed(EvaluateRegistrationWithAi::class, 2);
        $this->assertDatabaseCount('ai_executions', 1);
    }

    public function test_valid_response_is_persisted_and_laravel_calculates_result(): void
    {
        [$registration, $evaluator, $criterion] = $this->domain();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        Http::fake(['*/v1/evaluations/technical' => Http::response([
            'inscricao_id' => $registration->id,
            'avaliador_id' => $evaluator->id,
            'prompt_version' => 'technical_evaluator_v1',
            'provider' => 'gemini',
            'model' => 'gemini-test',
            'criterios' => [[
                'criterio_id' => $criterion, 'nota' => 4,
                'justificativa' => $this->aiJustification(),
            ]],
        ])]);

        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->firstOrFail();
        app()->call([new EvaluateRegistrationWithAi($execution->id), 'handle']);

        Http::assertSent(fn ($request): bool => $request['inscricao']['titulo'] === 'Proposta teste'
            && ! isset($request['inscricao']['candidate_id']) && ! isset($request['criterios'][0]['weight']));
        $this->assertDatabaseHas('scores', ['evaluation_criterion_id' => $criterion, 'valor' => 4]);
        $this->assertSame(40, $registration->refresh()->evaluation_avg);
        $this->assertSame(AiExecutionStatus::Completed, $execution->refresh()->status);
        $this->assertSame('gemini', $execution->provider);
        $this->assertSame('gemini-test', $execution->model);

        app()->call([new EvaluateRegistrationWithAi($execution->id), 'handle']);
        Http::assertSentCount(1);
    }

    public function test_empty_rubric_is_serialized_as_json_object(): void
    {
        [$registration, $evaluator, $criterion] = $this->domain();
        DB::table('evaluation_criteria')->where('id', $criterion)->update(['rubric' => null]);

        $request = EvaluationRequestData::fromRegistration(
            $registration->fresh(),
            $evaluator->id,
            (string) str()->uuid(),
        );

        $this->assertInstanceOf(\stdClass::class, $request->criteria[0]['rubrica']);
        $this->assertStringContainsString('"rubrica":{}', json_encode($request->toArray(), JSON_THROW_ON_ERROR));
    }

    public function test_invalid_or_missing_criterion_response_is_rejected(): void
    {
        [$registration, $evaluator] = $this->domain();
        $request = EvaluationRequestData::fromRegistration($registration, $evaluator->id, 'test-correlation');
        $this->expectException(ValidationException::class);
        EvaluationResultData::fromArray([
            'inscricao_id' => $registration->id,
            'avaliador_id' => $evaluator->id,
            'prompt_version' => 'technical_evaluator_v1',
            'provider' => 'openai',
            'model' => 'openai-test',
            'criterios' => [[
                'criterio_id' => 9999, 'nota' => 11,
                'justificativa' => $this->aiJustification(),
            ]],
        ], $request);
    }

    public function test_quorum_only_completes_after_three_valid_opinions(): void
    {
        [$registration, $firstEvaluator, $criterion] = $this->domain(3);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $service = app(OpinionSubmissionService::class);
        $evaluators = [$firstEvaluator, $this->evaluatorFor($registration), $this->evaluatorFor($registration)];

        foreach ($evaluators as $index => $evaluator) {
            $service->submit($registration->refresh(), $evaluator, [[
                'criterion_id' => $criterion,
                'score' => 5,
                'justification' => 'Justificativa completa para avaliação de quorum.',
            ]]);
            $expectedStatus = $index < 2
                ? RegistrationStatusEnum::Habilitado->value
                : RegistrationStatusEnum::Avaliado->value;
            $this->assertSame($expectedStatus, (int) $registration->refresh()->status);
        }
    }

    public function test_result_is_not_persisted_after_registration_status_changes(): void
    {
        [$registration, $evaluator] = $this->domain();
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->create([
            'registration_id' => $registration->id,
            'evaluator_id' => $evaluator->id,
            'type' => \App\Enum\AiExecutionType::TechnicalEvaluation,
            'status' => AiExecutionStatus::Processing,
            'correlation_id' => (string) str()->uuid(),
            'prompt_version' => 'race-test-v1',
        ]);
        $request = EvaluationRequestData::fromRegistration($registration, $evaluator->id, $execution->correlation_id);
        $result = EvaluationResultData::fromArray([
            'inscricao_id' => $registration->id,
            'avaliador_id' => $evaluator->id,
            'prompt_version' => 'technical_evaluator_v1',
            'provider' => 'openai',
            'model' => 'openai-test',
            'criterios' => [[
                'criterio_id' => $request->criteria[0]['criterio_id'],
                'nota' => 5,
                'justificativa' => $this->aiJustification(),
            ]],
        ], $request);
        $registration->update(['status' => RegistrationStatusEnum::Rejeitado]);

        $persisted = app(AiExecutionManager::class)->completeEvaluation($execution, $request, $result, 10);

        $this->assertFalse($persisted);
        $this->assertSame(RegistrationStatusEnum::Rejeitado->value, (int) $registration->refresh()->status);
        $this->assertDatabaseCount('opinions', 0);
        $this->assertDatabaseCount('scores', 0);
        $this->assertSame(AiExecutionStatus::Failed, $execution->refresh()->status);
    }

    public function test_incomplete_opinion_does_not_count_for_quorum(): void
    {
        [$registration, $evaluator] = $this->domain();
        DB::table('opinions')->insert([
            'user_id' => $evaluator->id,
            'registration_id' => $registration->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertFalse(app(EvaluationCompletionService::class)->hasRequiredQuorum($registration));
    }

    public function test_unauthorized_evaluator_cannot_submit_opinion(): void
    {
        [$registration, , $criterion] = $this->domain();

        $this->expectException(ValidationException::class);
        app(OpinionSubmissionService::class)->submit($registration, User::factory()->create(), [[
            'criterion_id' => $criterion,
            'score' => 5,
            'justification' => 'Justificativa válida, mas avaliador não autorizado.',
        ]]);
    }

    public function test_score_outside_criterion_scale_is_rejected(): void
    {
        [$registration, $evaluator, $criterion] = $this->domain();

        $this->expectException(ValidationException::class);
        app(OpinionSubmissionService::class)->submit($registration, $evaluator, [[
            'criterion_id' => $criterion,
            'score' => 11,
            'justification' => 'Justificativa válida com uma nota fora da escala.',
        ]]);
    }

    public function test_database_prevents_duplicate_opinion_for_same_evaluator(): void
    {
        [$registration, $evaluator, $criterion] = $this->domain(3);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $service = app(OpinionSubmissionService::class);
        $score = [[
            'criterion_id' => $criterion,
            'score' => 5,
            'justification' => 'Justificativa completa para testar a unicidade.',
        ]];
        $service->submit($registration, $evaluator, $score);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        $service->submit($registration->refresh(), $evaluator, $score);
    }

    public function test_queued_job_honors_disabled_feature_flag(): void
    {
        [$registration, $evaluator] = $this->domain();
        Queue::fake();
        config(['ai_evaluation.enabled' => true, 'ai_evaluation.technical_evaluator_id' => $evaluator->id]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->firstOrFail();
        config(['ai_evaluation.enabled' => false]);
        Http::fake();

        app()->call([new EvaluateRegistrationWithAi($execution->id), 'handle']);

        Http::assertNothingSent();
        $this->assertSame(AiExecutionStatus::Pending, $execution->refresh()->status);
    }

    public function test_unauthorized_ai_response_is_not_retried_by_job(): void
    {
        [$registration, $evaluator] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->firstOrFail();
        Http::fake(['*' => Http::response([], 401)]);

        app()->call([new EvaluateRegistrationWithAi($execution->id), 'handle']);

        Http::assertSentCount(1);
        $this->assertSame(AiExecutionStatus::Failed, $execution->refresh()->status);
        $this->assertSame('AI_UNAUTHORIZED', $execution->error_code);
    }

    public function test_llm_configuration_error_is_safe_and_not_retried_by_job(): void
    {
        [$registration, $evaluator] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->firstOrFail();
        Http::fake(['*' => Http::response([
            'code' => 'LLM_CONFIGURATION_ERROR',
            'detail' => 'Required credential is not configured: GEMINI_API_KEY',
        ], 500)]);

        app()->call([new EvaluateRegistrationWithAi($execution->id), 'handle']);

        Http::assertSentCount(1);
        $this->assertSame(AiExecutionStatus::Failed, $execution->refresh()->status);
        $this->assertSame('LLM_CONFIGURATION_ERROR', $execution->error_code);
        $this->assertSame(
            'O provedor de IA está com configuração incompleta ou inválida.',
            $execution->error_message,
        );
    }

    public function test_divergent_prompt_version_is_rejected(): void
    {
        [$registration, $evaluator, $criterion] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->firstOrFail();
        Http::fake(['*' => Http::response([
            'inscricao_id' => $registration->id,
            'avaliador_id' => $evaluator->id,
            'prompt_version' => 'technical_evaluator_v999',
            'provider' => 'openai',
            'model' => 'openai-test',
            'criterios' => [[
                'criterio_id' => $criterion,
                'nota' => 5,
                'justificativa' => $this->aiJustification(),
            ]],
        ])]);

        app()->call([new EvaluateRegistrationWithAi($execution->id), 'handle']);

        $this->assertDatabaseCount('opinions', 0);
        $this->assertSame('AI_INVALID_RESPONSE', $execution->refresh()->error_code);
    }

    public function test_changed_evaluation_configuration_is_not_persisted(): void
    {
        [$registration, $evaluator, $criterionId] = $this->domain();
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $request = EvaluationRequestData::fromRegistration($registration, $evaluator->id, (string) str()->uuid());
        $execution = AiExecution::query()->create([
            'registration_id' => $registration->id,
            'evaluator_id' => $evaluator->id,
            'type' => \App\Enum\AiExecutionType::TechnicalEvaluation,
            'status' => AiExecutionStatus::Processing,
            'correlation_id' => $request->correlationId,
            'prompt_version' => $request->promptVersion,
            'evaluation_configuration_hash' => $request->configurationHash,
        ]);
        $result = EvaluationResultData::fromArray([
            'inscricao_id' => $registration->id,
            'avaliador_id' => $evaluator->id,
            'prompt_version' => $request->promptVersion,
            'provider' => 'openai',
            'model' => 'openai-test',
            'criterios' => [[
                'criterio_id' => $criterionId,
                'nota' => 5,
                'justificativa' => $this->aiJustification(),
            ]],
        ], $request);
        DB::table('evaluation_criteria')->where('id', $criterionId)->update(['description' => 'Descrição alterada']);

        $persisted = app(AiExecutionManager::class)->completeEvaluation($execution, $request, $result, 10);

        $this->assertFalse($persisted);
        $this->assertDatabaseCount('opinions', 0);
        $this->assertSame('AI_CONFIGURATION_CHANGED', $execution->refresh()->error_code);
    }

    public function test_transient_service_failures_can_be_retried_until_success(): void
    {
        [$registration, $evaluator, $criterion] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.technical_evaluator_id' => $evaluator->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        $execution = AiExecution::query()->firstOrFail();
        Http::fakeSequence()
            ->push([], 503)
            ->push([], 503)
            ->push([
                'inscricao_id' => $registration->id,
                'avaliador_id' => $evaluator->id,
                'prompt_version' => 'technical_evaluator_v1',
                'provider' => 'openai',
                'model' => 'openai-test',
                'criterios' => [[
                    'criterio_id' => $criterion,
                    'nota' => 5,
                    'justificativa' => $this->aiJustification(),
                ]],
            ]);
        $job = new EvaluateRegistrationWithAi($execution->id);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                app()->call([$job, 'handle']);
            } catch (\Illuminate\Http\Client\RequestException $exception) {
                $this->assertLessThan(3, $attempt);
            }
        }

        Http::assertSentCount(3);
        $this->assertSame(AiExecutionStatus::Completed, $execution->refresh()->status);
        $this->assertDatabaseCount('opinions', 1);
    }

    public function test_strategic_selection_does_not_modify_existing_scores(): void
    {
        [$registration, $evaluator, $criterion, $selector] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.selection_enabled' => true,
            'ai_evaluation.selection_evaluator_id' => $selector->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        app(OpinionSubmissionService::class)->submit($registration, $evaluator, [[
            'criterion_id' => $criterion,
            'score' => 5,
            'justification' => 'Justificativa técnica que deve permanecer inalterada.',
        ]]);
        $execution = AiExecution::query()->where('type', \App\Enum\AiExecutionType::StrategicSelection)->firstOrFail();
        Http::fake(['*/v1/evaluations/selection' => Http::response([
            'inscricao_id' => $registration->id,
            'prompt_version' => 'selection_reviewer_v1',
            'provider' => 'openai',
            'model' => 'openai-selection-test',
            'indicacao' => 'INDICADA',
            'justificativa' => $this->aiJustification(),
        ])]);

        app()->call([new SelectRegistrationWithAi($execution->id), 'handle']);

        $this->assertDatabaseHas('scores', ['evaluation_criterion_id' => $criterion, 'valor' => 5]);
        $this->assertDatabaseHas('indications', [
            'registration_id' => $registration->id,
            'user_id' => $selector->id,
            'decision' => 'INDICADA',
        ]);
        $this->assertDatabaseCount('scores', 1);
    }

    public function test_strategic_selection_updates_existing_indication_without_duplicates(): void
    {
        [$registration, $evaluator, $criterion, $selector] = $this->domain();
        Queue::fake();
        config([
            'ai_evaluation.enabled' => true,
            'ai_evaluation.selection_enabled' => true,
            'ai_evaluation.selection_evaluator_id' => $selector->id,
            'ai_evaluation.token' => 'test-token',
        ]);
        $registration->update(['status' => RegistrationStatusEnum::Habilitado]);
        app(OpinionSubmissionService::class)->submit($registration, $evaluator, [[
            'criterion_id' => $criterion,
            'score' => 5,
            'justification' => 'Justificativa técnica que deve permanecer inalterada.',
        ]]);
        $execution = AiExecution::query()->where('type', \App\Enum\AiExecutionType::StrategicSelection)->firstOrFail();
        Http::fake(['*/v1/evaluations/selection' => Http::response([
            'inscricao_id' => $registration->id,
            'prompt_version' => 'selection_reviewer_v1',
            'provider' => 'openai',
            'model' => 'openai-selection-test',
            'indicacao' => 'INDICADA',
            'justificativa' => $this->aiJustification(),
        ])]);

        $existing = $registration->indications()->create([
            'user_id' => $selector->id,
            'decision' => 'NAO_INDICADA',
            'descricao' => 'Justificativa anterior.',
        ]);

        app()->call([new SelectRegistrationWithAi($execution->id), 'handle']);
        app()->call([new SelectRegistrationWithAi($execution->id), 'handle']);

        $this->assertDatabaseCount('indications', 1);
        $this->assertSame($existing->id, $execution->refresh()->indication_id);
        $this->assertSame(AiExecutionStatus::Completed, $execution->status);
        $this->assertSame($this->aiJustification(), $existing->refresh()->descricao);
        Http::assertSentCount(1);

        $this->assertDatabaseHas('scores', ['evaluation_criterion_id' => $criterion, 'valor' => 5]);
        $this->assertDatabaseHas('indications', [
            'registration_id' => $registration->id,
            'user_id' => $selector->id,
            'decision' => 'INDICADA',
        ]);
        $this->assertDatabaseCount('scores', 1);
    }

    public function test_strategic_result_is_discarded_after_registration_context_changes(): void
    {
        [$registration, , , $selector] = $this->domain();
        $registration->update(['status' => RegistrationStatusEnum::Avaliado]);
        $execution = AiExecution::query()->create([
            'registration_id' => $registration->id,
            'evaluator_id' => $selector->id,
            'type' => \App\Enum\AiExecutionType::StrategicSelection,
            'status' => AiExecutionStatus::Processing,
            'correlation_id' => (string) str()->uuid(),
            'prompt_version' => 'selection_reviewer_v1',
            'evaluation_configuration_hash' => hash('sha256', 'selection_reviewer_v1'),
        ]);
        $result = new SelectionResultData(
            $registration->id,
            'selection_reviewer_v1',
            'gemini',
            'gemini-test',
            \App\Enum\SelectionDecision::Indicated,
            $this->aiJustification(),
        );
        $registration->update(['status' => RegistrationStatusEnum::Rejeitado]);

        $request = \App\Data\Ai\SelectionRequestData::fromRegistration(
            $registration->fresh(),
            $selector->id,
            $execution->correlation_id,
            $execution->prompt_version,
            $execution->evaluation_configuration_hash,
        );
        app(AiExecutionManager::class)->completeSelection($execution, $request, $result, 10);

        $this->assertDatabaseCount('indications', 0);
        $this->assertSame(AiExecutionStatus::Failed, $execution->refresh()->status);
        $this->assertSame('AI_SELECTION_CONTEXT_CHANGED', $execution->error_code);
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
            'evaluations_count' => $requiredOpinions, 'created_at' => $now, 'updated_at' => $now,
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

    private function evaluatorFor(Registration $registration): User
    {
        $evaluator = User::factory()->create();
        DB::table('evaluators')->insert([
            'user_id' => $evaluator->id,
            'category_id' => $registration->category_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $evaluator;
    }

    private function aiJustification(): string
    {
        return implode(' ', array_fill(0, 10, 'A proposta apresenta evidências concretas, coerentes e suficientes para sustentar tecnicamente a decisão atribuída.'));
    }
}
