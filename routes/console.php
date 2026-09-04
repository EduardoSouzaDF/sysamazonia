<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Enum\RegistrationStatusEnum;
use App\Events\RegistrationStatusChanged;
use App\Models\Registration;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:evaluate-habilitados {--dispatch : Enfileira as avaliações; sem esta opção apenas simula}', function (): int {
    if (! config('ai_evaluation.enabled')) {
        $this->error('A avaliação por IA está desabilitada.');
        return self::FAILURE;
    }

    $query = Registration::query()->where('status', RegistrationStatusEnum::Habilitado->value);
    $total = (clone $query)->count();
    $this->info("Inscrições habilitadas encontradas: {$total}");
    if (! $this->option('dispatch')) {
        $this->comment('Simulação concluída. Use --dispatch para enfileirar de forma idempotente.');
        return self::SUCCESS;
    }

    $query->select('id')->orderBy('id')->chunkById(100, function ($registrations): void {
        foreach ($registrations as $registration) {
            event(new RegistrationStatusChanged(
                $registration->id,
                RegistrationStatusEnum::Inscrito->value,
                RegistrationStatusEnum::Habilitado->value,
            ));
        }
    });
    $this->info('Solicitações enviadas ao fluxo idempotente de avaliação.');
    return self::SUCCESS;
})->purpose('Simula ou enfileira avaliações de todas as inscrições habilitadas');

Artisan::command('ai:select-avaliados {--dispatch : Enfileira as seleções; sem esta opção apenas simula}', function (): int {
    if (! config('ai_evaluation.enabled') || ! config('ai_evaluation.selection_enabled')) {
        $this->error('A seleção estratégica por IA está desabilitada.');
        return self::FAILURE;
    }

    $query = Registration::query()->where('status', RegistrationStatusEnum::Avaliado->value);
    $total = (clone $query)->count();
    $this->info("Inscrições avaliadas encontradas: {$total}");
    if (! $this->option('dispatch')) {
        $this->comment('Simulação concluída. Use --dispatch para enfileirar de forma idempotente.');
        return self::SUCCESS;
    }

    $query->select('id')->orderBy('id')->chunkById(100, function ($registrations): void {
        foreach ($registrations as $registration) {
            event(new RegistrationStatusChanged(
                $registration->id,
                RegistrationStatusEnum::Habilitado->value,
                RegistrationStatusEnum::Avaliado->value,
            ));
        }
    });
    $this->info('Solicitações enviadas ao fluxo idempotente de seleção.');
    return self::SUCCESS;
})->purpose('Simula ou enfileira seleções de todas as inscrições avaliadas');
