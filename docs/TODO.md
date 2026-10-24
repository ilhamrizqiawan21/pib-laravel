# TODO — Rencana Eksekusi

Aturan: kerjakan **berurutan per fase**, satu tugas per kali ([AI_RULES.md](AI_RULES.md) §2). Centang `[x]` hanya bila kriteria penerimaan (KP) terbukti dan `composer check` hijau.
Setiap tugas: **Ref** = dokumen/kode lama yang jadi acuan · **Berkas** = tempat kode · **KP** = kriteria penerimaan · **Tes** = tes minimum.
Legenda prioritas mengikuti PRD (P0 wajib 1.0).

---

## Fase 0 — Fondasi ✅ (selesai oleh Claude, 2026-10-21)

- [x] Proyek Laravel 13 + Livewire 4 + Pest 4 + Larastan + Pint + Excel + DomPDF + spatie/backup terpasang.
- [x] Migration lengkap (12 tabel) + enum + model + factory + `AuditLogger`.
- [x] Domain murni: `ScoreCalculator`, `Progress`, `StudentPosition` (+ 48 tes lulus). `composer check` hijau.
- [x] Dokumen: PRD, ERD, SCHEMA, DESIGN, ARCHITECTURE, AI_RULES, DECISIONS, LEGACY_MAP, DEPLOYMENT, GEMINI.md.
- [x] Konfigurasi: timezone `Asia/Jakarta`, locale `id`, `config/pib.php`, `.env.example`.

---

## Fase 1 — Cangkang aplikasi & autentikasi

- [x] **T-101 Mode ketat & lokal id** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Configuration/StrictModelAndLocaleTest.php (6 tes), composer check ✔ (54 tes, Larastan 0 error).
  Ref: ARCHITECTURE §Performa. Berkas: `app/Providers/AppServiceProvider.php`, `lang/id/*`, `config/app.php` (sudah).
  KP: `Model::shouldBeStrict(! production)` (termasuk `preventLazyLoading`, `preventSilentlyDiscardingAttributes`, `preventAccessingMissingAttributes`); tes yang ada diperbaiki dengan eager loading (bukan mematikan strict). `lang/id` (validation, auth, passwords, pagination) tersedia.
  Tes: seluruh suite tetap hijau.
- [x] **T-102 Vite + Tailwind + token desain** (P0) — selesai 2026-10-21. Bukti: npm run build sukses, tests/Feature/Configuration/ViteAndDesignTokensTest.php (3 tes), composer check ✔ (57 tes, Larastan 0 error).
  Ref: DESIGN §1. Berkas: `resources/css/app.css`, `vite.config.js`, `package.json`.
  KP: token CSS variable + `@theme` Tailwind; font Inter lokal (`@fontsource-variable/inter`); `npm run build` sukses; tanpa CDN.
- [x] **T-103 Layout aplikasi & komponen dasar** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Layout/AppLayoutTest.php (5 tes), composer check ✔ (62 tes, Larastan 0 error), npm run build sukses.
  Ref: DESIGN §2–3, lama `app/sidebar.tsx`, `app-layout.tsx`, `ui.tsx`. Berkas: `resources/views/layouts/app.blade.php`, `components/*`.
  KP: sidebar (ciut/lebar, ingat pilihan tanpa kedip), topbar, skip link, menu & label baku, drawer ponsel; komponen: page-header, card, button, field/input, modal, toast, badge, progress-bar, empty/error/loading-state, icon (Lucide). Halaman contoh `/dashboard` kosong.
  Tes: render halaman memuat landmark `nav`/`main`; menu admin tersembunyi untuk guru.
- [x] **T-104 Login/logout + rate limit + EnsureActiveUser** (P0, FR-A1/A2) — selesai 2026-10-21. Bukti: tests/Feature/Auth/AuthenticationTest.php (7 tes), composer check ✔ (69 tes, Larastan 0 error).
  Ref: PRD §5.1, ARCHITECTURE §Keamanan. Berkas: `app/Livewire/Auth/Login.php` (atau controller), `routes/web.php`, `app/Http/Middleware/EnsureActiveUser.php`.
  KP: semua rute selain `/login` & `/up` di grup `auth`; gagal login generik; 5/menit/email+IP; akun nonaktif ditolak; sesi diregenerasi; `LOGIN_FAILED` ke audit.
  Tes: tamu→redirect; login benar/salah; rate limit; nonaktif ditolak; logout.
