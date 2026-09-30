<?php

namespace App\Services\Ai;

use App\Data\Ai\AiSettingsData;
use App\Enum\AiExecutionStatus;
use App\Enum\RegistrationStatusEnum;
use App\Models\AiExecution;
use App\Models\Category;
use App\Models\Registration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AiWorkflowDashboard
{
    public function summary(AiSettingsData $settings): array
    {
        $registrations = Registration::query()
            ->whereHas('category', fn ($query) => $query->where('is_honorific', false))
            ->whereIn('status', [
                RegistrationStatusEnum::Habilitado->value,
                RegistrationStatusEnum::Avaliado->value,
                RegistrationStatusEnum::Agraciado->value,
            ])
            ->with([
                'category:id,title,is_honorific,evaluation_mode,human_evaluations_required,indication_mode,human_indications_required',
                'opinions:id,registration_id,source',
                'indications:id,registration_id,source,decision',
            ])->get();

        $technical = ['completed' => 0, 'awaiting_human' => 0, 'awaiting_ai' => 0];
        $selection = ['completed' => 0, 'awaiting_human' => 0, 'awaiting_ai' => 0, 'indicated' => 0, 'not_indicated' => 0];
        $byCategory = [];

        foreach ($registrations as $registration) {
            $category = $registration->category;
            if ($category === null) {
                continue;
            }
            $categoryRow = $byCategory[$category->id] ?? [
                'category' => $category->title,
                'enabled' => 0,
                'evaluated' => 0,
                'awaiting_human' => 0,
                'awaiting_ai' => 0,
                'selection_pending' => 0,
            ];

            if ((int) $registration->status === RegistrationStatusEnum::Habilitado->value) {
                $categoryRow['enabled']++;
                [$humanMissing, $aiMissing] = $this->technicalMissing($registration);
                $technical['awaiting_human'] += (int) $humanMissing;
                $technical['awaiting_ai'] += (int) $aiMissing;
                $categoryRow['awaiting_human'] += (int) $humanMissing;
                $categoryRow['awaiting_ai'] += (int) $aiMissing;
            } else {
                $technical['completed']++;
                $categoryRow['evaluated']++;
                [$humanMissing, $aiMissing] = $this->selectionMissing($registration);
                $selection['awaiting_human'] += (int) $humanMissing;
                $selection['awaiting_ai'] += (int) $aiMissing;
                $categoryRow['selection_pending'] += (int) ($humanMissing || $aiMissing);
                if (! $humanMissing && ! $aiMissing) {
                    $selection['completed']++;
                }
                $selection['indicated'] += (int) $registration->indications->contains(fn ($item) => $item->decision?->value === 'INDICADA' || $item->decision === null);
                $selection['not_indicated'] += (int) $registration->indications->contains(fn ($item) => $item->decision?->value === 'NAO_INDICADA');
            }
            $byCategory[$category->id] = $categoryRow;
        }

        $stalledBefore = now()->subMinutes(max(5, (int) ceil($settings->timeout / 60) + 2));
        $problems = [
            'failed' => AiExecution::query()->where('status', AiExecutionStatus::Failed)->count(),
            'stalled' => AiExecution::query()->where('status', AiExecutionStatus::Processing)->where('updated_at', '<', $stalledBefore)->count(),
            'configuration' => $this->configurationProblems($settings),
        ];

        return [
            'registrations' => $registrations->count(),
            'technical' => $technical,
            'selection' => $selection,
            'problems' => $problems + ['total' => array_sum($problems)],
            'queue' => $this->queueStatus(),
            'categories' => collect($byCategory)->sortBy('category')->values()->all(),
        ];
    }

    private function technicalMissing(Registration $registration): array
    {
        $category = $registration->category;
        $human = $registration->opinions->where('source', 'human')->count();
        $ai = $registration->opinions->where('source', 'ai')->count();

        return [
            $category->allowsHumanEvaluation() && $human < (int) $category->human_evaluations_required,
            $category->allowsAiEvaluation() && $ai < $category->requiredAiEvaluations(),
        ];
    }

    private function selectionMissing(Registration $registration): array
    {
        $category = $registration->category;
        $human = $registration->indications->where('source', 'human')->count();
        $ai = $registration->indications->where('source', 'ai')->count();

        return [
            $category->allowsHumanIndication() && $human < (int) $category->human_indications_required,
            $category->allowsAiIndication() && $ai < $category->requiredAiIndications(),
        ];
    }

    private function configurationProblems(AiSettingsData $settings): int
    {
        return Category::query()
            ->where('is_honorific', false)
            ->whereHas('registrations', fn ($query) => $query->whereIn('status', [
                RegistrationStatusEnum::Habilitado->value,
                RegistrationStatusEnum::Avaliado->value,
            ]))
            ->with(['evaluators:id', 'indicators:id'])->get()->filter(function (Category $category) use ($settings): bool {
                $technical = $settings->evaluationEnabled && $category->allowsAiEvaluation()
                    && ! $category->evaluators->contains('id', $settings->technicalEvaluatorId);
                $selection = $settings->selectionEnabled && $category->allowsAiIndication()
                    && ! $category->indicators->contains('id', $settings->selectionEvaluatorId);

                return $technical || $selection;
            })->count();
    }

    private function queueStatus(): array
    {
        if (config('queue.default') !== 'database' || ! Schema::hasTable('jobs')) {
            return ['pending' => null, 'oldest_minutes' => null];
        }
        $jobs = DB::table('jobs')->where('queue', config('ai_evaluation.queue'));
        $oldest = (clone $jobs)->min('created_at');

        return [
            'pending' => (clone $jobs)->count(),
            'oldest_minutes' => $oldest ? max(0, now()->diffInMinutes(\Carbon\Carbon::createFromTimestamp($oldest))) : null,
        ];
    }
}
