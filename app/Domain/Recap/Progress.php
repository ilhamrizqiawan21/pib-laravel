<?php

namespace App\Domain\Recap;

use App\Enums\CompletionStatus;

/** Progres penilaian: expected = jumlah nilai yang seharusnya ada, assessed = yang sudah terisi. */
final readonly class Progress
{
    public function __construct(
        public int $expected,
        public int $assessed,
    ) {}

    public function isComplete(): bool
    {
        return $this->expected > 0 && $this->assessed === $this->expected;
    }

    /** Persen 0..100. 100 hanya bila lengkap; selain itu dibatasi 99 agar tidak menyesatkan. */
    public function percent(): int
    {
        if ($this->expected === 0) {
            return 0;
        }
        if ($this->assessed >= $this->expected) {
            return 100;
        }

        return min(99, (int) round($this->assessed / $this->expected * 100));
    }

    public function status(): CompletionStatus
    {
        return CompletionStatus::fromCounts($this->expected, $this->assessed);
    }

    /** Subbab selesai bila semua siswa aktif punya nilai untuk semua materi aktif. */
    public static function subchapterComplete(int $students, int $assessments, int $scored): bool
    {
        return $students > 0 && $assessments > 0 && $scored === $students * $assessments;
    }
}
