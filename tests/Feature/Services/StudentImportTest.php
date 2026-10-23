<?php

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
