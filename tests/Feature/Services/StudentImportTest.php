<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentImporter;
use Illuminate\Http\UploadedFile;

test('importer dapat mengimpor data dari file csv dan melaporkan baris gagal', function () {
    $class = SchoolClass::factory()->create(['name' => 'VII-A']);
    Student::factory()->create(['nis' => '1111']); // NIS sudah ada

    $csvContent = implode("\n", [
        'nama,kelas,nis,nisn,jenis_kelamin',
        'Zaid bin Tsabit,VII-A,2222,0012345678,L',
        'Usamah bin Zaid,Kelas Tidak Ada,3333,,L', // Gagal: kelas tidak ada
        'Ali bin Abi Thalib,VII-A,1111,,L',        // Gagal: NIS duplikat
        'Aisyah binti Abu Bakar,VII-A,4444,,P',
    ]);

    $file = UploadedFile::fake()->createWithContent('siswa.csv', $csvContent);

    $importer = app(StudentImporter::class);
    $result = $importer->import($file);

    expect($result->importedCount)->toBe(2);
    expect($result->failed)->toHaveCount(2);

    expect($result->failed[0]['row'])->toBe(3);
    expect($result->failed[0]['error'])->toContain('Kelas');

    expect($result->failed[1]['row'])->toBe(4);
    expect($result->failed[1]['error'])->toContain('NIS');

    // Pastikan siswa yang valid tersimpan di database
    expect(Student::query()->where('name', 'Zaid bin Tsabit')->exists())->toBeTrue();
    expect(Student::query()->where('name', 'Aisyah binti Abu Bakar')->exists())->toBeTrue();

    // Audit log harus tercatat
    $audit = AuditLog::query()
        ->where('entity', 'import')
        ->where('action', 'IMPORT')
        ->first();
    expect($audit)->not->toBeNull();
});

test('unduhan template siswa tersedia dan memiliki header yang tepat', function () {
    $response = $this->actingAs(User::factory()->create())
        ->get('/siswa/template');

    $response->assertOk();
    $response->assertHeader('content-disposition');
    expect($response->streamedContent())->toContain('nama,kelas,nis,nisn,jenis_kelamin');
});

test('impor memilih kelas pada tahun ajaran aktif ketika nama kelas berulang', function () {
    $activeYear = AcademicYear::factory()->active()->create();
    $otherYear = AcademicYear::factory()->create();
    $activeClass = SchoolClass::factory()->create(['academic_year_id' => $activeYear->id, 'name' => 'VII A']);
    SchoolClass::factory()->create(['academic_year_id' => $otherYear->id, 'name' => 'VII A']);

    $file = UploadedFile::fake()->createWithContent('siswa.csv', "nama,kelas,nis\nSiswa Baru,VII A,991122\n");
    $result = app(StudentImporter::class)->import($file);

    expect($result->importedCount)->toBe(1);
    expect(Student::where('nis', '991122')->value('school_class_id'))->toBe($activeClass->id);
});

test('impor menolak nama kelas ambigu bila tidak ada tahun ajaran aktif', function () {
    $firstYear = AcademicYear::factory()->create();
    $secondYear = AcademicYear::factory()->create();
    SchoolClass::factory()->create(['academic_year_id' => $firstYear->id, 'name' => 'VII A']);
    SchoolClass::factory()->create(['academic_year_id' => $secondYear->id, 'name' => 'VII A']);
    $file = UploadedFile::fake()->createWithContent('siswa.csv', "nama,kelas,nis\nSiswa Baru,VII A,991122\n");

    $result = app(StudentImporter::class)->import($file);

    expect($result->importedCount)->toBe(0);
    expect($result->failed)->toHaveCount(1);
    expect($result->failed[0]['error'])->toContain('Aktifkan tahun ajaran');
});
