<?php

use App\Enums\Semester;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Services\AcademicYearService;
use Illuminate\Validation\ValidationException;

test('service dapat membuat tahun ajaran dengan format valid', function () {
    $service = app(AcademicYearService::class);

    $year = $service->create('2026/2027', Semester::Ganjil, isActive: true);

    expect($year->name)->toBe('2026/2027');
    expect($year->semester)->toBe(Semester::Ganjil);
    expect($year->is_active)->toBeTrue();

    $audit = AuditLog::query()
        ->where('entity', 'academic_year')
        ->where('action', 'CREATE')
        ->first();
    expect($audit)->not->toBeNull();
});

test('service menolak format nama tahun ajaran yang bukan YYYY/YYYY', function () {
    $service = app(AcademicYearService::class);

    expect(fn () => $service->create('2026-2027', Semester::Ganjil))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->create('2026', Semester::Ganjil))
        ->toThrow(ValidationException::class);
});

test('service menolak kombinasi nama dan semester duplikat', function () {
    $service = app(AcademicYearService::class);

    $service->create('2025/2026', Semester::Ganjil);

    expect(fn () => $service->create('2025/2026', Semester::Ganjil))
        ->toThrow(ValidationException::class);
});

test('mengaktifkan satu tahun ajaran otomatis menonaktifkan yang lain', function () {
    $service = app(AcademicYearService::class);

    $year1 = $service->create('2024/2025', Semester::Ganjil, isActive: true);
    expect($year1->fresh()->is_active)->toBeTrue();

    $year2 = $service->create('2024/2025', Semester::Genap, isActive: true);
    expect($year2->fresh()->is_active)->toBeTrue();
    expect($year1->fresh()->is_active)->toBeFalse();

    // Aktifkan kembali year1
    $service->activate($year1);
    expect($year1->fresh()->is_active)->toBeTrue();
    expect($year2->fresh()->is_active)->toBeFalse();

    expect(AcademicYear::query()->where('is_active', true)->count())->toBe(1);
});

test('tahun ajaran tidak dapat dihapus jika masih memiliki kelas', function () {
    $service = app(AcademicYearService::class);
    $year = $service->create('2025/2026', Semester::Ganjil);

    SchoolClass::factory()->create(['academic_year_id' => $year->id]);

    expect(fn () => $service->delete($year))->toThrow(ValidationException::class);
    expect(AcademicYear::query()->whereKey($year->id)->exists())->toBeTrue();
});

test('tahun ajaran tidak dapat dihapus jika masih memiliki template kurikulum', function () {
    $service = app(AcademicYearService::class);
    $year = $service->create('2025/2026', Semester::Genap);

    CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);

    expect(fn () => $service->delete($year))->toThrow(ValidationException::class);
    expect(AcademicYear::query()->whereKey($year->id)->exists())->toBeTrue();
});

test('tahun ajaran tanpa relasi dapat dihapus dan dicatat di audit log', function () {
    $service = app(AcademicYearService::class);
    $year = $service->create('2025/2026', Semester::Genap);

    $service->delete($year);

    expect(AcademicYear::query()->whereKey($year->id)->exists())->toBeFalse();

    $audit = AuditLog::query()
        ->where('entity', 'academic_year')
        ->where('action', 'DELETE')
        ->first();
    expect($audit)->not->toBeNull();
});
