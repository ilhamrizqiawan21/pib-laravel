<?php

namespace App\Services;

use App\Exceptions\RestoreException;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupService
{
    private const FILE_PATTERN = '/^pib-\d{8}-\d{6}\.(sqlite|json)$/';

    private const TABLES = [
        'users', 'academic_years', 'school_classes', 'students',
        'curriculum_templates', 'chapters', 'subchapters', 'assessments',
        'scores', 'settings', 'audit_logs', 'sync_operations',
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function getBackupDirectory(): string
    {
        /** @var string|null $configDir */
        $configDir = config('pib.backup.dir');

        return $configDir ?: storage_path('app/backups/scheduled');
    }

    public function getKeepCount(): int
    {
        /** @var int $keep */
        $keep = config('pib.backup.keep', 30);

        return max(1, $keep);
    }

    public function getIntervalHours(): int
    {
        /** @var int $hours */
        $hours = config('pib.backup.interval_hours', 2);

        return max(1, $hours);
    }

    /**
     * @return array<int, array{name: string, path: string, size: int, modified_at: string}>
     */
    public function listBackups(): array
    {
        $dir = $this->getBackupDirectory();
        if (! File::isDirectory($dir)) {
            return [];
        }

        $files = File::files($dir);
        $result = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();
            if (preg_match(self::FILE_PATTERN, $filename)) {
                $result[] = [
                    'name' => $filename,
                    'path' => $file->getPathname(),
                    'size' => $file->getSize(),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime())->toISOString(),
                ];
            }
        }

        usort($result, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    /**
     * @return array{name: string, path: string, size: int, modified_at: string}|null
     */
    public function latestBackup(): ?array
    {
        $backups = $this->listBackups();
        if (empty($backups)) {
            return null;
        }

        return $backups[array_key_last($backups)];
    }

    public function hasDatabaseChangedSince(?DateTimeInterface $since): bool
    {
        if ($since === null) {
            return true;
        }

        $sinceTimestamp = $since->getTimestamp();

        // 1. Periksa file fisik SQLite mtime bila koneksi SQLite
        $connection = DB::connection();
        if ($connection->getDriverName() === 'sqlite') {
            $dbPath = $connection->getDatabaseName();
            if ($dbPath !== ':memory:' && File::exists($dbPath)) {
                $filesToCheck = [$dbPath, "{$dbPath}-wal", "{$dbPath}-shm"];
                foreach ($filesToCheck as $filePath) {
                    if (File::exists($filePath) && filemtime($filePath) > $sinceTimestamp) {
                        return true;
                    }
                }
            }
        }

        // 2. Periksa audit logs terbaru
        try {
            /** @var string|null $latestAudit */
            $latestAudit = DB::table('audit_logs')->max('created_at');
            if ($latestAudit && Carbon::parse($latestAudit)->getTimestamp() > $sinceTimestamp) {
                return true;
            }
        } catch (\Throwable) {
            // Tabel mungkin belum ada
        }

        // 3. Periksa skor terbaru
        try {
            /** @var string|null $latestScore */
            $latestScore = DB::table('scores')->max('updated_at');
            if ($latestScore && Carbon::parse($latestScore)->getTimestamp() > $sinceTimestamp) {
                return true;
            }
        } catch (\Throwable) {
            // Tabel mungkin belum ada
        }

        return false;
    }

    public function createScheduledBackup(bool $force = false): ?string
    {
        $dir = $this->getBackupDirectory();
        File::ensureDirectoryExists($dir);

        $latest = $this->latestBackup();
        $now = now();

        if ($latest && ! $force) {
            $lastTime = Carbon::parse($latest['modified_at']);
            $intervalSeconds = $this->getIntervalHours() * 3600;

            if ($now->diffInSeconds($lastTime) < $intervalSeconds) {
                return null;
            }

            if (! $this->hasDatabaseChangedSince($lastTime)) {
                return null;
            }
        }

        $connection = DB::connection();
        $isSqlite = $connection->getDriverName() === 'sqlite';
        $stamp = $now->format('Ymd-His');
        $targetFile = $dir.'/pib-'.$stamp.($isSqlite ? '.sqlite' : '.json');

        if (File::exists($targetFile)) {
            return $force ? $targetFile : null;
        }

        $tmpFile = $targetFile.'.tmp';
        if (File::exists($tmpFile)) {
            File::delete($tmpFile);
        }

        if ($isSqlite) {
            $dbPath = $connection->getDatabaseName();
            if ($dbPath === ':memory:') {
                // Untuk testing sqlite in-memory
                file_put_contents($tmpFile, 'SQLite format 3');
            } else {
                $escapedTmp = str_replace("'", "''", $tmpFile);
                DB::statement("VACUUM INTO '{$escapedTmp}'");
            }
        } else {
            File::put($tmpFile, json_encode($this->backupPayload(), JSON_THROW_ON_ERROR));
        }

        File::move($tmpFile, $targetFile);

        // Rotasi retensi
        $keep = $this->getKeepCount();
        $allBackups = $this->listBackups();
        if (count($allBackups) > $keep) {
            $toDelete = array_slice($allBackups, 0, count($allBackups) - $keep);
            foreach ($toDelete as $old) {
                File::delete($old['path']);
            }
        }

        $this->auditLogger->record('backup', 'local', 'SNAPSHOT', 'Backup otomatis dibuat: '.basename($targetFile));

        return $targetFile;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     count: int,
     *     interval_hours: int,
     *     keep: int,
     *     last_backup: string|null,
     *     last_backup_time: string|null,
     *     last_backup_size: int|null
     * }
     */
    public function status(): array
    {
        $dir = $this->getBackupDirectory();
        $backups = $this->listBackups();
        $latest = $this->latestBackup();

        return [
            'enabled' => true,
            'count' => count($backups),
            'interval_hours' => $this->getIntervalHours(),
            'keep' => $this->getKeepCount(),
            'last_backup' => $latest['name'] ?? null,
            'last_backup_time' => $latest['modified_at'] ?? null,
            'last_backup_size' => $latest['size'] ?? null,
        ];
    }

    /**
     * @return array{
     *     app: string,
     *     version: int,
     *     schemaVersion: int,
     *     createdAt: string,
     *     data: array<string, array<int, array<string, mixed>>>
     * }
     */
    public function exportJson(): array
    {
        $payload = $this->backupPayload();

        $this->auditLogger->record('backup', 'local', 'DOWNLOAD', 'Backup lengkap dibuat');

        return $payload;
    }

    /**
     * @return array{app: string, version: int, schemaVersion: int, createdAt: string, data: array<string, array<int, array<string, mixed>>>}
     */
    private function backupPayload(): array
    {
        return DB::transaction(function (): array {
            $data = [];
            foreach (self::TABLES as $table) {
                $data[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            }

            return [
                'app' => 'pib-penilaian',
                'version' => 3,
                'schemaVersion' => 1,
                'createdAt' => now()->toISOString(),
                'data' => $data,
            ];
        });
    }

    public function createPreRestoreSnapshot(): string
    {
        $dir = storage_path('app/backups/snapshots');
        File::ensureDirectoryExists($dir);

        $stamp = now()->format('Ymd-His');
        $connection = DB::connection();
        $isSqlite = $connection->getDriverName() === 'sqlite';
        $targetFile = $dir.'/pre-restore-'.$stamp.($isSqlite ? '.sqlite' : '.json');

        if ($isSqlite) {
            $dbPath = $connection->getDatabaseName();
            if ($dbPath === ':memory:') {
                file_put_contents($targetFile, 'SQLite format 3 snapshot');
            } else {
                $escaped = str_replace("'", "''", $targetFile);
                DB::statement("VACUUM INTO '{$escaped}'");
            }
        } else {
            File::put($targetFile, json_encode($this->backupPayload(), JSON_THROW_ON_ERROR));
        }

        return $targetFile;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function restoreFromJson(array $payload, ?User $actor = null): int
    {
        if (($payload['app'] ?? '') !== 'pib-penilaian') {
            $this->auditLogger->record('restore', 'local', 'FAILED', 'File bukan cadangan PIB yang valid', null, $actor?->id);
            throw new RestoreException('File bukan cadangan PIB yang valid.');
        }

        if (! isset($payload['data']) || ! is_array($payload['data'])) {
            $this->auditLogger->record('restore', 'local', 'FAILED', 'Struktur berkas cadangan rusak', null, $actor?->id);
            throw new RestoreException('Struktur berkas cadangan tidak memiliki bagian data.');
        }

        foreach (self::TABLES as $table) {
            if (! array_key_exists($table, $payload['data']) || ! is_array($payload['data'][$table])) {
                $this->auditLogger->record('restore', 'local', 'FAILED', 'Struktur tabel cadangan tidak lengkap', null, $actor?->id);
                throw new RestoreException('Berkas cadangan tidak lengkap atau rusak.');
            }

            foreach ($payload['data'][$table] as $row) {
                if (! is_array($row) || $row === []) {
                    $this->auditLogger->record('restore', 'local', 'FAILED', 'Baris cadangan tidak valid', null, $actor?->id);
                    throw new RestoreException('Berkas cadangan tidak lengkap atau rusak.');
                }
            }
        }

        if ($payload['data']['users'] === []) {
            $this->auditLogger->record('restore', 'local', 'FAILED', 'Cadangan tidak memiliki pengguna', null, $actor?->id);
            throw new RestoreException('Berkas cadangan tidak memiliki akun pengguna.');
        }

        $hasActiveAdmin = false;
        $actorInBackup = false;
        foreach ($payload['data']['users'] as $user) {
            $hasActiveAdmin = $hasActiveAdmin || (($user['role'] ?? null) === 'admin' && (bool) ($user['is_active'] ?? false));
            $actorInBackup = $actorInBackup || ($actor !== null && (int) ($user['id'] ?? 0) === $actor->id);
        }

        if (! $hasActiveAdmin) {
            $this->auditLogger->record('restore', 'local', 'FAILED', 'Cadangan tidak memiliki admin aktif', null, $actor?->id);
            throw new RestoreException('Berkas cadangan tidak memiliki administrator aktif.');
        }

        $tablesOrder = self::TABLES;

        // 1. Buat snapshot pra-restore
        $this->createPreRestoreSnapshot();

        // 2. Aktifkan maintenance mode
        Artisan::call('down');
        if (! app()->maintenanceMode()->active()) {
            $this->auditLogger->record('restore', 'local', 'FAILED', 'Mode pemeliharaan gagal diaktifkan', null, $actor?->id);
            throw new RestoreException('Mode pemeliharaan gagal diaktifkan. Data belum diubah.');
        }

        try {
            Artisan::call('migrate', ['--force' => true]);

            $restoredCount = DB::transaction(function () use ($payload, $tablesOrder, $actor, $actorInBackup) {
                $data = $payload['data'];

                // Hapus data lama dengan urutan terbalik
                foreach (array_reverse($tablesOrder) as $table) {
                    DB::table($table)->delete();
                }

                $totalRows = 0;
                // Masukkan data baru sesuai urutan
                foreach ($tablesOrder as $table) {
                    $rows = $data[$table] ?? [];
                    if (! is_array($rows) || empty($rows)) {
                        continue;
                    }

                    foreach ($rows as $row) {
                        if (is_array($row) && ! empty($row)) {
                            // Bersihkan format boolean / JSON jika perlu
                            DB::table($table)->insert($row);
                            $totalRows++;
                        }
                    }
                }

                $this->auditLogger->record(
                    'restore',
                    'local',
                    'RESTORE',
                    "Pemulihan data berhasil ({$totalRows} baris)",
                    null,
                    $actorInBackup ? $actor?->id : null,
                    asSystem: ! $actorInBackup
                );

                return $totalRows;
            });

            return $restoredCount;
        } catch (\Throwable $e) {
            $this->auditLogger->record('restore', 'local', 'FAILED', 'Pemulihan data gagal', null, $actor?->id);
            throw new RestoreException('Gagal memulihkan data. Periksa log server dan snapshot pra-restore.', 0, $e);
        } finally {
            try {
                Artisan::call('up');
            } catch (\Throwable) {
                // Abaikan
            }
        }
    }

    public function restoreFromFile(string $filePath, ?User $actor = null): int
    {
        if (! File::exists($filePath)) {
            throw new RestoreException('Berkas cadangan tidak ditemukan.');
        }

        $content = File::get($filePath);
        $json = json_decode($content, true);

        if (is_array($json) && isset($json['app'])) {
            return $this->restoreFromJson($json, $actor);
        }

        throw new RestoreException('Format berkas cadangan belum didukung.');
    }
}
