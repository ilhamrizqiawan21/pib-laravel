<?php

namespace App\Services;

final readonly class StudentImportResult
{
    /**
     * @param  array<int, array{row: int, error: string, data: array<string, mixed>}>  $failed
     */
    public function __construct(
        public int $importedCount,
        public array $failed = [],
    ) {}
}