- [x] **T-105 `pib:create-admin` + `AdminSeeder`** (P0, FR-A4) — selesai 2026-10-21. Bukti: tests/Feature/Commands/CreateAdminCommandTest.php (4 tes), composer check ✔ (73 tes, Larastan 0 error).
  KP: interaktif & non-interaktif (`--name --email --password`), password ≥10, idempoten (tidak menggandakan email), baca `PIB_ADMIN_*` untuk seeder.
  Tes: perintah membuat admin; menolak password pendek/email duplikat.
- [x] **T-106 SecurityHeaders middleware** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Security/SecurityHeadersTest.php (2 tes), composer check ✔ (75 tes, Larastan 0 error).
  KP: header sesuai ARCHITECTURE §Keamanan di semua respons web; CSP kompatibel Vite dev & build.
  Tes: header ada.
- [x] **T-107 Akun: ubah password** (P0, FR-A3) — selesai 2026-10-21. Bukti: tests/Feature/Livewire/AkunTest.php (6 tes), composer check ✔ (81 tes, Larastan 0 error).
  Berkas: `Livewire/Akun.php`. KP: butuh password lama, min 10, audit `user UPDATE` tanpa menyimpan password. Tes: sukses/gagal.
- [x] **T-108 Pengaturan CI** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Configuration/CiWorkflowTest.php (1 tes), composer check ✔ (82 tes, Larastan 0 error).
  Berkas: `.github/workflows/ci.yml`. KP: PHP 8.5, `composer install`, `composer check`, `composer audit`, `npm ci && npm run build`. (Opsional matriks MySQL.)

---

## Fase 2 — Data induk

- [x] **T-201 `SettingsRepository` + halaman Sekolah (identitas)** (P0, FR-M7) — selesai 2026-10-21. Bukti: tests/Feature/Settings/SettingsRepositoryTest.php (2 tes), tests/Feature/Livewire/SekolahTest.php (3 tes), composer check ✔ (87 tes, Larastan 0 error).
  Ref: lama `api/settings`. KP: kunci `school_name|school_address|teacher_name`; cache; validasi panjang (150/250/120); audit.
- [x] **T-202 `AcademicYearService` + UI tahun ajaran** (P0, FR-M1) — selesai 2026-10-21. Bukti: tests/Feature/Services/AcademicYearServiceTest.php (7 tes), tests/Feature/Livewire/SekolahTest.php (4 tes), composer check ✔ (95 tes, Larastan 0 error).
  Ref: lama `api/academic-years`. KP: format `YYYY/YYYY`; semester Ganjil/Genap; unik; **maks satu aktif** (transaksi); hapus hanya bila tak dipakai.
  Tes: mengaktifkan satu menonaktifkan lain; duplikat ditolak; hapus ditolak bila ada kelas/template.
- [x] **T-203 `SchoolClass` CRUD** (P0, FR-M2) — selesai 2026-10-21. Bukti: tests/Feature/Services/SchoolClassServiceTest.php (5 tes), tests/Feature/Livewire/KelasTest.php (4 tes), composer check ✔ (104 tes, Larastan 0 error).
  Ref: lama `api/classes`, `app/classes`. KP: tambah/ubah/nonaktifkan; nama unik per tahun ajaran; filter periode; hapus permanen hanya bila tanpa siswa; 4 keadaan UI.
- [x] **T-204 `StudentService` + UI Siswa** (P0, FR-M3) — selesai 2026-10-21. Bukti: tests/Feature/Services/StudentServiceTest.php (4 tes), tests/Feature/Livewire/SiswaTest.php (4 tes), composer check ✔ (112 tes, Larastan 0 error).
  Ref: lama `api/students`, `app/students`. KP: CRUD, pindah kelas, nonaktifkan; NIS `''`→NULL & unik; gender L/P/kosong; **hapus permanen** (modal bahaya, hitung nilai terdampak, transaksi, audit); pencarian & filter kelas; paginasi.
  Tes: NIS duplikat; hapus permanen ikut menghapus nilai & mengaudit.
- [x] **T-205 Impor siswa + template** (P0, FR-M4) — selesai 2026-10-21. Bukti: tests/Feature/Services/StudentImportTest.php (2 tes), tests/Feature/Livewire/SiswaImporTest.php (2 tes), composer check ✔ (116 tes, Larastan 0 error).
  Ref: lama `api/students/import`, `student-import.ts`, `api/students/template`. KP: xlsx/csv ≤1000 baris, ≤2 MB; pratinjau; hasil `{imported, failed[{row,error}]}`; template unduhan; transaksi; audit.
  Tes: baris gagal dilaporkan, sisanya masuk.

