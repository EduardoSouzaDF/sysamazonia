<?php

namespace App\Listeners;

use App\Enum\AiExecutionType;
use App\Enum\RegistrationStatusEnum;
use App\Events\RegistrationStatusChanged;
use App\Models\Registration;
use App\Services\Ai\AiExecutionDispatcher;

class DispatchAiEvaluation
{
    public function __construct(private AiExecutionDispatcher $dispatcher) {}

    public function handle(RegistrationStatusChanged $event): void
    {
        $type = match ($event->currentStatus) {
            RegistrationStatusEnum::Habilitado->value => AiExecutionType::TechnicalEvaluation,
            RegistrationStatusEnum::Avaliado->value => AiExecutionType::StrategicSelection,
            default => null,
        };
        if (! $type || ! ($registration = Registration::query()->find($event->registrationId))) {
            return;
        }
        if ($execution = $this->dispatcher->create($registration, $type)) {
            $this->dispatcher->dispatch($execution);
        }
    }
}
