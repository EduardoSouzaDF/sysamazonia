<?php

namespace App\Data\Ai;

final readonly class AiSettingsData
{
    public function __construct(
        public ?int $id, public ?int $versionId, public string $provider, public string $model,
        public ?string $apiKey, public ?string $technicalPrompt, public ?string $selectionPrompt,
        public string $technicalPromptVersion, public string $selectionPromptVersion,
        public bool $evaluationEnabled, public bool $selectionEnabled,
        public int $technicalEvaluatorId, public int $selectionEvaluatorId,
        public int $connectTimeout, public int $timeout, public int $tries, public ?string $knowledgeVersion,
    ) {}
}
