<?php

use App\Enums\Gender;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Validation\ValidationException;

test('service dapat membuat siswa dan menormalkan nis kosong menjadi null', function () {
    $service = app(StudentService::class);
    $class = SchoolClass::factory()->create();

    $student1 = $service->create(
        schoolClassId: $class->id,
        name: 'Ahmad Fauzan',
        nis: '',
        nisn: '0012345678',
        gender: Gender::L,
    );

    expect($student1->nis)->toBeNull();
    expect($student1->gender)->toBe(Gender::L);

    // Siswa kedua juga dengan NIS kosong (NULL) tidak bertabrakan pada unique constraint
    $student2 = $service->create(
        schoolClassId: $class->id,
        name: 'Fatimah Az-Zahra',
        nis: null,
        gender: Gender::P,
    );

    expect($student2->nis)->toBeNull();
    expect($student2->gender)->toBe(Gender::P);
});

test('service menolak nis duplikat jika diisi', function () {
    $service = app(StudentService::class);
    $class = SchoolClass::factory()->create();

    $service->create($class->id, 'Budi Santoso', nis: '12345');

    expect(fn () => $service->create($class->id, 'Budi Lain', nis: '12345'))
        ->toThrow(ValidationException::class);
});

test('service dapat memperbarui data siswa dan memindahkan kelas', function () {
    $service = app(StudentService::class);
    $class1 = SchoolClass::factory()->create();
    $class2 = SchoolClass::factory()->create();

    $student = $service->create($class1->id, 'Citra Lestari', nis: '9901');

    $updated = $service->update(
        student: $student,
        schoolClassId: $class2->id,
        name: 'Citra Kirana',
        nis: '9901',
        gender: Gender::P,
        isActive: false
    );

    expect($updated->school_class_id)->toBe($class2->id);
    expect($updated->name)->toBe('Citra Kirana');
    expect($updated->is_active)->toBeFalse();
});

test('hapus permanen siswa ikut menghapus nilai terdampak dan mengaudit', function () {
    $service = app(StudentService::class);
    $class = SchoolClass::factory()->create();
    $student = $service->create($class->id, 'Siswa Dinilai');

    $assessment1 = Assessment::factory()->create();
    $assessment2 = Assessment::factory()->create();

    Score::factory()->create(['student_id' => $student->id, 'assessment_id' => $assessment1->id]);
    Score::factory()->create(['student_id' => $student->id, 'assessment_id' => $assessment2->id]);

    expect($student->scores()->count())->toBe(2);

    $deletedScores = $service->deletePermanently($student);

    expect($deletedScores)->toBe(2);
    expect(Student::query()->whereKey($student->id)->exists())->toBeFalse();
    expect(Score::query()->where('student_id', $student->id)->count())->toBe(0);

    $audit = AuditLog::query()
        ->where('entity', 'student')
        ->where('action', 'DELETE')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->meta)->toHaveKey('scores_deleted');
    expect($audit->meta['scores_deleted'])->toBe(2);
});