---

## Fase 3 — Kurikulum

- [x] **T-301 `CurriculumService` (domain tulis)** (P0, FR-M5) — selesai 2026-10-21. Bukti: tests/Feature/Services/CurriculumServiceTest.php (9 tes), composer check ✔ (125 tes, Larastan 0 error).
  Ref: lama `curriculum.ts` + `curriculum.test.ts`, `curriculum-schema.ts`. KP: buat/ubah bab-subbab-materi, nama unik per induk, bobot 0,1–100, pindah urutan (naik/turun, renumber stabil), nonaktifkan, hapus permanen **hanya tanpa nilai** (`CurriculumException`), audit.
  Tes: port semua kasus `curriculum.test.ts`.
- [x] **T-302 Salin struktur + pratinjau** (P0, FR-M6) — selesai 2026-10-21. Bukti: tests/Feature/Services/CurriculumCopyTest.php (3 tes), composer check ✔ (128 tes, Larastan 0 error).
  Ref: lama `previewCopy`, `copyPeriod`, `templates/[id]/duplicate`. KP: sumber→target (tahun ajaran), pratinjau (bab/subbab/materi), tidak menimpa, nama unik, transaksi, audit `COPY`.
- [x] **T-303 UI Materi** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Livewire/MateriTest.php (5 tes), composer check ✔ (133 tes, Larastan 0 error).
  Ref: DESIGN §4.8, lama `app/master-data/curriculum/*`. KP: navigasi template→bab→subbab→materi (breadcrumb), edit inline, urutan, nonaktifkan, salin, 4 keadaan.

---

## Fase 4 — Penilaian (inti)

- [x] **T-401 `ScoreService`** (P0, FR-S3/S4) — selesai 2026-10-21. Bukti: tests/Feature/Services/ScoreServiceTest.php (8 tes), composer check ✔ (141 tes, Larastan 0 error).
  Ref: ARCHITECTURE §Endpoint JSON, lama `api/scores`, `api/sync`, `score-version.ts`. Berkas: `app/Services/ScoreService.php`, `app/Exceptions/ScoreConflictException.php`.
  KP: `record(User, studentId, assessmentId, ?mistakes, ?baseVersion, opId, deviceId, extras)`; `score` dari `ScoreCalculator`; versi naik; mengosongkan = update NULL (baris tetap); idempoten via `sync_operations`; konflik → exception membawa nilai terkini; audit `UPSERT|CLEAR`; semua dalam transaksi.
  Tes: nilai/kosong, 0–90 batas, konflik (`null` vs versi), idempotensi, audit, baris kosong tetap ada.
- [x] **T-402 `ClassProgressService` + `GET /penilaian/kelas/{class}/progress`** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Services/ClassProgressServiceTest.php (4 tes), composer check ✔ (145 tes, Larastan 0 error).
  Ref: lama `class-progress.ts`. KP: payload `{classId, className, materials[], students[], scores[]}` (materi aktif tahun ajaran kelas, urutan kurikulum; siswa aktif urut nama; **semua** baris nilai termasuk kosong). 3 query.
  Tes: urutan, baris kosong ikut, tanpa N+1, 404.
- [x] **T-403 `POST /penilaian/sync`** (P0) — selesai 2026-10-21. Bukti: tests/Feature/Http/ScoreSyncEndpointTest.php (6 tes), composer check ✔ (151 tes, Larastan 0 error).
  KP: Form Request, rate limit 120/menit/pengguna, respons 200/409/422/404 sesuai ARCHITECTURE; CSRF.
  Tes: kontrak per kode status; tamu 401/302.
- [x] **T-404 `draft-store.js` + helper fetch** (P0, FR-S4) — selesai 2026-10-21. Bukti: resources/js/draft-store.js, build npm build ✔, composer check ✔ (151 tes).
  Ref: lama `assessment-workspace.ts`, `score-client.ts`. KP: kunci localStorage `pib-score-edit:*`, event `pib-draft-change`, status dirty/pending/conflict/failed, retry GET 1×, id via `crypto.getRandomValues`, tanpa dependensi. Tes JS ringan (Vitest/Node test) opsional — minimal uji manual terdokumentasi.
