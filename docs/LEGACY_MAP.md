# LEGACY MAP — Peta dari Aplikasi Lama ke Laravel

Aplikasi lama: `/home/ilhamrizqiawan/Projects/penilaian-pib` (Next.js 15, **hanya-baca**). Kode lama dipadatkan satu baris-per-fungsi — **baca untuk perilaku, jangan tiru gaya**.
Tes lama (`lib/*.test.ts`) adalah spesifikasi tambahan: porting setiap kasus ke Pest.

## Domain (`lib/`)

| Lama | Baru | Status | Catatan perilaku |
|---|---|---|---|
| `scoring.ts` (`calculateScore`, `weightedAverage`) | `App\Domain\Scoring\ScoreCalculator` | ✅ selesai + tes | Bulatkan 1 desimal half-up |
| `domain/progress.ts` | `App\Domain\Recap\Progress` | ✅ | |
| `domain/recap.ts` (`recapScores`, `subchapterComplete`) | `Progress` + `RecapService` | ◐ sebagian | `recapScores` → di `RecapService` |
| `class-progress.ts` (`getClassProgress`, `studentPosition`, `isSameLocalDay`) | `ClassProgressService` + `Domain\Scoring\StudentPosition` ✅ | ◐ | Baris nilai kosong **ikut dikirim** (membawa versi) |
| `assessment-workspace.ts` (parse/step/draft) | `resources/js/draft-store.js` + `ScoreCalculator::parseMistakes` ✅ | ◐ | `stepMistakes` ±1 dibatasi 0..90; draft di localStorage `pib-score-edit:*` |
| `score-client.ts`, `score-version.ts` | `ScoreService` + `draft-store.js` | ☐ | versi: `updated_at` → `version` integer |
| `curriculum.ts` (`curriculumContext`, `previewCopy`, `copyPeriod`, `editCurriculum`, `moveCurriculum`, `CurriculumError`) | `CurriculumService` + `CurriculumException` | ☐ | pesan error & status HTTP lihat tes `curriculum.test.ts` |
| `curriculum-schema.ts` | Form Request kurikulum | ☐ | bobot 0,1–100 |
| `recap-rows.ts`, `recap-query.ts`, `recap-data.ts`, `filters.ts` | `RecapService`, `RecapFilter` | ☐ | filter: tahun ajaran, kelas, siswa, bab, subbab, materi; status: `Materi belum tersedia/Belum dinilai/Sebagian/Lengkap`; persen maks 99 bila belum lengkap |
| `report-query.ts`, `report-groups.ts` | `Reports\ReportQuery` + `Reports\ReportGrouper` | ☐ | urut bab → kelas → siswa; `localeCompare("id")` → `Str`/collator id; status baris: `Dinilai/Sebagian dinilai/Belum dinilai/Materi belum tersedia` |
| `dashboard.ts`, `onboarding.ts` | `DashboardService`, `SetupChecklist` | ☐ | langkah: sekolah, tahun ajaran, kelas, siswa, materi, nilai |
| `student-import.ts` | `StudentImporter` | ☐ | maks 1000 baris; gagal: NIS duplikat / kelas tak valid |
| `restore.ts`, `auto-backup.ts`, `data-dir.ts` | `BackupService` + spatie | ☐ | restore: validasi → snapshot → tulis; **desain baru** (PRD §9) |
| `db.ts` (snapshot sebelum migrasi), `migrations.ts` | migration Laravel ✅ | ✅ | |
| `auth.ts`, `session.ts`, `session-edge.ts`, `api.ts` (`requireUser` dst.) | Laravel Auth + middleware | ☐ | **perilaku lama tidak dipertahankan** (tanpa login) |
| `request-origin.ts`, `middleware.ts` | CSRF Laravel | — | tak perlu |
| `env.ts` | `config/*.php` + `.env` | ✅ | |

## API → rute baru

| Lama (`app/api/…`) | Baru |
|---|---|
| `academic-years` | Livewire `Sekolah` + `AcademicYearService` |
| `classes`, `master/[entity]/[id]` (classes) | Livewire `Kelas` + service |
| `students`, `students/import`, `students/template` | Livewire `Siswa`, `StudentImporter`, unduh template |
| `templates`, `templates/[id]/duplicate`, `curriculum`, `chapters`, `subchapters`, `assessments`, `master/*` | Livewire `Materi` + `CurriculumService` |
| `scores` (POST/GET), `sync` | `POST /penilaian/sync` (satu endpoint idempoten) — ARCHITECTURE.md |
| `class-progress` | `GET /penilaian/kelas/{class}/progress` |
| `recap` | Livewire `Rekap` + `RecapService` |
| `export` | `GET /laporan/excel` |
| `pdf`, `pdf/student` | `GET /laporan/pdf`, `GET /laporan/pdf/siswa/{student}` |
| `backup`, `backup/status`, `restore`, `restore/sqlite` | `BackupService` + halaman Laporan/Admin |
| `settings` | Livewire `Sekolah` + `SettingsRepository` |
| `auth/*` | Laravel auth + Livewire `Akun` |

## Halaman → Livewire/Blade

| Lama | Baru |
|---|---|
| `app/dashboard` | `Livewire\Dashboard` |
| `app/penilaian`, `app/individual-assessment` (Setoran), `app/assessment` | `/penilaian`, `/penilaian/setoran`, `/penilaian/materi` (Blade+Alpine) |
| `app/recap` (+`matrix.tsx`) | `Livewire\Rekap` |
| `app/master-data`, `app/master-data/curriculum/*` | `Livewire\Sekolah`, `Livewire\Materi\*` |
| `app/classes`, `app/students` | `Livewire\Kelas`, `Livewire\Siswa` |
| `app/reports` | `Livewire\Laporan` |
| `app/account`, `app/login`, `app/setup` | `Livewire\Akun`, `login`, — (setup dibuang) |
| `app/sidebar.tsx`, `app-layout.tsx`, `ui.tsx`, `navigation-progress.tsx`, `draft-indicator.tsx`, `backup-status.tsx` | `layouts/app.blade.php`, komponen Blade, Alpine |
| `app/*.css` | `resources/css/app.css` (token DESIGN.md) |

## Dokumen lama berguna
`docs/aturan-penilaian.md` (sudah diserap PRD §4) · `docs/individual-assessment-verification.md` (perilaku Setoran) · `docs/rancangan-aplikasi-pib.md` (kontrak laporan/filter) ·
`docs/audit-*.md` (temuan UX yang sudah diperbaiki — baca sebelum merancang ulang layar terkait).
