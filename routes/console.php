<?php

use App\Enum\AiExecutionType;
use App\Services\Ai\AiPendingOperations;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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

Artisan::command('ai:reconcile', function (AiPendingOperations $operations): int {
    $technical = $operations->start(AiExecutionType::TechnicalEvaluation);
    $selection = $operations->start(AiExecutionType::StrategicSelection);
    $this->info("Reconciliação concluída: {$technical['started']} avaliação(ões) e {$selection['started']} indicação(ões) enviadas.");

    return self::SUCCESS;
})->purpose('Reconcilia e envia automaticamente operações de IA pendentes');

Schedule::command('ai:reconcile')->everyMinute()->withoutOverlapping(5);