- [x] **T-405 Layar Setoran** (P0, FR-S1/S5) — selesai 2026-10-21. Bukti: tests/Feature/Http/SetoranPageTest.php (4 tes), resources/js/setoran.js, resources/views/penilaian/setoran.blade.php, npm build ✔, composer check ✔ (155 tes, Larastan 0 error).
  Ref: DESIGN §4.3, lama `app/individual-assessment/page.tsx`, `docs/individual-assessment-verification.md`. KP: sesuai DESIGN; kursor di materi berikutnya; **Terlewat**; tombol 90; `/` cari siswa; ingat kelas & mode; tahan jaringan putus (draft); ponsel.
  Tes: Feature (render, payload), manual (keyboard, offline).
- [x] **T-406 Layar Per materi** (P0, FR-S2) — selesai 2026-10-21. Bukti: tests/Feature/Http/MateriAssessmentPageTest.php (3 tes), resources/js/materi-assessment.js, resources/views/penilaian/materi.blade.php, npm build ✔, composer check ✔ (158 tes, Larastan 0 error).
  Ref: lama `app/assessment/page.tsx`. KP: sesuai DESIGN §4.4; ± langkah; paraf/catatan/tanggal; progres.
- [x] **T-407 Indikator draft global + pengalih mode** (P0) — selesai 2026-10-21. Bukti: resources/js/draft-indicator.js, tests/Feature/Http/SetoranPageTest.php (6 tes), npm build ✔, composer check ✔ (160 tes, Larastan 0 error).
  Ref: lama `draft-indicator.tsx`, `assessment-mode.tsx`.

---

## Fase 5 — Rekap & Beranda

- [x] **T-501 `RecapFilter` + `RecapService`** (P0, FR-R1/FR-L3) — selesai 2026-10-21. Bukti: tests/Feature/Services/RecapServiceTest.php (5 tes), composer check ✔ (165 tes, Larastan 0 error).
  Ref: lama `filters.ts`, `recap-rows.ts`, `recap-data.ts`, `recap-query.ts`, tes `recap-*.test.ts`. KP: kontrak filter tunggal (dipakai rekap, Excel, PDF); ringkasan per siswa + detail; status & persen sesuai PRD; urutan deterministik; query tanpa N+1.
  Tes: port semua kasus lama (konsistensi rekap).
- [x] **T-502 UI Rekap + matriks** (P0, FR-R1/R2) — selesai 2026-10-21. Bukti: tests/Feature/Livewire/RekapTest.php (5 tes), app/Livewire/Rekap.php, resources/views/livewire/rekap.blade.php, composer check ✔ (170 tes, Larastan 0 error).
  DESIGN §4.5; filter di query string.
- [x] **T-503 `DashboardService` + Beranda + checklist** (P0, FR-D1) — selesai 2026-10-21. Bukti: tests/Feature/Services/DashboardServiceTest.php (3 tes), tests/Feature/Livewire/DashboardTest.php (3 tes), app/Livewire/Dashboard.php, resources/views/livewire/dashboard.blade.php, composer check ✔ (176 tes, Larastan 0 error).
  Ref: lama `dashboard.ts`, `onboarding.ts`, tes terkait, `app/dashboard/*`.

---

## Fase 6 — Laporan

- [x] **T-601 Excel** (P0, FR-L1) — selesai 2026-10-22. Bukti: tests/Feature/Services/ReportGrouperTest.php (3 tes), tests/Feature/Http/ReportExcelTest.php (3 tes), app/Services/ReportGrouper.php, app/Services/ExcelReportService.php, app/Http/Requests/ReportFilterRequest.php, composer check ✔ (182 tes, Larastan 0 error).
  Ref: lama `api/export`; lima lembar (Ringkasan, Laporan per Materi, Rekap Siswa, Rekap Kelas, Detail Nilai); styling header & zebra; angka sesuai `RecapService`.
- [x] **T-602 PDF kelas** (P0, FR-L2) — selesai 2026-10-22. Bukti: tests/Feature/Http/ReportPdfClassTest.php (3 tes), resources/views/pdf/class-report.blade.php, app/Http/Controllers/ReportController.php, composer check ✔ (185 tes, Larastan 0 error).
  Ref: lama `api/pdf`; Blade+DomPDF A4 portrait; header berulang, nomor halaman "Halaman {PAGE_NUM} dari {PAGE_COUNT}", identitas sekolah/alamat, ringkasan akhir.
