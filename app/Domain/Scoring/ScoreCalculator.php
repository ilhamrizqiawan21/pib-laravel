<?php

namespace App\Domain\Scoring;

use InvalidArgumentException;

/**
 * Aturan nilai inti. Murni (tanpa DB/HTTP) agar mudah diuji. Sumber kebenaran: docs/PRD.md.
 *
 *   nilai = 90 - jumlah kesalahan      (kesalahan bulat 0..90, nilai 0..90)
 *   null  = belum dinilai (BUKAN 0)
 */
final class ScoreCalculator
{
    public static function maxScore(): int
    {
        return (int) config('pib.max_score', 90);
    }

    public static function isValidMistakes(mixed $mistakes): bool
    {
        return is_int($mistakes) && $mistakes >= 0 && $mistakes <= self::maxScore();
    }

    /** @throws InvalidArgumentException bila kesalahan di luar 0..90 */
    public static function score(?int $mistakes): ?int
    {
        if ($mistakes === null) {
            return null;
        }
        if (! self::isValidMistakes($mistakes)) {
            throw new InvalidArgumentException('Jumlah kesalahan harus bilangan bulat 0–'.self::maxScore().'.');
        }

        return self::maxScore() - $mistakes;
    }

    /**
     * Parse input mentah dari form (string). '' => null (belum dinilai).
     * Hanya digit; menolak '1.5', '-1', '1e1', ' 5x'.
     *
     * @throws InvalidArgumentException
     */
    public static function parseMistakes(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (! preg_match('/^\d+$/', $raw)) {
            throw new InvalidArgumentException('Isi bilangan bulat 0–'.self::maxScore().'.');
        }
        $value = (int) $raw;
        if (! self::isValidMistakes($value)) {
            throw new InvalidArgumentException('Isi bilangan bulat 0–'.self::maxScore().'.');
        }

        return $value;
    }

    /**
     * Rata-rata berbobot dari baris terisi saja: sum(nilai×bobot) / sum(bobot).
     *
     * @param  iterable<array{score: int|null, weight: float|int|string}>  $rows
     */
    public static function weightedAverage(iterable $rows): ?float
    {
        $sum = 0.0;
        $weights = 0.0;
        foreach ($rows as $row) {
            if ($row['score'] === null) {
                continue;
            }
            $weight = (float) $row['weight'];
            $sum += $row['score'] * $weight;
            $weights += $weight;
        }

        return $weights > 0.0 ? self::round($sum / $weights) : null;
    }

    public static function round(float $value): float
    {
        return round($value, (int) config('pib.average_precision', 1), PHP_ROUND_HALF_UP);
    }
}
