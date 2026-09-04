<?php

namespace App\Data\Ai;

use App\Models\Registration;

final readonly class SelectionRequestData
{
    public function __construct(
        public int $registrationId,
        public int $evaluatorId,
        public string $correlationId,
        public string $promptVersion,
        public string $configurationHash,
        public array $registration,
        public array $evaluations,
    ) {}

    public static function fromRegistration(
        Registration $registration,
        int $evaluatorId,
        string $correlationId,
        ?string $promptVersion = null,
        ?string $configurationHash = null,
    ): self {
        $registration->loadMissing('opinions.scores.evaluationCriterion');

        return new self(
            $registration->id,
            $evaluatorId,
            $correlationId,
            $promptVersion ?? (string) config('ai_evaluation.prompts.selection'),
            $configurationHash ?? app(\App\Services\Ai\EvaluationConfigurationFingerprint::class)->forSelection($registration),
            [
                'titulo' => $registration->title,
                'resumo' => $registration->resumo,
                'objetivo' => $registration->objetivo,
                'desenvolvimento' => $registration->desenvolvimento,
                'conclusao' => $registration->conclusao,
            ],
            $registration->opinions->flatMap(fn ($opinion) => $opinion->scores->map(fn ($score): array => [
                'criterio_id' => $score->evaluation_criterion_id,
                'criterio' => $score->evaluationCriterion?->name,
                'nota' => $score->valor,
                'justificativa' => $score->descricao,
            ]))->values()->all(),
        );
    }

    public function toArray(): array
    {
        return [
            'inscricao_id' => $this->registrationId,
            'avaliador_id' => $this->evaluatorId,
            'correlation_id' => $this->correlationId,
            'prompt_version' => $this->promptVersion,
            'evaluation_configuration_hash' => $this->configurationHash,
            'inscricao' => $this->registration,
            'avaliacoes' => $this->evaluations,
        ];
    }
}
