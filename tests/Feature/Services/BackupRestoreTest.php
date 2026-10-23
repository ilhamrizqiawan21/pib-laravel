<?php

use App\Exceptions\RestoreException;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->backupDir = storage_path('framework/testing/backups_test_'.uniqid());
    File::makeDirectory($this->backupDir, 0755, true, true);
    config(['pib.backup.dir' => $this->backupDir]);

    $this->service = app(BackupService::class);

    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);

    $this->class = SchoolClass::factory()->create(['name' => 'VII A']);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Siswa Awal']);
});

afterEach(function () {
    if (File::exists($this->backupDir)) {
        File::deleteDirectory($this->backupDir);
    }
});

test('createPreRestoreSnapshot membuat snapshot sebelum restore dijalankan', function () {
    $snapshot = $this->service->createPreRestoreSnapshot();

    expect($snapshot)->not->toBeNull();
    expect(File::exists($snapshot))->toBeTrue();
    expect(basename($snapshot))->toMatch('/^pre-restore-\d{8}-\d{6}\.sqlite$/');
});

test('restoreFromJson memulihkan data dari backup json valid dan mencatat audit log', function () {
    // 1. Buat backup JSON saat ini
    $export = $this->service->exportJson();

    // 2. Modifikasi database lokal: hapus siswa dan tambah siswa baru
    Student::query()->delete();
    Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Siswa Baru']);
    expect(Student::where('name', 'Siswa Awal')->exists())->toBeFalse();

    // 3. Jalankan restoreFromJson
    $restoredRows = $this->service->restoreFromJson($export, $this->admin);

    expect($restoredRows)->toBeGreaterThan(0);
    // Siswa Awal harus kembali ada
    expect(Student::where('name', 'Siswa Awal')->exists())->toBeTrue();

    // Audit log RESTORE tercatat
    $lastAudit = AuditLog::query()->where('action', 'RESTORE')->latest('id')->first();
    expect($lastAudit)->not->toBeNull();
});

test('restoreFromJson menolak file tidak valid dan mencatat audit log FAILED', function () {
    $invalidPayload = [
        'app' => 'bukan-pib',
        'data' => [],
    ];

    expect(fn () => $this->service->restoreFromJson($invalidPayload, $this->admin))
        ->toThrow(RestoreException::class);

    // Audit log FAILED tercatat
    $lastAudit = AuditLog::query()->where('action', 'FAILED')->latest('id')->first();
    expect($lastAudit)->not->toBeNull();
});
