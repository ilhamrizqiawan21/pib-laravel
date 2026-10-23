<?php

use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\LazyLoadingViolationException;

test('mode ketat aktif pada lingkungan non-produksi', function () {
    expect(Model::preventsLazyLoading())->toBeTrue();
    expect(Model::preventsSilentlyDiscardingAttributes())->toBeTrue();
    expect(Model::preventsAccessingMissingAttributes())->toBeTrue();
});

test('lazy loading pada koleksi memicu LazyLoadingViolationException', function () {
    $class = SchoolClass::factory()->create();
    Student::factory()->count(2)->create(['school_class_id' => $class->id]);

    $students = Student::all();

    expect(fn () => $students->map(fn (Student $student) => $student->schoolClass->name))
        ->toThrow(LazyLoadingViolationException::class);
});

test('mengakses atribut yang tidak ada memicu MissingAttributeException', function () {
    $student = Student::factory()->create();
    $queried = Student::query()->select(['id'])->whereKey($student->id)->firstOrFail();

    expect(fn () => $queried->name)
        ->toThrow(MissingAttributeException::class);
});

test('atribut yang tidak diizinkan memicu MassAssignmentException', function () {
    expect(fn () => Student::create([
        'school_class_id' => 1,
        'name' => 'Budi',
        'non_existent_unfillable_attribute' => 'value',
    ]))->toThrow(MassAssignmentException::class);
});

test('lokal bawaan aplikasi adalah bahasa indonesia', function () {
    expect(app()->getLocale())->toBe('id');
});

test('terjemahan bahasa indonesia tersedia untuk modul penting', function () {
    expect(trans('auth.failed'))->toBe('Identitas tersebut tidak cocok dengan data kami.');
    expect(trans('pagination.previous'))->toBe('&laquo; Sebelumnya');
    expect(trans('pagination.next'))->toBe('Berikutnya &raquo;');
    expect(trans('passwords.reset'))->toBe('Kata sandi Anda sudah direset!');
    expect(trans('validation.required', ['attribute' => 'nama']))->toContain('wajib diisi');
});
