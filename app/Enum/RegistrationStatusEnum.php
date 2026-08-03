<?php

namespace App\Enum;

enum RegistrationStatusEnum: int
{
    case Inscrito = 1;
    case Rejeitado = 2;
    case Habilitado = 3;
    case Avaliado = 4;
    case Agraciado = 5;

    public function label(): string
    {
        return match ($this) {
            self::Inscrito => 'Inscrito',
            self::Rejeitado => 'Rejeitado',
            self::Habilitado => 'Habilitado',
            self::Avaliado => 'Avaliado',
            self::Agraciado => 'Agraciado',
        };
    }

    public static function toArray(): array
    {
        return array_combine(
            array_map(fn (self $case) => $case->value, self::cases()),
            array_map(fn (self $case) => $case->label(), self::cases()),
        );
    }
}
