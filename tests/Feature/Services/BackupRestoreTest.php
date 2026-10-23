<?php

use App\Exceptions\RestoreException;
use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
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

test('restore menolak cadangan parsial sebelum menghapus data', function () {
    expect(fn () => $this->service->restoreFromJson([
        'app' => 'pib-penilaian',
        'data' => [],
    ], $this->admin))->toThrow(RestoreException::class);

    expect(Student::whereKey($this->student->id)->exists())->toBeTrue();
    expect(SchoolClass::whereKey($this->class->id)->exists())->toBeTrue();
});

test('restore menolak cadangan tanpa admin aktif dan mempertahankan data', function () {
    $backup = $this->service->exportJson();
    foreach ($backup['data']['users'] as &$user) {
        $user['role'] = 'teacher';
    }
    unset($user);

    expect(fn () => $this->service->restoreFromJson($backup, $this->admin))
        ->toThrow(RestoreException::class);

    expect(Student::whereKey($this->student->id)->exists())->toBeTrue();
});

test('restore tetap mencatat audit bila admin pemulih tidak ada dalam cadangan', function () {
    $backup = $this->service->exportJson();
    $backup['data']['users'] = array_values(array_filter(
        $backup['data']['users'],
        fn (array $user): bool => $user['id'] !== $this->admin->id
    ));
    $backup['data']['users'][0]['role'] = 'admin';
    $backup['data']['users'][0]['is_active'] = true;

    $this->service->restoreFromJson($backup, $this->admin);

    $audit = AuditLog::where('action', 'RESTORE')->latest('id')->first();
    expect($audit)->not->toBeNull();
    expect($audit->user_id)->toBeNull();
});

test('restore tidak mengubah data bila mode pemeliharaan gagal aktif', function () {
    $backup = $this->service->exportJson();
    Artisan::shouldReceive('call')->once()->with('down')->andReturn(0);

    expect(fn () => $this->service->restoreFromJson($backup, $this->admin))
        ->toThrow(RestoreException::class);

    expect(Student::whereKey($this->student->id)->exists())->toBeTrue();
});
