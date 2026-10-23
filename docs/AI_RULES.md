# AI RULES — Aturan untuk Agen Pengeksekusi Kode

Berlaku untuk **Gemini** (eksekutor utama) dan agen AI lain yang menyentuh repo ini. Aturan ini mengikat. Bila ragu, **berhenti dan tanya** (lihat §9).

## 1. Sumber kebenaran & urutan baca

Sebelum mengerjakan tugas apa pun, baca berurutan:
1. `GEMINI.md` (ringkasan operasional)
2. `docs/PRD.md` (apa & aturan nilai) — **menang** bila bertentangan dengan kode
3. `docs/SCHEMA.md` + `docs/ERD.md` (data)
4. `docs/ARCHITECTURE.md` (di mana kode ditaruh, endpoint, keamanan)
5. `docs/DESIGN.md` (UI) bila tugas menyentuh tampilan
6. `docs/DECISIONS.md` (jangan membalik ADR "Diterima")
7. Tugas di `docs/TODO.md` + butir terkait di `docs/LEGACY_MAP.md`

Aplikasi lama (Next.js) ada di `/home/ilhamrizqiawan/Projects/penilaian-pib` — **hanya-baca, rujukan perilaku**. Jangan mengubahnya, jangan menyalin gaya kodenya
(file lama sengaja dipadatkan; di sini kode harus terbaca). Porting = pahami perilaku → tulis ulang idiomatik Laravel → buktikan dengan tes.

## 2. Alur kerja satu tugas (wajib)

1. Ambil **satu** tugas dari `docs/TODO.md` (urut per fase; jangan loncat fase; jangan mengerjakan dua tugas sekaligus).
2. Tulis **tes dulu** dari kontrak PRD/TODO (harus merah karena alasan yang benar).
3. Implementasikan seminimal mungkin sampai hijau.
4. Jalankan `composer check` (Pint + Larastan lvl 6 + Pest) — **harus hijau**. Jangan menyatakan selesai bila merah.
5. Untuk perubahan UI: jalankan aplikasi, periksa di browser (keyboard, ponsel 360px, keadaan kosong/galat), dan sebutkan apa yang diverifikasi.
6. Perbarui dokumen yang terdampak (SCHEMA/ERD/ARCHITECTURE/DESIGN) **di commit yang sama**.
7. Centang tugas di TODO.md **hanya** bila semua kriteria penerimaan terpenuhi dan terbukti; tulis bukti singkat (nama tes / perintah).
8. Commit kecil, pesan Conventional Commits berbahasa Inggris atau Indonesia konsisten: `feat(scoring): …`, `fix(recap): …`, `test: …`, `docs: …`.

**Definition of Done** (semua harus benar): perilaku sesuai PRD · tes baru ada & lulus · `composer check` hijau · tak ada N+1 (strict mode) ·
auth/otorisasi/validasi/audit sesuai §Keamanan · UI punya 4 keadaan & lolos a11y dasar · dokumen sinkron · tidak ada TODO/dd()/dump()/komentar mati.

## 3. Larangan keras

- ❌ Mengubah **Aturan Nilai** (PRD §4), skema fondasi, ADR "Diterima", atau menghapus/melonggarkan tes yang ada agar hijau.
- ❌ Mengedit migration yang sudah ada. Perubahan skema = **migration baru** + persetujuan pemilik (kecuali TODO eksplisit menugaskannya), plus update SCHEMA/ERD/tes.
- ❌ Menulis nilai (`mistakes`/`score`) langsung dari komponen/controller. Hanya lewat `ScoreService`; `score` selalu dari `ScoreCalculator`.
- ❌ SQL khusus dialek (`strftime`, `INSERT OR IGNORE`, `GROUP_CONCAT`, `PRAGMA`, JSON path dialek) dan `DB::raw` dengan input pengguna. Pakai Query Builder/Eloquent.
- ❌ Query di dalam loop / lazy loading. Eager load (`with`) atau satu query terjoin. Jangan mematikan `preventLazyLoading`/`shouldBeStrict`.
- ❌ Menghapus data pengguna diam-diam: hapus permanen hanya dengan konfirmasi UI + audit; selain itu nonaktifkan.
- ❌ Menambah dependensi Composer/NPM tanpa alasan tertulis di commit & persetujuan (cek dulu bisa dilakukan dengan yang ada). Dilarang CDN pihak ketiga.
- ❌ Menyimpan secret di repo, mencetak `.env`, menonaktifkan CSRF/auth/rate-limit, `APP_DEBUG=true` di produksi, `$guarded=[]`, `Model::unguard()`.
- ❌ Perintah destruktif tanpa izin: `migrate:fresh` pada DB selain testing/dev lokal, `rm -rf` di luar `storage/framework`/`node_modules`/`vendor`, `git reset --hard`, `git push --force`, mengubah riwayat git.
- ❌ Menambah fitur di luar tugas ("sekalian"), refactor besar tak diminta, atau mengganti gaya kode berkas lain.
- ❌ Mengarang perilaku. Bila PRD diam: ikuti aplikasi lama (LEGACY_MAP) atau tanya (§9).

## 4. Standar kode

- PHP 8.5, tipe eksplisit pada parameter/return, `final` untuk kelas domain/service yang tak diwarisi, `readonly` untuk value object.
- Ikuti struktur ARCHITECTURE.md. Satu tanggung jawab per kelas. Controller/Livewire tipis; logika di Service/Domain.
- Nama: kelas `PascalCase`, metode/variabel `camelCase`, kolom/tabel `snake_case`, route name `kebab.dot` (`penilaian.setoran`). Istilah domain Indonesia **hanya di UI & dokumen**; kode memakai Inggris
  (`Score`, `Assessment`, `Chapter`) sesuai model yang ada.
