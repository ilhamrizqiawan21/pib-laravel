<?php

namespace App\Enums;

/** Status kelengkapan penilaian sebuah cakupan (siswa/bab/kelas). */
enum CompletionStatus: string
{
    case NoMaterial = 'Materi belum tersedia';
    case NotAssessed = 'Belum dinilai';
    case Partial = 'Sebagian';
    case Complete = 'Lengkap';

    public static function fromCounts(int $expected, int $assessed): self
    {
        return match (true) {
            $expected === 0 => self::NoMaterial,
            $assessed === 0 => self::NotAssessed,
            $assessed >= $expected => self::Complete,
            default => self::Partial,
        };
    }
}
