<?php

use App\Domain\Scoring\ScoreCalculator;

// Aturan: docs/PRD.md §Aturan Nilai. Setiap kasus di sini adalah kontrak, bukan sekadar contoh.

it('menghitung nilai = 90 - kesalahan', function (int $mistakes, int $expected) {
    expect(ScoreCalculator::score($mistakes))->toBe($expected);
})->with([[0, 90], [3, 87], [89, 1], [90, 0]]);

it('null berarti belum dinilai, bukan nol', function () {
    expect(ScoreCalculator::score(null))->toBeNull();
});

it('menolak kesalahan di luar 0..90', function (int $mistakes) {
    ScoreCalculator::score($mistakes);
})->with([-1, 91, 1000])->throws(InvalidArgumentException::class);

it('nilai 0 hanya muncul dari 90 kesalahan', function () {
    expect(ScoreCalculator::score(90))->toBe(0)->and(ScoreCalculator::score(89))->not->toBe(0);
});

it('mem-parse input mentah', function (string $raw, ?int $expected) {
    expect(ScoreCalculator::parseMistakes($raw))->toBe($expected);
})->with([['', null], ['   ', null], ['0', 0], [' 7 ', 7], ['90', 90]]);

it('menolak input mentah tidak valid', function (string $raw) {
    ScoreCalculator::parseMistakes($raw);
})->with(['-1', '1.5', '1e1', '5x', '91', 'abc'])->throws(InvalidArgumentException::class);

it('rata-rata berbobot hanya memakai baris terisi', function () {
    $rows = [
        ['score' => 90, 'weight' => 2],
        ['score' => 80, 'weight' => 1],
        ['score' => null, 'weight' => 5], // diabaikan
    ];

    // (90*2 + 80*1) / 3 = 86.666… → 86.7
    expect(ScoreCalculator::weightedAverage($rows))->toBe(86.7);
});

it('rata-rata null bila belum ada nilai', function () {
    expect(ScoreCalculator::weightedAverage([]))->toBeNull()
        ->and(ScoreCalculator::weightedAverage([['score' => null, 'weight' => 1]]))->toBeNull();
});

it('membulatkan setengah ke atas pada satu desimal', function () {
    expect(ScoreCalculator::weightedAverage([['score' => 85, 'weight' => 1], ['score' => 86, 'weight' => 1]]))->toBe(85.5)
        ->and(ScoreCalculator::weightedAverage([['score' => 90, 'weight' => 1], ['score' => 81, 'weight' => 1], ['score' => 81, 'weight' => 1]]))->toBe(84.0)
        ->and(ScoreCalculator::round(0.25))->toBe(0.3);
});

it('menerima bobot sebagai string desimal dari database', function () {
    expect(ScoreCalculator::weightedAverage([['score' => 90, 'weight' => '1.50'], ['score' => 60, 'weight' => '0.50']]))->toBe(82.5);
});
