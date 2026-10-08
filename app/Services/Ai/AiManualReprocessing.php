<?php

namespace App\Services\Ai;

use App\Enum\AiExecutionStatus;
use App\Enum\AiExecutionType;
use App\Enum\RegistrationStatusEnum;
use App\Models\AiExecution;
use App\Models\Indication;
use App\Models\Opinion;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiManualReprocessing
{
    public function __construct(private AiSettingsService $settings, private EvaluationConfigurationFingerprint $fingerprint, private AiExecutionDispatcher $dispatcher) {}

    public function request(int $sourceId, User $actor, string $configuration, string $reason): AiExecution
    {
        abort_unless($actor->isAdmin(), 403);
        if (! in_array($configuration, ['previous', 'current'], true) || mb_strlen(trim($reason)) < 10 || mb_strlen($reason) > 1000) {
            $this->reject('Informe a configuração e um motivo entre 10 e 1000 caracteres.');
        }
        // Enqueue atomically in the same DB transaction; sync would execute in the browser.
        $defaultDb = config('database.default');
        if (config('queue.default') !== 'database' || (config('queue.connections.database.connection') ?: $defaultDb) !== $defaultDb || config('queue.connections.database.after_commit', false)) {
            $this->reject('Reprocessamento exige fila database na mesma conexão da aplicação e after_commit=false.');
        }
        $source = AiExecution::query()->findOrFail($sourceId);
        // Shared with processors: a failed request still finishing cannot race with this action.
        $lock = Cache::lock('ai-registration-process-'.$source->registration_id.'-'.$source->type->value, 120);
        if (! $lock->get()) {
            $this->reject('A inscrição ainda está sendo processada. Aguarde e tente novamente.');
        }
        try {
            return DB::transaction(function () use ($sourceId, $actor, $configuration, $reason): AiExecution {
                $source = AiExecution::query()->findOrFail($sourceId);
                $registration = Registration::query()->lockForUpdate()->findOrFail($source->registration_id);
                $source = AiExecution::query()->lockForUpdate()->findOrFail($sourceId);
                $current = $this->settings->current();
                if ($source->status !== AiExecutionStatus::Failed || $source->attempts < $current->tries || $source->superseded_at) {
                    $this->reject('Somente a rodada com falha e tentativas esgotadas pode ser reprocessada.');
                }
                // Require the latest round across the stage, including changes of configuration.
                $latest = AiExecution::query()->where('registration_id', $registration->id)->where('type', $source->type)->latest('id')->first();
                if ($latest?->id !== $source->id) {
                    $this->reject('Existe uma execução mais recente para esta etapa.');
                }
                $technical = $source->type === AiExecutionType::TechnicalEvaluation;
                $expectedStatus = $technical ? RegistrationStatusEnum::Habilitado->value : RegistrationStatusEnum::Avaliado->value;
                $evaluatorId = $technical ? $current->technicalEvaluatorId : $current->selectionEvaluatorId;
                $enabled = $technical ? $current->evaluationEnabled : $current->selectionEnabled;
                $category = $registration->category;
                $allowed = $category && ($technical ? $category->allowsAiEvaluation() : $category->allowsAiIndication());
                $authorized = $category && ($technical ? $category->evaluators() : $category->indicators())->whereKey($source->evaluator_id)->exists();
                if ((int) $registration->status !== $expectedStatus || ! $enabled || ! $allowed || ! $authorized || $evaluatorId !== $source->evaluator_id) {
                    $this->reject('Confira status, ativador, política e vínculo do usuário técnico da execução.');
                }
                $exists = $technical
                    ? Opinion::query()->where('registration_id', $registration->id)->where('user_id', $source->evaluator_id)->exists()
                    : Indication::query()->where('registration_id', $registration->id)->where('user_id', $source->evaluator_id)->exists();
                if ($exists || AiExecution::query()->where('registration_id', $registration->id)->where('type', $source->type)->whereIn('status', ['pending', 'processing', 'completed'])->exists()) {
                    $this->reject('Já existe resultado ou execução ativa/concluída para esta etapa.');
                }
                $resolved = $configuration === 'previous' ? $this->settings->forCorrelation($source->correlation_id) : $current;
                if (! $resolved->versionId || ! $resolved->model || ! $resolved->provider) {
                    $this->reject('Salve uma configuração versionada no painel antes de reprocessar.');
                }
                if ($configuration === 'previous' && ! $source->ai_setting_version_id) {
                    $this->reject('A execução não tem configuração anterior versionada. Escolha a atual.');
                }
                if ($resolved->provider !== 'local' && ! $resolved->apiKey) {
                    $this->reject('A configuração escolhida não tem uma credencial atual compatível com seu destino.');
                }
                $hash = $technical ? $this->fingerprint->forRegistration($registration) : $this->fingerprint->forSelection($registration);
                if ($configuration === 'previous' && $hash !== $source->evaluation_configuration_hash) {
                    $this->reject('Os critérios/contexto mudaram. Use a configuração atual.');
                }
                $round = (int) AiExecution::query()->where('registration_id', $registration->id)->where('type', $source->type)->max('retry_round') + 1;
                $execution = AiExecution::query()->create([
                    'registration_id' => $registration->id, 'evaluator_id' => $source->evaluator_id,
                    'type' => $source->type, 'status' => AiExecutionStatus::Pending,
                    'correlation_id' => (string) str()->uuid(), 'retry_round' => $round,
                    'parent_execution_id' => $source->id, 'requested_by' => $actor->id,
                    'requested_at' => now(), 'retry_reason' => trim($reason), 'retry_configuration' => $configuration,
                    'ai_setting_version_id' => $resolved->versionId,
                    'prompt_version' => $technical ? $resolved->technicalPromptVersion : $resolved->selectionPromptVersion,
                    'evaluation_configuration_hash' => $hash, 'knowledge_version' => $resolved->knowledgeVersion,
                ]);
                $source->update(['superseded_at' => now()]);
                // database jobs insertion rolls back with the new round if enqueue fails.
                $this->dispatcher->dispatch($execution);

                return $execution;
            }, 3);
        } finally {
            $lock->release();
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['reprocessing' => $message]);
    }
}
