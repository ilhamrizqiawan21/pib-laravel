# ARCHITECTURE

Stack: **PHP 8.5 · Laravel 13 · Livewire 4 · Alpine.js · Tailwind CSS 4 (Vite) · Pest 4 · Larastan 3 · Pint**.
DB: **SQLite (default, dev & instalasi kecil) / MySQL 8 (opsional)** — kode portabel (ADR-002).
Ekspor: `maatwebsite/excel` (Excel), `barryvdh/laravel-dompdf` (PDF), `spatie/laravel-backup` (backup).

## Prinsip

1. **Aturan bisnis hidup di `app/Domain` dan `app/Services`**, bukan di Livewire/controller/Blade. Komponen UI tipis: validasi → panggil service → render.
2. **Domain murni** (`app/Domain/*`) tanpa DB/HTTP → mudah diuji, port langsung dari `lib/*.ts` aplikasi lama.
3. **Server adalah sumber kebenaran**: `score` dihitung server; klien tak pernah dipercaya.
4. **Satu pintu tulis**: perubahan data melalui service (transaksi + audit). Tidak ada `Model::create` bebas dari komponen UI untuk entitas ber-audit.
5. **Portabel**: tanpa SQL khusus dialek (SCHEMA.md).
6. **Aman secara default**: semua rute `auth`; otorisasi eksplisit; validasi di Form Request/`Validate` Livewire dengan aturan sama dengan service.

## Struktur direktori

```
app/
  Domain/                 # murni, tanpa framework I/O
    Scoring/              # ScoreCalculator ✔, StudentPosition ✔
    Recap/                # Progress ✔, (RecapFilter, RecapSummarizer — TODO)
  Enums/                  # Role, Semester, Gender, SyncStatus, CompletionStatus ✔
  Models/                 # Eloquent (relasi, cast, scope) ✔
  Services/               # use-case, transaksi, audit
    AuditLogger ✔
    SettingsRepository    # TODO  (cache)
    AcademicYearService   # TODO  (satu aktif)
    CurriculumService     # TODO  (edit, pindah, salin, hapus-aman)
    StudentService        # TODO  (CRUD, pindah kelas, hapus permanen, impor)
    ScoreService          # TODO  (record/clear + optimistic lock + idempotensi)
    ClassProgressService  # TODO  (payload Setoran satu kelas)
    RecapService          # TODO  (ringkasan, detail, matriks)
    Reports/{ExcelReport,PdfReport}  # TODO
    BackupService         # TODO  (status, unduh, restore + snapshot)
    LegacyImporter        # TODO  (pib:import-legacy)
  Http/
    Controllers/          # hanya yang non-Livewire: unduhan (Excel/PDF/backup), JSON penilaian
    Requests/             # Form Request untuk endpoint non-Livewire
    Middleware/           # EnsureActiveUser, SecurityHeaders
  Livewire/               # halaman & komponen (Dashboard, Classes, Students, Curriculum, Recap, Reports, Account, Admin\Users)
  Policies/               # bila perlu per-model; minimal Gate 'admin'
  Console/Commands/       # pib:create-admin, pib:import-legacy, pib:backup-status
resources/views/          # Blade: layouts, livewire/*, components/*, pdf/*
resources/css/app.css     # token desain (DESIGN.md) + Tailwind
resources/js/             # Alpine components: setoran.js, material-grid.js, draft-store.js
routes/web.php            # semua rute (di balik auth)
tests/{Unit,Feature,Fixtures}
docs/
```

## Dua gaya UI (sengaja)

| Area | Teknik | Mengapa |
|---|---|---|
| CRUD, rekap, laporan, akun, dashboard | **Livewire 4** (halaman penuh) | Cepat dibangun, validasi server, minim JS |
| **Setoran** & **Per materi** (input nilai) | **Blade + Alpine** + endpoint JSON | Keyboard-first, tanpa round-trip Livewire per ketukan, antrean draft offline-tolerant, payload satu kelas |

### Endpoint JSON penilaian (di `routes/web.php`, middleware `auth`, CSRF lewat header `X-XSRF-TOKEN`)

