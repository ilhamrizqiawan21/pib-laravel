# SCHEMA — Skema Database

Sumber kebenaran skema = **migration** di `database/migrations/` (sudah ada & diuji di `tests/Feature/Schema`). Dokumen ini
menjelaskan maksud, aturan, dan pemetaan dari aplikasi lama. Diagram: [ERD.md](ERD.md).

## Konvensi

- Nama tabel jamak snake_case; PK `id` (bigint). Pengecualian: `settings.key` (string), `sync_operations.id` (string dari klien).
- **Boolean** = `boolean` (SQLite 0/1, MySQL tinyint). **Enum** disimpan sebagai `string` + PHP backed enum (`app/Enums`); **bukan** kolom `enum` DB (portabilitas, mudah diubah).
- **Portabel SQLite + MySQL**: hanya Schema Builder. Dilarang `DB::statement` khusus-dialek, `strftime`, `INSERT OR IGNORE`, `GROUP_CONCAT`, JSON path khusus dialek. Bila perlu agregasi, pakai Query Builder.
- FK selalu `constrained()` dengan perilaku hapus eksplisit (lihat ERD). Index untuk setiap FK yang dipakai filter.
- `timestamps()` pada tabel yang berubah; `audit_logs`/`sync_operations` hanya `created_at`.
- Waktu disimpan dalam zona `APP_TIMEZONE` (default `Asia/Jakarta`); jangan ubah setelah ada data.
- Migration **tidak boleh diedit** setelah dipakai di produksi — buat migration baru. (Sebelum rilis 1.0, fondasi ini boleh disesuaikan, dengan persetujuan pemilik.)
- Setiap perubahan skema ⇒ perbarui dokumen ini + ERD + tes skema dalam commit yang sama.

## Tabel

### users
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string(255) | |
| email | string UNIQUE | |
| email_verified_at | timestamp null | tak dipakai (tanpa verifikasi email) |
| password | string | hash; cast `hashed` |
| role | string(20) default `teacher` | `App\Enums\Role`: admin, teacher |
| is_active | bool default true | akun nonaktif tak bisa login |
| remember_token, timestamps | | |

Juga: `password_reset_tokens`, `sessions` (bawaan; sesi pakai driver `database`).

### academic_years
`name` string(20) · `semester` string(10) (`Ganjil|Genap`) · `is_active` bool default false · timestamps · **UNIQUE(name, semester)**.
Maksimal satu `is_active=true` — dijaga `AcademicYearService` (transaksi: nonaktifkan yang lain), bukan unique index (portabilitas).

### school_classes  (model `SchoolClass`)
`academic_year_id` FK RESTRICT · `name` string(50) · `grade_level` string(20) default '' · `homeroom_teacher` string(100) default '' ·
`is_active` bool default true · **UNIQUE(academic_year_id, name)**.

### students
`school_class_id` FK RESTRICT · `nis` string(30) **UNIQUE nullable** · `nisn` string(30) default '' · `name` string(120) ·
`gender` string(1) null (`L|P`) · `is_active` bool default true · INDEX(school_class_id, is_active).
Catatan: UNIQUE pada kolom nullable mengizinkan banyak `NULL` di SQLite dan MySQL. Normalisasi `''` → `NULL` di Form Request.

### curriculum_templates
`academic_year_id` FK RESTRICT · `name` string(100) · `is_active` · **UNIQUE(academic_year_id, name)**.

### chapters
`curriculum_template_id` FK CASCADE · `title` string(150) · `display_order` uint default 0 · **UNIQUE(curriculum_template_id, title)**.

### subchapters
`chapter_id` FK CASCADE · `title` string(150) · `display_order` · **UNIQUE(chapter_id, title)**.

### assessments  (materi)
`subchapter_id` FK CASCADE · `title` string(150) · `description` text null · `weight` decimal(6,2) default 1 (valid 0,1–100) ·
`display_order` · `is_active` · INDEX(subchapter_id, is_active).

Urutan kurikulum **baku** (dipakai di mana-mana): `chapters.display_order, chapters.id` → `subchapters.display_order, subchapters.id` →
`assessments.display_order, assessments.id`. Jangan menyusun urutan sendiri; pakai scope/relasi model.

### scores
| Kolom | Tipe | Catatan |
|---|---|---|
| student_id | FK CASCADE | |
| assessment_id | FK **RESTRICT** | |
| mistakes | tinyint unsigned null | 0–90; `NULL` = belum dinilai |
| score | tinyint unsigned null | `90 − mistakes`; **dihitung server**, `NULL` bila mistakes NULL |
| assessed_at | timestamp null | |
| assessor_id | FK users SET NULL | |
| initials | string(30) default '' | paraf |
| note | string(500) default '' | keterangan |
| version | uint default 1 | naik 1 tiap perubahan (termasuk pengosongan) |

**UNIQUE(student_id, assessment_id)** · INDEX(assessment_id, student_id).
Invarian (dijaga `ScoreService` + tes): `score === ScoreCalculator::score(mistakes)`; baris yang dikosongkan **tetap ada**.
CHECK constraint DB sengaja tidak dipakai (SQLite/MySQL < 8.0.16 berbeda perilaku); invarian dijaga di aplikasi + tes.

