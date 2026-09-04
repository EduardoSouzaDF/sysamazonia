<?php

namespace App\Data\Ai;

use App\Models\Registration;

final readonly class EvaluationRequestData
{
    /** @param array<int, array{criterio_id: int, nome: string, descricao: ?string, nota_minima: float, nota_maxima: float, rubrica: object, rubric_version: ?string}> $criteria */
    public function __construct(
        public int $registrationId,
        public int $evaluatorId,
        public string $correlationId,
        public string $promptVersion,
        public string $configurationHash,
        public array $registration,
        public array $criteria,
    ) {}

    public static function fromRegistration(
        Registration $registration,
        int $evaluatorId,
        string $correlationId,
        ?string $promptVersion = null,
        ?string $configurationHash = null,
    ): self {
        $registration->loadMissing('category.evaluationCriteria');

        return new self(
            $registration->id,
            $evaluatorId,
            $correlationId,
            $promptVersion ?? (string) config('ai_evaluation.prompts.technical'),
            $configurationHash ?? app(\App\Services\Ai\EvaluationConfigurationFingerprint::class)->forRegistration($registration),
            [
                'titulo' => $registration->title,
                'resumo' => $registration->resumo,
                'objetivo' => $registration->objetivo,
                'desenvolvimento' => $registration->desenvolvimento,
                'conclusao' => $registration->conclusao,
            ],
            $registration->category->evaluationCriteria->map(fn ($criterion): array => [
                'criterio_id' => $criterion->id,
                'nome' => $criterion->name,
                'descricao' => $criterion->description,
                'nota_minima' => (float) $criterion->min_score,
                'nota_maxima' => (float) $criterion->max_score,
                'rubrica' => (object) ($criterion->rubric ?? []),
                'rubric_version' => $criterion->rubric_version,
            ])->values()->all(),
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
            'criterios' => $this->criteria,
        ];
    }
}