| Metode & path | Fungsi | Respons |
|---|---|---|
| `GET /penilaian/kelas/{class}/progress` | Payload satu kelas: `materials[]` (urutan kurikulum), `students[]`, `scores[]` termasuk baris kosong (membawa `version`) | 200 JSON, 404 |
| `POST /penilaian/sync` | Simpan satu operasi nilai **idempoten**. Body: `{id, device_id, student_id, assessment_id, mistakes\|null, base_version\|null, initials?, note?, assessed_at?}` | 200 `{status:"SYNCED", score, mistakes, version, assessed_at, initials, note}` · 409 konflik `{current:{…}}` · 422 validasi · 404 |

Aturan `POST /penilaian/sync` (port dari `app/api/sync` & `scores` lama):
1. Validasi (Form Request): `id` 8–100, `mistakes` int 0–90 atau null, `base_version` int|null (`null` = editor tidak melihat baris; **absen** = klien lama → tanpa cek konflik).
2. Dalam satu transaksi: pastikan siswa/kelas dan materi aktif serta materi berada pada tahun ajaran kelas siswa (404); cek `sync_operations.id` — bila `SYNCED` kembalikan hasil tersimpan.
3. Bila `base_version` diberikan dan ≠ versi baris saat ini (`null` jika tak ada baris) → **409** + nilai terkini.
4. Tulis: `score = ScoreCalculator::score(mistakes)`, `version = (lama ?? 0) + 1`, `assessed_at = payload ?? now()` (null bila dikosongkan), `assessor_id = auth id`;
   mengosongkan = update ke NULL (jangan hapus baris) bila baris ada, no-op bila tidak.
5. Catat `sync_operations` (SYNCED) + `AuditLogger` (`score`, `UPSERT|CLEAR`) — transaksi yang sama.

Di sisi klien (`draft-store.js`): kunci `pib-score-edit:{studentId}:{assessmentId}` di localStorage, event `pib-draft-change`, status
`dirty|pending|conflict|failed`, retry GET 1×, `crypto.getRandomValues` untuk id (jalan di HTTP LAN). Port dari `lib/assessment-workspace.ts` & `lib/score-client.ts` aplikasi lama.
Paraf dan catatan yang tidak diedit tidak dikirim agar nilai lama tetap terjaga. Bila satu nilai diedit lagi ketika permintaan sebelumnya masih berjalan, draft terbaru dikirim sesudah respons pertama dengan versi server terbaru.

## Rute (semua `auth` kecuali tertulis)

| Path | Tujuan | Peran |
|---|---|---|
| `GET /login`, `POST /login`, `POST /logout` | auth (guest untuk login) | publik/auth |
| `GET /up` | health | publik |
| `/` → redirect `/dashboard` | | |
| `/dashboard` | Beranda | semua |
| `/penilaian` | redirect ke mode terakhir | semua |
| `/penilaian/setoran`, `/penilaian/materi` | dua mode input | semua |
| `/rekap` | Rekap | semua |
| `/laporan` | Unduh Excel/PDF & backup | semua (restore: admin) |
| `/laporan/excel`, `/laporan/pdf`, `/laporan/pdf/siswa/{student}` | unduhan | semua |
| `/sekolah` | Sekolah & periode (identitas, tahun ajaran) | semua |
| `/kelas`, `/siswa`, `/siswa/impor`, `/siswa/template` | data induk | semua |
| `/materi`, `/materi/{chapter}`, `/materi/{chapter}/{subchapter}` | kurikulum | semua |
| `/akun`, `/pengaturan` | profil, identitas sekolah, ubah password | semua |
| `/admin/audit`, `/admin/restore` | admin | admin |

## Keamanan (lihat juga AI_RULES.md §Keamanan)

