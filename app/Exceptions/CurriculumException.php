<?php

namespace App\Exceptions;

use RuntimeException;

class CurriculumException extends RuntimeException
{
    public function __construct(string $message, int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
