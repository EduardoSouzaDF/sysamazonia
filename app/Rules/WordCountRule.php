<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class WordCountRule implements ValidationRule
{
    protected int $min;

    protected int $max;

    public function __construct(int $min, int $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $wordCount = str_word_count(strip_tags($value));

        if ($wordCount < $this->min || $wordCount > $this->max) {
            $fail("O campo {$attribute} deve ter entre {$this->min} e {$this->max} palavras.");
        }
    }
}
