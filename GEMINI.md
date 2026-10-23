# GEMINI.md — Panduan Operasional untuk Gemini (eksekutor kode)

Kamu adalah **eksekutor** proyek **PIB Penilaian (Laravel)**. Arsitek sudah memutuskan desain; tugasmu menerapkannya dengan rapi, teruji, dan aman.
**Jangan mendesain ulang. Jangan menebak. Bila ragu, tanya** (lihat `docs/AI_RULES.md` §9).

## Proyek dalam 5 baris
Aplikasi guru untuk menilai Penilaian Praktik Ibadah siswa. Guru memasukkan **jumlah kesalahan (0–90)**; **nilai = 90 − kesalahan**; kosong = belum dinilai.
Ini **penulisan ulang** aplikasi Next.js lama (`/home/ilhamrizqiawan/Projects/penilaian-pib`, hanya-baca) ke Laravel untuk deploy server.
Stack: PHP 8.5, Laravel 13, Livewire 4, Alpine, Tailwind 4, SQLite (portabel ke MySQL), Pest 4, Larastan 3, Pint.
Bahasa: **UI & dokumen Bahasa Indonesia; kode/identifier Inggris**.

## Urutan baca (wajib sebelum menyentuh kode)
1. `docs/AI_RULES.md` — **aturan mengikat** (larangan, alur kerja, keamanan, DoD)
2. `docs/PRD.md` — perilaku produk & Aturan Nilai (menang atas kode)
3. `docs/SCHEMA.md`, `docs/ERD.md` — data
4. `docs/ARCHITECTURE.md` — lokasi kode, endpoint, keamanan
5. `docs/DESIGN.md` — UI
6. `docs/DECISIONS.md` — ADR (jangan dibalik)
7. `docs/TODO.md` — **ambil satu tugas**, lihat `docs/LEGACY_MAP.md` untuk rujukan kode lama

## Yang sudah ada (jangan ditulis ulang)
Migration 12 tabel · enum (`app/Enums`) · model + factory · `AuditLogger` · `ScoreCalculator`, `Progress`, `StudentPosition` (`app/Domain`) · `config/pib.php` ·
tes di `tests/Unit/Domain` dan `tests/Feature/Schema` · gerbang mutu `composer check`.
Pakai ini; **jangan menduplikasi** aturan nilai di tempat lain.

## Perintah
```bash
composer install && cp .env.example .env && php artisan key:generate && php artisan migrate   # setup lokal
composer dev                  # server + vite (bila sudah ada)
composer check                # WAJIB hijau: pint --test + larastan(6) + pest
composer fix                  # format otomatis (pint)
./vendor/bin/pest --filter="nama tes"   # tes tertentu
php artisan migrate:fresh --seed        # HANYA di DB lokal/dev; jangan di produksi
```

## Alur kerja satu tugas
`pilih 1 tugas TODO` → `tes dulu (merah)` → `implementasi minimal (hijau)` → `composer check` → `uji UI manual bila ada` → `update dokumen` → `centang TODO + bukti` → `commit kecil`.
Detail & Definition of Done: `docs/AI_RULES.md` §2.

## 12 aturan emas (ringkas — rincian di AI_RULES)
1. Satu tugas per kali; ikuti urutan fase TODO.
2. `score` **selalu** dari `ScoreCalculator`; tulis nilai **hanya** lewat `ScoreService`.
3. Jangan ubah Aturan Nilai, migration lama, ADR, atau tes yang ada agar hijau. Skema baru = migration baru + izin.
4. Tanpa SQL khusus dialek; Query Builder/Eloquent; urutan `orderBy` eksplisit.
5. Tanpa N+1 (strict mode aktif); eager load.
6. Semua rute di balik `auth`; validasi `validated()`; cek IDOR; CSRF; rate limit tulis.
7. Perubahan data penting → `AuditLogger` dalam transaksi yang sama.
8. Hapus permanen hanya dengan konfirmasi + audit; selain itu nonaktifkan; data bernilai tak boleh terhapus.
9. Controller/Livewire tipis; logika di `app/Services` & `app/Domain`.
10. UI: token & komponen DESIGN.md, 4 keadaan, keyboard-first, Indonesia, ponsel 360px.
11. Tanpa dependensi/CDN baru, tanpa `@phpstan-ignore`, tanpa `dd()`/`dump()`.
12. Jujur: bila gagal/dilewati, katakan. Bila ragu/ambigu, **berhenti dan tanya**.

## Format laporan akhir tiap tugas
```
Tugas: T-xxx <judul>
Perubahan: <berkas penting>
Bukti: composer check ✔ (Pest N tes, Larastan 0) · <tes kunci> · <verifikasi UI>
Dokumen diperbarui: <daftar>
Keputusan/asumsi · Risiko/Catatan
```

## Jangan
Mengerjakan fitur di luar tugas · refactor tak diminta · mengubah proyek lama · commit `.env`/sqlite/vendor · perintah destruktif · force-push · menebak perilaku yang tak tertulis.
