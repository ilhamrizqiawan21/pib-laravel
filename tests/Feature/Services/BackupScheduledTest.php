<?php

use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->backupDir = storage_path('framework/testing/backups_test_'.uniqid());
    File::makeDirectory($this->backupDir, 0755, true, true);
    config(['pib.backup.dir' => $this->backupDir]);
    config(['pib.backup.keep' => 3]);
    config(['pib.backup.interval_hours' => 2]);

    $this->service = app(BackupService::class);
});

afterEach(function () {
    if (File::exists($this->backupDir)) {
        File::deleteDirectory($this->backupDir);
    }
});

test('hasDatabaseChangedSince mengembalikan true jika belum pernah ada backup', function () {
    expect($this->service->hasDatabaseChangedSince(null))->toBeTrue();
});

test('hasDatabaseChangedSince mengembalikan false jika tidak ada perubahan data sejak backup terakhir', function () {
    // Anggap backup dibuat di masa depan (1 jam ke depan)
    $futureTime = now()->addHour();
    expect($this->service->hasDatabaseChangedSince($futureTime))->toBeFalse();
});

test('hasDatabaseChangedSince mengembalikan true jika ada perubahan audit log setelah waktu tertentu', function () {
    $pastTime = now()->subMinutes(10);

    app(AuditLogger::class)->record(
        entity: 'score',
        entityId: '1',
        action: 'UPSERT',
        summary: 'Pembaruan nilai',
        meta: ['score' => 90]
    );

    expect($this->service->hasDatabaseChangedSince($pastTime))->toBeTrue();
});

test('createScheduledBackup membuat berkas sqlite jika dipaksa atau ada perubahan', function () {
    $file = $this->service->createScheduledBackup(force: true);

    expect($file)->not->toBeNull();
    expect(File::exists($file))->toBeTrue();
    expect(basename($file))->toMatch('/^pib-\d{8}-\d{6}\.sqlite$/');
});

test('createScheduledBackup dilewati jika tidak ada perubahan dan interval belum lewat', function () {
    $first = $this->service->createScheduledBackup(force: true);
    expect($first)->not->toBeNull();

    // Panggilan kedua tanpa perubahan data
    $second = $this->service->createScheduledBackup(force: false);
    expect($second)->toBeNull();
});

test('createScheduledBackup merotasi berkas sesuai batas retensi keep', function () {
    config(['pib.backup.keep' => 2]);

    // Buat 3 berkas backup
    $f1 = $this->backupDir.'/pib-20261020-100000.sqlite';
    $f2 = $this->backupDir.'/pib-20261021-100000.sqlite';
    $f3 = $this->backupDir.'/pib-20261022-100000.sqlite';
    file_put_contents($f1, 'dummy1');
    touch($f1, time() - 3600);
    file_put_contents($f2, 'dummy2');
    touch($f2, time() - 1800);

    // Sekarang createScheduledBackup (berkas ke-3)
    $newFile = $this->service->createScheduledBackup(force: true);

    expect(File::exists($newFile))->toBeTrue();
    // Berkas paling tua ($f1) harus sudah terhapus karena keep = 2
    expect(File::exists($f1))->toBeFalse();
    expect(File::exists($f2))->toBeTrue();
});

test('command artisan pib:backup-scheduled berjalan sukses', function () {
    $exitCode = Artisan::call('pib:backup-scheduled', ['--force' => true]);
    expect($exitCode)->toBe(0);
});
