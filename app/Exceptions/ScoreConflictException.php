<?php

namespace App\Exceptions;

use App\Models\Score;
use RuntimeException;

class ScoreConflictException extends RuntimeException
{
    public function __construct(
        public readonly ?Score $currentScore,
        string $message = 'Konflik versi data nilai.'
    ) {
        parent::__construct($message);
    }
}