- [x] **T-603 PDF per siswa** (P0) — selesai 2026-10-22. Bukti: tests/Feature/Http/ReportPdfStudentTest.php (3 tes), resources/views/pdf/student-report.blade.php, app/Http/Controllers/ReportController.php, composer check ✔ (188 tes, Larastan 0 error).
  Ref: lama `api/pdf/student`; A4 portrait, materi terkelompok per bab & subbab, rata-rata berbobot, blok tanda tangan guru penilai, slug berkas aman `rapor-{nama-siswa}.pdf`.
- [x] **T-604 Halaman Laporan (bagian unduh)** (P0) — selesai 2026-10-22. Bukti: tests/Feature/Livewire/LaporanTest.php (3 tes), app/Livewire/Laporan.php, resources/views/livewire/laporan.blade.php, composer check ✔ (191 tes, Larastan 0 error).
  DESIGN §4.9; filter sinkron dengan Rekap, kartu unduhan Excel, PDF Kelas, dan pemilih siswa untuk Rapor individual.

---

## Fase 7 — Backup, restore, admin, audit

- [x] **T-701 Backup terjadwal** (P0, FR-B1) — selesai 2026-10-22. Bukti: tests/Feature/Services/BackupScheduledTest.php (7 tes), app/Services/BackupService.php, app/Console/Commands/ScheduledBackupCommand.php, routes/console.php, config/backup.php, composer check ✔ (198 tes, Larastan 0 error).
  Logika deteksi perubahan mtime file DB + audit_logs / scores max updated_at, rotasi retensi `PIB_BACKUP_KEEP` (default 30), audit log `SNAPSHOT`.
- [x] **T-702 Unduh backup + status** (P0, FR-B2) — selesai 2026-10-22. Bukti: tests/Feature/Http/BackupDownloadTest.php (4 tes), app/Http/Controllers/BackupController.php, exportJson di BackupService, composer check ✔ (202 tes, Larastan 0 error).
  Endpoint GET /backup/status, GET /backup/unduh, POST /backup/manual; panel status terintegrasi di Livewire Laporan.
- [x] **T-703 Restore aman + snapshot pra-migrasi** (P0, FR-B3/B4) — selesai 2026-10-22. Bukti: tests/Feature/Services/BackupRestoreTest.php (3 tes), tests/Feature/Http/AdminRestoreTest.php (3 tes), app/Http/Controllers/Admin/RestoreController.php, composer check ✔ (208 tes, Larastan 0 error).
  Otorisasi khusus admin, snapshot pra-restore otomatis (`pre-restore-*.sqlite`), maintenance mode `down` -> snapshot -> delete/insert -> `migrate --force` -> `up` di `finally`, validasi teks konfirmasi "PULIHKAN", audit `RESTORE|FAILED`.
- [x] **T-704 Admin Pengguna** (P1, FR-A5) — selesai 2026-10-22. Bukti: tests/Feature/Livewire/Admin/PenggunaTest.php (8 tes), app/Livewire/Admin/Pengguna.php, resources/views/livewire/admin/pengguna.blade.php, composer check ✔ (216 tes, Larastan 0 error).
  Halaman /admin/pengguna: buat pengguna, ubah peran (admin/guru), reset password, nonaktifkan/aktifkan, perlindungan admin terakhir aktif tidak dapat dinonaktifkan/diturunkan, audit log tercatat.
- [x] **T-705 Halaman Audit** (P0, FR-D2) — selesai 2026-10-22. Bukti: tests/Feature/Livewire/Admin/AuditTest.php (4 tes), app/Livewire/Admin/Audit.php, resources/views/livewire/admin/audit.blade.php, composer check ✔ (220 tes, Larastan 0 error).
  Halaman /admin/audit: pencarian teks, filter entitas, filter aksi, paginasi 25 baris, tampilan meta JSON.

---

## Fase 8 — Paritas data lama

- [x] **T-801 Fixture DB lama** — selesai 2026-10-22. Bukti: tests/Fixtures/generate.php, tests/Fixtures/legacy-sample.sqlite (3 kelas, 30 siswa, 9 materi kurikulum berbobot beragam, variasi nilai lengkap, sebagian, dan kosong).
- [x] **T-802 `pib:import-legacy`** (P0) — selesai 2026-10-22. Bukti: tests/Feature/Import/LegacyImportTest.php (4 tes), app/Services/LegacyImporter.php, app/Console/Commands/ImportLegacyCommand.php, composer check ✔ (224 tes, Larastan 0 error).
  Spesifikasi SCHEMA.md: ID dipertahankan, pemetaan tabel lama ke skema baru, penyesuaian snake_case settings, akun LOCAL_ONLY dinonaktifkan, proteksi --force dengan snapshot otomatis pra-impor, opsi --dry-run.
