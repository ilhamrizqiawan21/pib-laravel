<?php

namespace App\Console\Commands;

use App\Services\LegacyImporter;
use Illuminate\Console\Command;

class ImportLegacyCommand extends Command
{
    protected $signature = 'pib:import-legacy
        {path : Jalur ke berkas SQLite aplikasi lama}
        {--dry-run : Jalankan simulasi impor tanpa menyimpan perubahan}
        {--force : Paksa impor walau database tujuan berisi data (membuat snapshot otomatis)}';

    protected $description = 'Impor data lengkap dari database SQLite aplikasi Next.js lama ke skema Laravel baru';

    public function handle(LegacyImporter $importer): int
    {
        $path = (string) $this->argument('path');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info("Memulai proses impor dari berkas: {$path}");
        if ($dryRun) {
            $this->warn('Mode DRY-RUN aktif: perubahan tidak akan disimpan ke database.');
        }

        try {
            $report = $importer->import($path, $dryRun, $force);

            $this->newLine();
            $this->info('Ringkasan Data yang Diimpor:');
            $rows = [];
            foreach ($report['tables'] as $table => $count) {
                $rows[] = [$table, $count];
            }
            $this->table(['Tabel', 'Jumlah Baris'], $rows);

            if (! empty($report['anomalies'])) {
                $this->warn('Anomali Terdeteksi:');
                foreach ($report['anomalies'] as $anomaly) {
                    $this->line(" - {$anomaly}");
                }
            } else {
                $this->info('Tidak ada anomali pada nilai atau relasi.');
            }

            if ($dryRun) {
                $this->info('Simulasi impor selesai dengan sukses.');
            } else {
                $this->info('Impor data lama berhasil diselesaikan.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal mengimpor data: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}

