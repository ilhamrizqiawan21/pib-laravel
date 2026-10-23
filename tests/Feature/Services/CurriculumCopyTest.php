<?php

use App\Exceptions\CurriculumException;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Score;
use App\Services\CurriculumService;

test('pratinjau salin kurikulum menghitung jumlah elemen dengan tepat', function () {
    $service = app(CurriculumService::class);
    $year = AcademicYear::factory()->create();

    $template = $service->createTemplate($year->id, 'Kurikulum Sumber');
    $c1 = $service->createChapter($template->id, 'Bab 1');
    $s1 = $service->createSubchapter($c1->id, 'Subbab 1.1');
    $service->createAssessment($s1->id, 'Materi 1.1.A', weight: 1.0);
    $service->createAssessment($s1->id, 'Materi 1.1.B', weight: 2.0);

    $s2 = $service->createSubchapter($c1->id, 'Subbab 1.2');
    $service->createAssessment($s2->id, 'Materi 1.2.A', weight: 1.5);

    $preview = $service->previewCopy($template);

    expect($preview['template_name'])->toBe('Kurikulum Sumber');
    expect($preview['chapters_count'])->toBe(1);
    expect($preview['subchapters_count'])->toBe(2);
    expect($preview['assessments_count'])->toBe(3);
});

test('salin struktur menduplikasi seluruh hierarki ke tahun ajaran target tanpa menyalin nilai', function () {
    $service = app(CurriculumService::class);
    $sourceYear = AcademicYear::factory()->create(['name' => '2024/2025']);
    $targetYear = AcademicYear::factory()->create(['name' => '2025/2026']);

    $sourceTemplate = $service->createTemplate($sourceYear->id, 'Kurikulum PAI');
    $chapter = $service->createChapter($sourceTemplate->id, 'Fiqih Ibadah');
    $subchapter = $service->createSubchapter($chapter->id, 'Shalat Sunnah');
    $assessment = $service->createAssessment($subchapter->id, 'Shalat Dhuha', weight: 2.5, description: 'Tata cara');

    // Tambahkan nilai siswa pada materi sumber
    Score::factory()->create(['assessment_id' => $assessment->id]);
    expect(Score::count())->toBe(1);

    // Salin struktur ke targetYear
    $targetTemplate = $service->copyToAcademicYear($sourceTemplate, $targetYear->id);

    expect($targetTemplate->academic_year_id)->toBe($targetYear->id);
    expect($targetTemplate->name)->toBe('Kurikulum PAI');
    expect($targetTemplate->chapters)->toHaveCount(1);

    $targetChapter = $targetTemplate->chapters->first();
    expect($targetChapter->title)->toBe('Fiqih Ibadah');
    expect($targetChapter->subchapters)->toHaveCount(1);

    $targetSubchapter = $targetChapter->subchapters->first();
    expect($targetSubchapter->title)->toBe('Shalat Sunnah');
    expect($targetSubchapter->assessments)->toHaveCount(1);

    $targetAssessment = $targetSubchapter->assessments->first();
    expect($targetAssessment->title)->toBe('Shalat Dhuha');
    expect($targetAssessment->weight)->toBe(2.5);
    expect($targetAssessment->description)->toBe('Tata cara');

    // ID materi harus berbeda dan nilai siswa pada materi baru harus 0 (bersih)
    expect($targetAssessment->id)->not->toBe($assessment->id);
    expect($targetAssessment->scores)->toHaveCount(0);
    expect(Score::count())->toBe(1); // Tetap hanya 1 milik materi lama

    // Audit log COPY tercatat
    $audit = AuditLog::query()
        ->where('entity', 'template')
        ->where('action', 'COPY')
        ->first();
    expect($audit)->not->toBeNull();
});

test('salin struktur menolak jika nama template sudah ada pada tahun ajaran tujuan', function () {
    $service = app(CurriculumService::class);
    $sourceYear = AcademicYear::factory()->create();
    $targetYear = AcademicYear::factory()->create();

    $sourceTemplate = $service->createTemplate($sourceYear->id, 'Template Standar');
    $service->createTemplate($targetYear->id, 'Template Standar'); // Sudah ada di target

    expect(fn () => $service->copyToAcademicYear($sourceTemplate, $targetYear->id))
        ->toThrow(CurriculumException::class);
});
