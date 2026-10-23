<?php

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['name' => 'VII A']);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Siswa Lama']);

    $this->backupService = app(BackupService::class);
    $this->validBackup = $this->backupService->exportJson();
});

test('tamu dan guru tidak dapat mengakses endpoint restore', function () {
    $this->postJson('/admin/restore', [])->assertUnauthorized();

    $this->actingAs($this->teacher)
        ->postJson('/admin/restore', [])
        ->assertForbidden();
});

test('admin harus menyertakan konfirmasi teks PULIHKAN', function () {
    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($this->validBackup));

    $this->actingAs($this->admin)
        ->postJson('/admin/restore', [
            'file' => $file,
            'confirmation' => 'SALAH',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['confirmation']);
});

test('admin dapat memulihkan data dengan file backup dan konfirmasi PULIHKAN', function () {
    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode($this->validBackup));

    $response = $this->actingAs($this->admin)
        ->postJson('/admin/restore', [
            'file' => $file,
            'confirmation' => 'PULIHKAN',
        ]);

    $response->assertOk()
        ->assertJsonStructure(['restored', 'message']);
});
