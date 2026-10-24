# DECISIONS — Catatan Keputusan Arsitektur (ADR)

Format: **ID — Keputusan** · Status · Alasan · Konsekuensi. Eksekutor (AI) **tidak boleh membalik keputusan "Diterima"**; ajukan lewat
bagian *Usulan* di bawah dan tunggu pemilik.

## Diterima

**ADR-001 — Laravel 13 + Livewire 4 + Alpine + Tailwind 4.** Diterima.
Pemilik berkeahlian Laravel; Livewire cepat untuk CRUD/rekap. Layar input nilai (Setoran) memakai Alpine+JSON demi kecepatan keyboard (ARCHITECTURE.md).
Konsekuensi: dua gaya UI yang terdokumentasi; hindari Inertia/React.

**ADR-002 — SQLite dulu, kode portabel ke MySQL.** Diterima.
Beban kecil (satu sekolah). Satu file = backup mudah. Semua skema & query ditulis portabel sehingga pindah MySQL hanya konfigurasi (SCHEMA.md §Konvensi).
Konsekuensi: dilarang SQL khusus dialek; CI menjalankan tes di SQLite, dan **opsional** matriks MySQL.

**ADR-003 — Server = sumber kebenaran skor; versi integer untuk konflik.** Diterima.
`score` dihitung server; `scores.version` (bukan `updated_at`) untuk optimistic locking; operasi klien idempoten lewat `sync_operations.id`.

**ADR-004 — Data bersama untuk semua guru.** Diterima (asumsi default).
Satu sekolah, satu set data. Tidak ada kepemilikan data per guru; `assessor_id` hanya atribusi. Bila nanti diperlukan pemisahan, buat ADR baru.

**ADR-005 — Buang Electron/.bat; target server.** Diterima.

**ADR-006 — Enum string, bukan kolom `enum` DB.** Diterima. Portabilitas & mudah berubah.

**ADR-007 — ID dipertahankan saat impor data lama.** Diterima. Memudahkan verifikasi paritas.

**ADR-008 — Zona waktu aplikasi `Asia/Jakarta` (env `APP_TIMEZONE`).** Diterima.
Perbandingan "hari ini" ala aplikasi lama (isSameLocalDay) jadi benar tanpa konversi. Jangan ubah setelah ada data.

**ADR-009 — Tanpa halaman setup/registrasi publik.** Diterima. Akun pertama via artisan; admin membuat akun lain.

**ADR-010 — Gerbang mutu: Pint + Larastan level 6 + Pest.** Diterima. `composer check` wajib hijau sebelum tugas dianggap selesai.

**ADR-011 — Mode penggunaan single-user dengan login tetap wajib.** Diterima.
Profil akun, email, nama pengguna, nama sekolah, foto profil, dan password dikelola dari Pengaturan.
Navigasi Pengguna dihilangkan; route admin lama dipertahankan sementara untuk kompatibilitas administrasi dan migrasi data.

## Terbuka (butuh keputusan pemilik — gunakan asumsi default sampai dijawab)

| # | Pertanyaan | Asumsi default |
|---|---|---|
| Q1 | Server tujuan: VPS/on-prem, spesifikasi, ada MySQL? | Satu server Linux, PHP-FPM + nginx/Caddy, SQLite |
| Q2 | Berapa guru bersamaan? Perlu data terpisah per guru? | ≤ 10 guru, data bersama (ADR-004) |
| Q3 | Perlu fitur mode offline lebih dari antrean draft? | Tidak |
| Q4 | Perlu domain/HTTPS publik atau hanya LAN/VPN (mis. Tailscale)? | HTTPS di belakang reverse proxy |
| Q5 | Backup disimpan ke mana (disk lokal/S3/Google Drive)? | Disk lokal + salinan manual |
| Q6 | Data lama dipindahkan (impor) atau mulai bersih? | Impor via `pib:import-legacy` |
| Q7 | Perlu tema gelap / PWA terpasang di ponsel? | Tidak (P2) |

## Usulan (belum diterima)

_(kosong — eksekutor menulis di sini bila menemukan kebutuhan mengubah keputusan di atas)_