- Rute aplikasi: grup `auth` + `EnsureActiveUser`; admin via `Gate::authorize('admin')`/middleware `can:admin`. Root ikut auth. Tidak ada rute tulis dengan GET.
- Livewire membawa `auth`, `Authorize`, dan `EnsureActiveUser` dari halaman asal ke setiap update; endpoint update dibatasi 120/menit. Upload sementara harus bertanda tangan dan dibatasi 60/menit. Disk `local` tidak memiliki route serving publik.
- CSRF aktif (Livewire & fetch dengan header). Cookie sesi: `http_only`, `same_site=lax`, `secure` otomatis di HTTPS (`SESSION_SECURE_COOKIE=true` produksi).
- Login: `RateLimiter` 5/menit per email+IP; catat `LOGIN_FAILED` ke audit; pesan error generik; regenerasi sesi setelah login.
- Password: min 10; `Hash::make` default; ubah password butuh password lama; `Auth::logoutOtherDevices` opsional.
- Header (middleware `SecurityHeaders`): `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`,
  `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `Content-Security-Policy` (self + Vite dev), HSTS di produksi.
- Mass assignment: model memakai `#[Fillable]`; jangan `unguarded`. Input selalu lewat validated().
- Unggahan (impor siswa, restore): batasi ukuran & MIME, simpan di `storage/app/private`, jangan eksekusi, hapus setelah diproses.
- Restore: hanya admin, konfirmasi ketik ulang, validasi semua tabel dan admin aktif sebelum menghapus data, snapshot pra-restore, `down`, migrasi, audit dalam transaksi, `up` di `finally`.
- Produksi: `APP_DEBUG=false`, `APP_ENV=production`, `php artisan config:cache route:cache view:cache`, `.env` di luar webroot publik, `storage/` tidak publik.
- Dependensi: `composer audit` di CI; tidak ada paket tak terpakai.

## Performa

- `Model::preventLazyLoading(! app()->isProduction())` dan `Model::shouldBeStrict()` (non-produksi) di `AppServiceProvider` — N+1 menggagalkan tes.
- Payload Setoran: 3 query (materi berurut, siswa kelas, nilai kelas). Rekap: 1 query terjoin + agregasi di PHP lewat domain (`ScoreCalculator`), bukan SQL dialek.
- Cache `SettingsRepository` (`Cache::rememberForever`, invalidasi saat simpan). Jangan cache data nilai.
- Index sesuai SCHEMA.md; periksa `EXPLAIN` untuk query rekap saat paritas (TODO fase 6).
- Aset: Vite build produksi, font Inter lokal (`@fontsource-variable/inter`), tanpa CDN pihak ketiga.

## Backup

- `BackupService` membuat snapshot SQLite konsisten dengan `VACUUM INTO`; pada MySQL membuat cadangan JSON seluruh tabel aplikasi. Berkas terjadwal disimpan di `PIB_BACKUP_DIR`, dengan retensi `PIB_BACKUP_KEEP`.
- Snapshot pra-restore mengikuti format database yang dipakai (`.sqlite` atau `.json`). Cadangan JSON berisi semua tabel aplikasi, dan impor restore menolak tabel yang hilang atau cadangan tanpa admin aktif.
- Restore: validasi → snapshot → `down` → `migrate --force` → penggantian data dan audit dalam satu transaksi → `up`.
- Jalur MySQL diuji dengan database sementara MySQL 8.4.10: migrasi, backup JSON, snapshot pra-restore JSON, dan pemulihan siswa berhasil. Uji ulang pada host produksi tetap diperlukan.

## Pengujian

- **Unit/Domain**: Pest murni (`tests/Unit/Domain`) — perilaku hitung & posisi.
- **Feature/Schema**: integritas DB (`tests/Feature/Schema`).
- **Feature/HTTP & Livewire**: auth wajib, otorisasi peran, validasi, 409/422, audit tercatat, tanpa N+1.
- **Frontend Node**: `npm run test:js` memeriksa antrean draft dan respons kelas terlambat; CI menjalankannya setelah build Vite.
- **Paritas**: `tests/Feature/Parity` — fixture DB lama → `RecapService` = angka referensi (TODO fase 8).
- Gerbang: `composer check` = Pint(`--test`) + Larastan level 6 + Pest. Wajib hijau sebelum tiap tugas dianggap selesai.
- Kebijakan: tiap perilaku baru ⇒ tes dulu (merah → hijau). Tes mengambil kontrak dari PRD §4–5, bukan dari implementasi.
