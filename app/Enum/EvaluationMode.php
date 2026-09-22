<?php

namespace App\Enum;

enum EvaluationMode: string
{
    case HumanOnly = 'human_only';
    case AiOnly = 'ai_only';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::HumanOnly => 'Somente humano',
            self::AiOnly => 'Somente IA',
            self::Hybrid => 'Humano + IA',
        };
    }

    public function allowsHuman(): bool
    {
        return $this !== self::AiOnly;
    }

    public function allowsAi(): bool
    {
        return $this !== self::HumanOnly;
    }
}
