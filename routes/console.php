<?php

use App\Enum\AiExecutionType;
use App\Services\Ai\AiPendingOperations;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:evaluate-habilitados {--dispatch}', function (AiPendingOperations $operations): int {
    $items = $operations->candidates(AiExecutionType::TechnicalEvaluation);
    $this->info("Inscrições habilitadas encontradas: {$items->count()}");
    if ($this->option('dispatch')) {
        $operations->start(AiExecutionType::TechnicalEvaluation);
        $this->info('Solicitações enviadas.');
    }

    return self::SUCCESS;
});

Artisan::command('ai:select-avaliados {--dispatch}', function (AiPendingOperations $operations): int {
    $items = $operations->candidates(AiExecutionType::StrategicSelection);
    $this->info("Inscrições avaliadas encontradas: {$items->count()}");
    if ($this->option('dispatch')) {
        $operations->start(AiExecutionType::StrategicSelection);
        $this->info('Solicitações enviadas.');
    }

    return self::SUCCESS;
});