### settings
`key` string(100) PK · `value` text. Kunci baku: `school_name`, `school_address`, `teacher_name`. Akses lewat `SettingsRepository` (cache).

### audit_logs  (append-only)
`user_id` FK SET NULL · `entity` string(50) · `entity_id` string(100) · `action` string(30) · `summary` string(255) · `meta` json null ·
`created_at` · INDEX(created_at), INDEX(entity, entity_id). Tidak ada update/delete dari aplikasi; hanya `AuditLogger::record()`.
Nilai `entity`: `score, student, class, academic_year, template, chapter, subchapter, assessment, curriculum, settings, user, backup, restore, import, auth`.
Nilai `action`: `CREATE, UPDATE, DELETE, DEACTIVATE, UPSERT, CLEAR, COPY, MOVE, IMPORT, DOWNLOAD, RESTORE, LOGIN_FAILED, FAILED`.

### sync_operations
`id` string(100) PK (UUID/ULID dari klien) · `device_id` string(100) · `operation` string(30) (`UPSERT_SCORE`) · `payload` json ·
`status` (`PENDING|SYNCED|FAILED`) · `error` null · `created_at` · `synced_at` null · INDEX(status).
Dipakai untuk **idempotensi**: operasi dengan `id` yang sama dan `SYNCED` mengembalikan hasil sebelumnya tanpa menulis ulang.

## Peta dari skema lama (SQLite aplikasi Next.js)

| Lama | Baru | Transformasi |
|---|---|---|
| `users` | `users` | `password_hash`→`password` (hash bcrypt lama kompatibel; `LOCAL_ONLY` ⇒ akun dinonaktifkan & wajib reset); `TEACHER/ADMIN`→`teacher/admin`; tambah `is_active` |
| `academic_years` | `academic_years` | `is_active` 0/1→bool |
| `classes` | `school_classes` | `academic_year_id`, nama sama |
| `students` | `students` | `class_id`→`school_class_id`; `nis ''`→NULL; `gender ''`→NULL |
| `curriculum_templates/chapters/subchapters` | sama | `template_id`→`curriculum_template_id` |
| `assessments` | `assessments` | `weight REAL`→decimal(6,2); `description ''`→NULL |
| `scores` | `scores` | `updated_at` ISO → `updated_at` timestamp; `version` = 1; `assessed_at` ISO(UTC)→zona app |
| `settings(schoolName…)` | `settings(school_name…)` | ganti nama kunci |
| `audit_logs` | `audit_logs` | `meta` NULL |
| `sync_operations` | — | **tidak diimpor** (transien) |
| `individual_test_sessions`, `…_items` | — | **tidak diimpor** (fitur lama sudah dihapus) |
| `schema_migrations` | `migrations` | tak relevan |

ID **dipertahankan** saat impor (memudahkan verifikasi paritas & FK). Setelah impor, sekuens auto-increment disetel ke `MAX(id)+1` (MySQL: `ALTER TABLE … AUTO_INCREMENT`; SQLite otomatis).

### Spesifikasi `php artisan pib:import-legacy {path.sqlite} [--dry-run] [--force]`
1. Tolak bila DB tujuan tidak kosong (kecuali `--force`, yang membuat snapshot dulu).
2. Buka file lama **read-only** (koneksi `legacy` sementara). Satu transaksi besar; urutan induk→anak: users, academic_years, classes, students,
   curriculum_templates, chapters, subchapters, assessments, scores, settings, audit_logs.
3. Terapkan transformasi di atas; baris yang melanggar invarian (mis. `score ≠ 90−mistakes`) **dilaporkan**, bukan diperbaiki diam-diam
   (kecuali `score` diturunkan ulang dari `mistakes` — dicatat di laporan).
4. Cetak laporan: jumlah baris per tabel (lama vs baru), anomali, dan **checksum paritas**: untuk tiap siswa, `(jumlah nilai, total, rata-rata berbobot)`
   dihitung dari DB lama oleh SQL ekuivalen vs hasil `RecapService` baru; harus identik.
5. `--dry-run`: jalankan semua di dalam transaksi lalu rollback.
6. Tes: `tests/Feature/Import/LegacyImportTest` memakai fixture SQLite kecil (`tests/Fixtures/legacy-sample.sqlite`, dibuat dari `scripts/seed-dummy.ts` aplikasi lama).

## Migrasi SQLite → MySQL (opsional, kemudian)
Aplikasi ditulis portabel sehingga cukup: set `DB_CONNECTION=mysql`, `php artisan migrate`, lalu jalankan `pib:import-legacy` dari berkas SQLite
Laravel (opsi `--from-laravel-sqlite`) atau `php artisan db:export/import` sederhana (dibuat bila diperlukan). Collation MySQL: `utf8mb4_unicode_ci`
(urut nama siswa bahasa Indonesia; bandingkan dengan `localeCompare("id")` aplikasi lama).
