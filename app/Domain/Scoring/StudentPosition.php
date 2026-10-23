<?php

namespace App\Domain\Scoring;

/**
 * Posisi siswa pada daftar materi berurutan (layar Setoran).
 * Siswa maju berurutan: materi belum bernilai SEBELUM posisi terjauh = "terlewat" (gap),
 * bukan target berikutnya. Port dari studentPosition() aplikasi lama.
 */
final readonly class StudentPosition
{
    /**
     * @param  list<int>  $gaps  indeks materi terlewat
     */
    public function __construct(
        public int $assessed,
        public int $total,
        public int $lastIndex,
        public int $nextIndex,
        public array $gaps,
    ) {}

    /**
     * @param  list<int>  $materialIds  urutan kurikulum
     * @param  callable(int): bool  $isScored
     */
    public static function from(array $materialIds, callable $isScored): self
    {
        $lastIndex = -1;
        $assessed = 0;
        foreach ($materialIds as $index => $id) {
            if ($isScored($id)) {
                $assessed++;
                $lastIndex = $index;
            }
        }

        $gaps = [];
        foreach (array_slice($materialIds, 0, max(0, $lastIndex), true) as $index => $id) {
            if (! $isScored($id)) {
                $gaps[] = $index;
            }
        }

        $ahead = -1;
        foreach ($materialIds as $index => $id) {
            if ($index > $lastIndex && ! $isScored($id)) {
                $ahead = $index;
                break;
            }
        }

        return new self($assessed, count($materialIds), $lastIndex, $ahead >= 0 ? $ahead : ($gaps[0] ?? -1), $gaps);
    }
}
