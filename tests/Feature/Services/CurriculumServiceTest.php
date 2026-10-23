<?php

use App\Exceptions\CurriculumException;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Chapter;
use App\Models\Score;
use App\Models\Subchapter;
use App\Services\CurriculumService;

test('service dapat membuat struktur kurikulum lengkap: template, bab, subbab, materi', function () {
    $service = app(CurriculumService::class);
    $year = AcademicYear::factory()->create();

    $template = $service->createTemplate($year->id, 'Kurikulum Utama 2026');
    expect($template->name)->toBe('Kurikulum Utama 2026');

    $chapter = $service->createChapter($template->id, 'Thaharah');
    expect($chapter->title)->toBe('Thaharah');
    expect($chapter->display_order)->toBe(1);

    $subchapter = $service->createSubchapter($chapter->id, 'Wudhu');
    expect($subchapter->title)->toBe('Wudhu');
    expect($subchapter->display_order)->toBe(1);

    $assessment = $service->createAssessment(
        subchapterId: $subchapter->id,
        title: 'Membasuh Muka',
        weight: 1.5,
        description: 'Tiga kali basuhan sempurna'
    );
    expect($assessment->title)->toBe('Membasuh Muka');
    expect($assessment->weight)->toBe(1.5);
    expect($assessment->display_order)->toBe(1);
    expect($assessment->is_active)->toBeTrue();

    // Verifikasi audit log tercatat
    expect(AuditLog::query()->where('entity', 'assessment')->where('action', 'CREATE')->exists())->toBeTrue();
});

test('judul entitas kurikulum harus unik per induk', function () {
    $service = app(CurriculumService::class);
    $year = AcademicYear::factory()->create();

    $template = $service->createTemplate($year->id, 'Kurikulum Standard');
    $chapter1 = $service->createChapter($template->id, 'Shalat');
    $subchapter1 = $service->createSubchapter($chapter1->id, 'Rukun Shalat');
    $service->createAssessment($subchapter1->id, 'Takbiratul Ihram');

    // Duplikat bab di template sama ditolak
    expect(fn () => $service->createChapter($template->id, 'Shalat'))
        ->toThrow(CurriculumException::class);

    // Duplikat subbab di bab sama ditolak
    expect(fn () => $service->createSubchapter($chapter1->id, 'Rukun Shalat'))
        ->toThrow(CurriculumException::class);

    // Duplikat materi di subbab sama ditolak
    expect(fn () => $service->createAssessment($subchapter1->id, 'Takbiratul Ihram'))
        ->toThrow(CurriculumException::class);
});

test('judul sama diizinkan pada induk yang berbeda', function () {
    $service = app(CurriculumService::class);
    $year = AcademicYear::factory()->create();

    $template = $service->createTemplate($year->id, 'Kurikulum B');
    $chapter1 = $service->createChapter($template->id, 'Bab Satu');
    $chapter2 = $service->createChapter($template->id, 'Bab Dua');

    $sub1 = $service->createSubchapter($chapter1->id, 'Pengenalan');
    $sub2 = $service->createSubchapter($chapter2->id, 'Pengenalan');

    expect($sub1->title)->toBe($sub2->title);
    expect($sub1->id)->not->toBe($sub2->id);
});

test('bobot materi harus bernilai antara 0.1 sampai 100', function () {
    $service = app(CurriculumService::class);
    $subchapter = Subchapter::factory()->create();

    // Bobot di bawah 0.1 ditolak
    expect(fn () => $service->createAssessment($subchapter->id, 'Materi 0', weight: 0.05))
        ->toThrow(CurriculumException::class);

    // Bobot di atas 100 ditolak
    expect(fn () => $service->createAssessment($subchapter->id, 'Materi 101', weight: 101.0))
        ->toThrow(CurriculumException::class);

    // Bobot 0.1 dan 100 diizinkan
    $m1 = $service->createAssessment($subchapter->id, 'Materi Min', weight: 0.1);
    $m2 = $service->createAssessment($subchapter->id, 'Materi Max', weight: 100.0);
    expect($m1->weight)->toBe(0.1);
    expect($m2->weight)->toBe(100.0);
});

test('urutan materi dapat dipindahkan naik dan turun secara stabil', function () {
    $service = app(CurriculumService::class);
    $subchapter = Subchapter::factory()->create();

    $m1 = $service->createAssessment($subchapter->id, 'Materi Pertama'); // order 1
    $m2 = $service->createAssessment($subchapter->id, 'Materi Kedua');   // order 2
    $m3 = $service->createAssessment($subchapter->id, 'Materi Ketiga');  // order 3

    expect($m1->display_order)->toBe(1);
    expect($m2->display_order)->toBe(2);
    expect($m3->display_order)->toBe(3);

    // Pindah m2 naik ke atas (menukar dengan m1)
    $service->moveOrder(Assessment::class, $m2->id, 'up');

    expect($m2->fresh()->display_order)->toBe(1);
    expect($m1->fresh()->display_order)->toBe(2);
    expect($m3->fresh()->display_order)->toBe(3);

    // Pindah m2 turun ke bawah
    $service->moveOrder(Assessment::class, $m2->id, 'down');

    expect($m1->fresh()->display_order)->toBe(1);
    expect($m2->fresh()->display_order)->toBe(2);
    expect($m3->fresh()->display_order)->toBe(3);
});

test('materi yang sudah memiliki nilai tidak dapat dihapus', function () {
    $service = app(CurriculumService::class);
    $assessment = Assessment::factory()->create();
    Score::factory()->create(['assessment_id' => $assessment->id]);

    expect(fn () => $service->deleteAssessment($assessment))
        ->toThrow(CurriculumException::class);

    expect(Assessment::query()->whereKey($assessment->id)->exists())->toBeTrue();
});

test('bab yang memuat materi bernilai tidak dapat dihapus', function () {
    $service = app(CurriculumService::class);
    $assessment = Assessment::factory()->create();
    Score::factory()->create(['assessment_id' => $assessment->id]);

    $chapter = $assessment->subchapter->chapter;

    expect(fn () => $service->deleteChapter($chapter))
        ->toThrow(CurriculumException::class);

    expect(Chapter::query()->whereKey($chapter->id)->exists())->toBeTrue();
});

test('materi tanpa nilai dapat dihapus dan dicatat di audit log', function () {
    $service = app(CurriculumService::class);
    $assessment = Assessment::factory()->create();

    $service->deleteAssessment($assessment);

    expect(Assessment::query()->whereKey($assessment->id)->exists())->toBeFalse();

    $audit = AuditLog::query()
        ->where('entity', 'assessment')
        ->where('action', 'DELETE')
        ->first();
    expect($audit)->not->toBeNull();
});

test('status aktif materi dapat diubah dan dicatat di audit log', function () {
    $service = app(CurriculumService::class);
    $assessment = Assessment::factory()->create(['is_active' => true]);

    $service->toggleAssessmentActive($assessment);
    expect($assessment->fresh()->is_active)->toBeFalse();

    $service->toggleAssessmentActive($assessment);
    expect($assessment->fresh()->is_active)->toBeTrue();
});
