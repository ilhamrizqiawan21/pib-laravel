<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class ScheduledBackupCommand extends Command
{
    protected $signature = 'pib:backup-scheduled {--force : Paksa pembuatan backup walau belum ada perubahan}';

    protected $description = 'Jalankan backup otomatis jika ada perubahan data sejak backup terakhir';

    public function handle(BackupService $backupService): int
    {
        $force = (bool) $this->option('force');

        $this->info('Memeriksa jadwal pencadangan data...');
        $target = $backupService->createScheduledBackup($force);

        if ($target) {
            $this->info("Backup berhasil dibuat: {$target}");
        } else {
            $this->line('Pencadangan dilewati (tidak ada perubahan atau interval belum lewat).');
        }

        return self::SUCCESS;
    }
}
