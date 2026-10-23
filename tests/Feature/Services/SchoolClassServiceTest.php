<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\SchoolClassService;
use Illuminate\Validation\ValidationException;

test('service dapat membuat kelas baru dengan validasi unik per tahun ajaran', function () {
    $service = app(SchoolClassService::class);
    $year = AcademicYear::factory()->create();

    $class = $service->create(
        academicYearId: $year->id,
        name: 'VII-A',
        gradeLevel: '7',
        homeroomTeacher: 'Ustadz Umar'
    );

    expect($class->name)->toBe('VII-A');
    expect($class->grade_level)->toBe('7');
    expect($class->homeroom_teacher)->toBe('Ustadz Umar');
    expect($class->is_active)->toBeTrue();

    $audit = AuditLog::query()
        ->where('entity', 'class')
        ->where('action', 'CREATE')
        ->first();
    expect($audit)->not->toBeNull();

    // Nama kelas unik per tahun ajaran
    expect(fn () => $service->create($year->id, 'VII-A'))
        ->toThrow(ValidationException::class);
});

test('kelas dengan nama sama diizinkan pada tahun ajaran berbeda', function () {
    $service = app(SchoolClassService::class);
    $year1 = AcademicYear::factory()->create();
    $year2 = AcademicYear::factory()->create();

    $class1 = $service->create($year1->id, 'VII-A');
    $class2 = $service->create($year2->id, 'VII-A');

    expect($class1->id)->not->toBe($class2->id);
});

test('service dapat memperbarui data kelas', function () {
    $service = app(SchoolClassService::class);
    $class = SchoolClass::factory()->create(['name' => 'VII-A']);

    $updated = $service->update($class, name: 'VII-B', gradeLevel: '7', homeroomTeacher: 'Ustadz Ali', isActive: true);

    expect($updated->name)->toBe('VII-B');
    expect($updated->homeroom_teacher)->toBe('Ustadz Ali');

    $audit = AuditLog::query()
        ->where('entity', 'class')
        ->where('action', 'UPDATE')
        ->first();
    expect($audit)->not->toBeNull();
});

test('kelas tidak dapat dihapus jika masih memiliki siswa', function () {
    $service = app(SchoolClassService::class);
    $class = SchoolClass::factory()->create();
    Student::factory()->create(['school_class_id' => $class->id]);

    expect(fn () => $service->delete($class))->toThrow(ValidationException::class);
    expect(SchoolClass::query()->whereKey($class->id)->exists())->toBeTrue();
});

test('kelas tanpa siswa dapat dihapus permanen dan dicatat di audit log', function () {
    $service = app(SchoolClassService::class);
    $class = SchoolClass::factory()->create();

    $service->delete($class);

    expect(SchoolClass::query()->whereKey($class->id)->exists())->toBeFalse();

    $audit = AuditLog::query()
        ->where('entity', 'class')
        ->where('action', 'DELETE')
        ->first();
    expect($audit)->not->toBeNull();
});