- Validasi: Form Request (HTTP) atau atribut/`rules()` Livewire; pesan Indonesia di `lang/id/validation.php`. Aturan angka kesalahan: `integer|between:0,90`.
- Transaksi: `DB::transaction()` untuk tulisan multi-tabel; audit di dalam transaksi yang sama.
- Exceptions domain khusus (`CurriculumException`, `ConflictException`) dengan pesan Indonesia siap tampil; jangan menelan exception (`catch {}` kosong dilarang).
- Komentar: jelaskan **mengapa**, bukan apa. Docblock generik untuk relasi/koleksi agar Larastan lulus. Tanpa `@phpstan-ignore` dan tanpa baseline.
- Format: `composer fix` (Pint, preset Laravel). Jangan commit kode tak terformat.
- Blade/Livewire: komponen kecil dapat dipakai ulang di `resources/views/components`; tidak ada logika bisnis di Blade; escape default `{{ }}`, `{!! !!}` dilarang kecuali konten tepercaya yang dijelaskan.
- JS: Alpine + modul ES kecil di `resources/js`; tanpa jQuery; tanpa framework tambahan. Semua fetch memakai helper tunggal yang menambahkan header CSRF & menangani 401/419/409/422.
- Tes: Pest. Nama tes berupa kalimat perilaku (Indonesia). Satu hal per tes. Factory untuk data; hindari seed besar. Tes tidak bergantung urutan.

## 5. Keamanan (checklist — diperiksa tiap tugas yang menyentuh HTTP/data)

- [ ] Rute berada di grup `auth` (+ `EnsureActiveUser`); rute admin diberi otorisasi; ada tes "tamu → redirect login/401" dan "guru → 403 untuk rute admin".
- [ ] Semua input divalidasi; hanya `validated()` yang dipakai; tidak ada mass assignment dari `request()->all()`.
- [ ] Tidak ada IDOR: ID di path/body diverifikasi ada & sah di konteks (siswa/materi ada → 404 bila tidak).
- [ ] Metode tulis bukan GET; CSRF aktif; fetch memakai header token.
- [ ] Tidak ada query berbasis string input; binding parameter selalu.
- [ ] Output di-escape; nama berkas unduhan disanitasi; tidak membocorkan stack trace/`APP_DEBUG`.
- [ ] Unggahan: ukuran, MIME & ekstensi dibatasi; dibaca sebagai data, tak pernah di-`include`.
- [ ] Rate limit pada login & endpoint tulis berat (`/penilaian/sync`: 120/menit/pengguna).
- [ ] Perubahan penting → `AuditLogger::record(...)`; audit tak berisi password/token.
- [ ] Log tidak memuat data sensitif.

## 6. Performa & portabilitas

- Setiap halaman daftar: paginasi atau batas; indeks mendukung filter. Lihat anggaran performa di PRD §7.
- Tes harus lulus di **SQLite**; hindari asumsi yang gagal di MySQL (case-sensitivity, `LIKE`, urutan tanpa ORDER BY, pembagian integer).
  Urutan **selalu eksplisit** (`orderBy`), terutama kurikulum (`display_order, id`) dan nama siswa (`name`).

## 7. UI/UX (rujuk DESIGN.md)

Token & komponen dari DESIGN.md; jangan menciptakan warna/gaya baru. Empat keadaan per layar. Bahasa Indonesia baku sehari-hari. Keyboard-first di layar penilaian.
Jangan menaruh teks yang tak diminta (mis. penjelasan fitur berlebihan) di UI.

## 8. Git

- Branch kerja: `feat/<id-tugas>-<slug>` dari `main`. Satu tugas = satu branch/PR kecil (≤ ~400 baris diff bila memungkinkan).
- Jangan commit: `.env`, `database/*.sqlite`, `storage/*`, `vendor`, `node_modules`, `public/build`.
- Jangan `--no-verify`, jangan force-push, jangan rebase cabang bersama. Jangan `git add -A` buta — tambahkan berkas yang disengaja.

## 9. Protokol bertanya / eskalasi

Berhenti dan minta keputusan (tulis di `docs/DECISIONS.md` bagian *Usulan* + sampaikan ke pemilik) bila:
- PRD/ADR/TODO ambigu atau saling bertentangan; atau butuh mengubah skema/aturan nilai/dependensi;
- tes yang ada tampak salah (jangan ubah — laporkan);
- ada risiko kehilangan data atau perintah destruktif;
- hasil aplikasi lama dan PRD berbeda (laporkan kedua perilaku; jangan pilih sendiri).

Bentuk pertanyaan: konteks (tugas), temuan (kutip berkas:baris), 2–3 opsi + rekomendasi, dampak. Satu pertanyaan jelas lebih baik daripada tebakan.

## 10. Format laporan akhir tiap tugas

```
Tugas: T-xxx <judul>
Perubahan: <berkas penting, 3–8 butir>
Bukti: composer check ✔ (Pest N tes, Larastan 0) · <tes kunci> · <verifikasi manual UI bila ada>
Dokumen diperbarui: <daftar>
Keputusan/asumsi: <bila ada> · Risiko/Catatan: <bila ada>
```
Jujur: bila ada yang gagal atau dilewati, katakan apa adanya.