- [x] **T-803 Uji paritas** (P0) — selesai 2026-10-22. Bukti: tests/Feature/Parity/LegacyParityTest.php (2 tes), composer check ✔ (226 tes, Larastan 0 error).
  Untuk setiap 30 siswa, hasil `RecapService` (assessed, total nilai, rata-rata berbobot) 100% IDENTIK dengan query SQL asli pada DB lama; buku kerja Excel memuat angka paritas yang sama persis.

---

## Fase 9 — Pengerasan & rilis

- [x] **T-901 Dokumen deploy teruji** — selesai 2026-10-23. Bukti: instalasi SQLite di container PHP 8.5.12 berhasil; migrasi, admin, cache, route:list, npm ci/build, `/up` 200, `/login` 200; `composer check` ✔ (226 tes, Larastan 0 error).
- [x] **T-902 Audit keamanan** — selesai 2026-10-23. Bukti: `docs/security-checklist.md`, `tests/Feature/Security/RouteAccessAuditTest.php` (7 tes); route inventory, auth/admin/CSRF, signed upload, validation, rate limit; `composer check` ✔ (235 tes, Larastan 0 error).
- [x] **T-903 Audit performa** — selesai 2026-10-23. Bukti: `scripts/performance-audit.php`, `docs/performance.md`; fixture 5 kelas × 40 siswa × 100 materi; rekap 27 ms (<500 ms), Excel 2,13 dtk dan PDF median 9,91 dtk (<10 dtk), payload Setoran 3 query; composer check ✔ (235 tes, Larastan 0 error). PDF mendekati batas (maks 10,27 dtk; 394 MiB), `DEPLOYMENT.md` kini menyarankan memory_limit 512M.
- [x] **T-904 A11y & responsif** — selesai 2026-10-23. Bukti: browser 360×800 dan 1366×768 pada login + 13 halaman internal (HTTP 200, tanpa luapan horizontal); keyboard drawer, menu, dialog, `/` + Enter Setoran; `LaporanTest` menguji relasi kelas tanpa lazy loading; `npm run build` dan `composer check` hijau (236 tes, Larastan 0 error). Tabel menjadi kartu pada ponsel, kecuali matriks rekap yang dapat digeser.
- [ ] **T-905 Rilis 1.0** — semua KP PRD §8 centang; tag `v1.0.0`; catatan rilis.

  Audit backend lanjutan 2026-10-24: restore, sinkronisasi nilai lintas periode, impor kelas bernama sama, hitungan beranda, dan aturan password diperbaiki; `composer check` lokal hijau (248 tes, Larastan 0 error). Backup dan restore diuji pada MySQL 8.4.10 sementara. Verifikasi CI dan pengujian pada server target masih diperlukan sebelum rilis.

  Tindak lanjut audit alur/UI 2026-10-24: metadata nilai dipertahankan dan dapat diedit di kedua mode penilaian; filter Rekap/Laporan konsisten; matriks mengikuti filter; antrean draft dan perpindahan kelas tahan respons terlambat; Setoran ponsel memprioritaskan editor; pengosongan nilai meminta konfirmasi. Bukti lokal: `composer check` (251 tes, Larastan 0), tes JS Node, build Vite, dan verifikasi browser 360×800. Verifikasi CI/server target tetap diperlukan sebelum T-905 dicentang.

  Implementasi `docs/mockups/modern-ui.html` 2026-10-24: token warna/kartu dan kerangka navigasi diterapkan; Beranda memakai hero penilaian, empat statistik, prioritas kelas, serta aktivitas dengan data aplikasi. Checklist penyiapan tetap tersedia. Bukti lokal: `composer check` (253 tes, Larastan 0), build Vite, tes JS Node, dan browser desktop/ponsel. Verifikasi CI/server target tetap diperlukan sebelum T-905 dicentang.

---

## Parkiran (P2, jangan dikerjakan tanpa persetujuan)
Tema gelap · PWA terpasang · mode nilai langsung 0–100 per materi · pemisahan data per guru · pencarian global · notifikasi.
