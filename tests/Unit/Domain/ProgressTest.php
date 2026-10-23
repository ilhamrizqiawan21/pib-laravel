<?php

use App\Domain\Recap\Progress;
use App\Domain\Scoring\StudentPosition;
use App\Enums\CompletionStatus;

it('menentukan status kelengkapan', function (int $expected, int $assessed, CompletionStatus $status) {
    expect((new Progress($expected, $assessed))->status())->toBe($status);
})->with([
    [0, 0, CompletionStatus::NoMaterial],
    [10, 0, CompletionStatus::NotAssessed],
    [10, 4, CompletionStatus::Partial],
    [10, 10, CompletionStatus::Complete],
]);

it('persen 100 hanya bila lengkap, selain itu maksimal 99', function () {
    expect((new Progress(200, 199))->percent())->toBe(99)
        ->and((new Progress(200, 200))->percent())->toBe(100)
        ->and((new Progress(0, 0))->percent())->toBe(0)
        ->and((new Progress(4, 1))->percent())->toBe(25);
});

it('subbab selesai bila semua siswa x semua materi terisi', function () {
    expect(Progress::subchapterComplete(3, 2, 6))->toBeTrue()
        ->and(Progress::subchapterComplete(3, 2, 5))->toBeFalse()
        ->and(Progress::subchapterComplete(0, 2, 0))->toBeFalse()
        ->and(Progress::subchapterComplete(3, 0, 0))->toBeFalse();
});

it('posisi siswa: materi berikutnya setelah posisi terjauh', function () {
    $scored = [10, 20, 30];
    $position = StudentPosition::from([10, 20, 30, 40, 50], fn (int $id) => in_array($id, $scored, true));

    expect($position->assessed)->toBe(3)
        ->and($position->lastIndex)->toBe(2)
        ->and($position->nextIndex)->toBe(3)
        ->and($position->gaps)->toBe([]);
});

it('posisi siswa: materi terlewat sebelum posisi terjauh adalah gap, bukan target', function () {
    $scored = [10, 40]; // 20 & 30 terlewat
    $position = StudentPosition::from([10, 20, 30, 40, 50], fn (int $id) => in_array($id, $scored, true));

    expect($position->gaps)->toBe([1, 2])
        ->and($position->nextIndex)->toBe(4); // ada materi di depan → itu target
});

it('posisi siswa: bila tidak ada materi di depan, target adalah gap pertama', function () {
    $scored = [10, 30];
    $position = StudentPosition::from([10, 20, 30], fn (int $id) => in_array($id, $scored, true));

    expect($position->nextIndex)->toBe(1)->and($position->gaps)->toBe([1]);
});

it('posisi siswa: belum ada nilai → mulai dari awal', function () {
    $position = StudentPosition::from([10, 20], fn () => false);

    expect($position->lastIndex)->toBe(-1)->and($position->nextIndex)->toBe(0)->and($position->assessed)->toBe(0);
});

it('posisi siswa: semua selesai → tidak ada target', function () {
    $position = StudentPosition::from([10, 20], fn () => true);

    expect($position->nextIndex)->toBe(-1);
});
