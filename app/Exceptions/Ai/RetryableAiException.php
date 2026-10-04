<?php

namespace App\Exceptions\Ai;

use RuntimeException;

class RetryableAiException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $safeMessage)
    {
        parent::__construct($safeMessage);
    }
}
