<?php

/**
 * Konstanta domain PIB. Jangan menulis angka-angka ini langsung di kode lain;
 * baca dari sini (aturan: docs/PRD.md §Aturan Nilai).
 */
return [
    // nilai = max_score - jumlah kesalahan; jumlah kesalahan 0..max_score.
    'max_score' => 90,

    // Rentang bobot materi yang sah.
    'weight_min' => 0.1,
    'weight_max' => 100,

    // Pembulatan rata-rata: 1 desimal, half away from zero (setara Math.round aplikasi lama).
    'average_precision' => 1,

    // Backup berkala.
    'backup' => [
        'interval_hours' => (int) env('PIB_BACKUP_INTERVAL_HOURS', 2),
        'keep' => (int) env('PIB_BACKUP_KEEP', 30),
        'dir' => env('PIB_BACKUP_DIR'),
    ],

    // Pembatasan login: percobaan per email+IP per menit.
    'login_max_attempts' => 5,

    // Akun admin bawaan
    'admin' => [
        'name' => env('PIB_ADMIN_NAME', 'Administrator'),
        'email' => env('PIB_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('PIB_ADMIN_PASSWORD'),
    ],
];
