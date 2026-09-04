<?php

namespace App\Data\Ai;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class EvaluationResultData
{
    /** @param array<int, array{criterio_id: int, nota: int, justificativa: string}> $criteria */
    public function __construct(
        public int $registrationId,
        public int $evaluatorId,
        public string $promptVersion,
        public string $provider,
        public string $model,
        public array $criteria,
    ) {}

    /** @throws ValidationException */
    public static function fromArray(array $payload, EvaluationRequestData $request): self
    {
        $validated = Validator::make($payload, [
            'inscricao_id' => ['required', 'integer', 'in:'.$request->registrationId],
            'avaliador_id' => ['required', 'integer', 'in:'.$request->evaluatorId],
            'prompt_version' => ['required', 'string', 'in:'.$request->promptVersion],
            'provider' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:120'],
            'criterios' => ['required', 'array', 'size:'.count($request->criteria)],
            'criterios.*.criterio_id' => ['required', 'integer', 'distinct'],
            'criterios.*.nota' => ['required', 'integer'],
            'criterios.*.justificativa' => ['required', 'string', 'min:10', 'max:2000'],
        ])->validate();

        $expected = collect($request->criteria)->keyBy('criterio_id');
        foreach ($validated['criterios'] as $result) {
            $criterion = $expected->get($result['criterio_id']);
            if ($criterion === null
                || $result['nota'] < $criterion['nota_minima']
                || $result['nota'] > $criterion['nota_maxima']) {
                throw ValidationException::withMessages(['criterios' => 'Critério ou nota inválida na resposta da IA.']);
            }
        }

        if (collect($validated['criterios'])->pluck('criterio_id')->sort()->values()->all()
            !== $expected->keys()->sort()->values()->all()) {
            throw ValidationException::withMessages(['criterios' => 'A resposta deve conter exatamente os critérios solicitados.']);
        }

        return new self(
            $validated['inscricao_id'],
            $validated['avaliador_id'],
            $validated['prompt_version'],
            $validated['provider'],
            $validated['model'],
            $validated['criterios'],
        );
    }
}
