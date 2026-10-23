# PRD — PIB Penilaian (Laravel)

> Status: **Draft v1 — fondasi**. Sumber kebenaran perilaku produk. Bila kode dan dokumen ini berbeda, dokumen ini menang,
> kecuali ada entri di [DECISIONS.md](DECISIONS.md) yang menggantikannya.

## 1. Latar belakang

PIB Penilaian adalah aplikasi untuk guru mencatat **Penilaian Praktik Ibadah (PIB)** siswa: hafalan dan praktik per materi
(mis. wudhu, shalat), dengan nilai berdasarkan jumlah kesalahan. Versi pertama (Next.js + SQLite, folder
`~/Projects/penilaian-pib`) berjalan lokal untuk satu guru. Versi ini **menulis ulang aplikasi tersebut di Laravel** agar:

1. aman dan fleksibel saat **di-deploy ke server** (auth sungguhan, CSRF, validasi, rate limit);
2. ringan di server (PHP-FPM, bukan proses Node yang selalu hidup);
3. mudah dirawat oleh pemilik (keahlian Laravel);
4. siap multi-guru bila diperlukan.

Perilaku bisnis **tidak berubah**. Ini porting, bukan redesign produk (kecuali yang tercantum di §9).

## 2. Tujuan & non-tujuan

**Tujuan**
- Paritas fungsi dengan aplikasi lama (§5), kecuali yang dinyatakan dibuang.
- Hasil hitung nilai/rekap **identik** dengan aplikasi lama untuk data yang sama (diverifikasi, lihat [TODO.md](TODO.md) fase 8).
- Dapat dipasang di server sendiri dengan satu prosedur (docs/DEPLOYMENT.md).
- Input nilai tetap secepat sekarang di kelas (keyboard-first, tahan jaringan putus).

**Non-tujuan (versi ini)**
- Aplikasi desktop (Electron) dan skrip Windows `.bat` — **dibuang** (ADR-005).
- Sinkronisasi cloud multi-perangkat penuh / mode offline penuh. Hanya **antrean draft** seperti aplikasi lama.
- Integrasi rapor/SIAKAD, mode nilai langsung 0–100, notifikasi, multi-sekolah (multi-tenant).

## 3. Pengguna & peran

| Peran | Siapa | Bisa |
|---|---|---|
| `teacher` (Guru) | Guru PIB | Semua kerja harian: data siswa/kelas/materi, input nilai, rekap, laporan, backup unduh |
| `admin` | Pengelola sistem | Semua hak guru + kelola akun pengguna, restore backup, lihat audit log penuh |

Asumsi (ADR-004): **seluruh guru yang login melihat data yang sama** (satu sekolah, satu set data). Pemisahan data per guru
bukan bagian versi ini. Akun nonaktif (`is_active=false`) tidak bisa login.

## 4. Aturan Nilai (inti — tidak boleh dilanggar)

```
nilai = 90 − jumlah_kesalahan
```

- Guru memasukkan **jumlah kesalahan**: bilangan bulat **0–90**. Nilai dihitung **di server**, tidak pernah dipercaya dari klien.
- Nilai berada pada 0–90. **Nilai kosong (`NULL`) = "Belum dinilai"**, bukan 0. Nilai 0 hanya sah dari 90 kesalahan.
- Menghapus nilai = mengosongkan (`mistakes`, `score`, `assessed_at` → `NULL`) **tanpa menghapus baris**; `version` tetap naik.
- Satu nilai per (siswa, materi). Unik di database.
- Total/rata-rata hanya memakai nilai yang terisi.
- **Rata-rata berbobot** = `Σ(nilai × bobot) / Σ(bobot)` atas materi terisi, dibulatkan 1 desimal (half-up). Bobot 0,1–100, default 1.
- **Progres**: `persen = round(terisi/seharusnya×100)`, tetapi **100% hanya bila lengkap** (selain itu maks 99%).
- **Status kelengkapan**: `Materi belum tersedia` (seharusnya = 0) → `Belum dinilai` (terisi = 0) → `Sebagian` → `Lengkap`.
- **Subbab selesai** bila *semua siswa aktif dalam cakupan* punya nilai untuk *semua materi aktif* subbab itu.
- Parent yang sudah punya nilai **tidak boleh dihapus permanen** (nonaktifkan). Dijaga di DB (FK `RESTRICT`) dan service.
- Implementasi acuan: `App\Domain\Scoring\ScoreCalculator`, `App\Domain\Recap\Progress` (+ tes di `tests/Unit/Domain`).

## 5. Persyaratan fungsional

Prioritas: **P0** wajib rilis 1.0, **P1** sebaiknya, **P2** nanti. ID dipakai di [TODO.md](TODO.md).

### 5.1 Autentikasi & akun
- **FR-A1 (P0)** Login email+password, sesi aman, logout. Semua halaman & endpoint selain login butuh login.
- **FR-A2 (P0)** Rate limit login: 5 percobaan/menit per email+IP (`config('pib.login_max_attempts')`).
- **FR-A3 (P0)** Ubah password sendiri (butuh password lama). Password min. 10 karakter.
- **FR-A4 (P0)** Admin pertama dibuat lewat `php artisan pib:create-admin` / seeder env — tidak ada halaman "setup" publik.
- **FR-A5 (P1)** Admin mengelola akun (buat, nonaktifkan, reset password, ubah peran).

### 5.2 Data induk
- **FR-M1 (P0)** Tahun ajaran + semester (Ganjil/Genap); maksimal **satu aktif**. Nama tahun ajaran format `YYYY/YYYY`.
- **FR-M2 (P0)** Kelas per tahun ajaran (nama unik per tahun ajaran, tingkat, wali kelas). Nonaktifkan, bukan hapus, bila ada siswa.
- **FR-M3 (P0)** Siswa: nama, NIS (unik, opsional), NISN, jenis kelamin (L/P/kosong), kelas. Tambah, ubah, pindah kelas, nonaktifkan,
  hapus permanen (hanya dengan konfirmasi eksplisit; ikut menghapus nilainya dan dicatat audit).
- **FR-M4 (P0)** Impor siswa massal (Excel/CSV, maks 1000 baris) dengan laporan baris gagal (NIS duplikat/kelas tidak valid) + unduh template.
- **FR-M5 (P0)** Kurikulum: Template per tahun ajaran → Bab → Subbab → Materi (judul, deskripsi, **bobot**, urutan, aktif). Tambah, ubah,
  pindah urutan, nonaktifkan. Hapus permanen hanya bila tanpa nilai.
- **FR-M6 (P0)** **Salin struktur** kurikulum ke tahun ajaran/semester baru (dengan pratinjau jumlah bab/subbab/materi).
- **FR-M7 (P0)** Identitas sekolah: nama, alamat, nama guru (dipakai di laporan & sapaan).

### 5.3 Penilaian
- **FR-S1 (P0) Setoran / per siswa** — pilih kelas (diingat), pilih siswa (klik atau `/` cari lalu Enter), seluruh materi kelas tampil per subbab,
  kursor langsung di **materi berikutnya**; ketik kesalahan + Enter → simpan → kursor pindah. Tombol **90** = 0 kesalahan, simpan seketika.
  Materi yang terlewat sebelum posisi terjauh ditandai **Terlewat**. Siswa dinilai hari ini tampil teratas; tiap siswa menampilkan posisi (`11/21`).
- **FR-S2 (P0) Per materi** — pilih materi, nilai seluruh siswa kelas satu per satu (grid), tombol langkah ±1 kesalahan.
- **FR-S3 (P0)** Tiap nilai bisa menyimpan paraf (maks 30) dan catatan (maks 500); tanggal nilai otomatis (bisa diubah).
- **FR-S4 (P0) Ketahanan jaringan** — draft nilai disimpan di perangkat (localStorage) sebelum dikirim; status `Menunggu koneksi`,
  `Konflik`, `Gagal`; kirim ulang idempoten (`sync_operations.id`). Lihat §7 Konkurensi.
- **FR-S5 (P0)** Mode terakhir (per siswa/per materi) dan kelas terakhir diingat per perangkat.

### 5.4 Rekap & laporan
- **FR-R1 (P0)** Rekap dengan filter bersama: tahun ajaran/semester, kelas, siswa, bab, subbab, materi. Ringkasan per siswa (terisi, total,
  rata-rata berbobot, status, progres) dan detail per siswa.
- **FR-R2 (P0)** Matriks rekap kelas (siswa × materi/subbab).
- **FR-L1 (P0)** **Excel**: lembar *Nilai Detail*, *Rekap Siswa*, *Rekap Kelas* (bobot, nilai, total, rata-rata, status, tanggal, penilai, paraf, keterangan);
  header berwarna, nilai tersusun per bab → kelas.
- **FR-L2 (P0)** **PDF A4**: identitas sekolah/guru, parameter filter, daftar materi & nilai, total, rata-rata, progres, paraf, keterangan, header
  berulang, footer nomor halaman, page break rapi; PDF per siswa.
- **FR-L3 (P0)** Filter laporan = filter rekap (satu kontrak yang sama; angka di layar = angka di berkas).

### 5.5 Backup & pemulihan
- **FR-B1 (P0)** Backup otomatis terjadwal (default tiap 2 jam bila ada perubahan, simpan 30) ke disk yang dapat dikonfigurasi.
- **FR-B2 (P0)** Unduh backup manual (file DB/arsip). Status backup terakhir tampil di UI.
- **FR-B3 (P0)** Restore dari berkas backup: validasi, **snapshot otomatis sebelum restore**, audit, mode pemeliharaan selama proses. Hanya `admin`.
- **FR-B4 (P1)** Snapshot otomatis sebelum migrasi produksi.

### 5.6 Dashboard & audit
- **FR-D1 (P0)** Beranda: ringkasan periode aktif (kelas, siswa, progres), kelas prioritas (progres terendah), lanjutkan penilaian terakhir,
  checklist penyiapan awal (sekolah, tahun ajaran, kelas, siswa, materi, nilai), 5 aktivitas terbaru.
- **FR-D2 (P0)** Audit log append-only untuk: nilai, master data, impor, backup/restore, login gagal beruntun, perubahan akun.

## 6. Alur kunci

1. **Persiapan periode**: buat tahun ajaran → salin kurikulum → buat kelas → impor siswa. (Checklist di Beranda menuntun urutan ini.)
2. **Hari penilaian (Setoran)**: pilih kelas → siswa maju → ketik kesalahan + Enter berulang → siswa berikutnya.
3. **Akhir periode**: Rekap → cek yang belum lengkap → unduh Excel/PDF → backup.

## 7. Persyaratan non-fungsional

- **Keamanan**: semua rute di balik `auth`; CSRF; validasi Form Request; otorisasi via Policy/Gate; password bcrypt/argon (default Laravel);
  rate limit login & endpoint tulis; header keamanan; tidak ada secret di repo; unggahan restore divalidasi ketat. Detail: [ARCHITECTURE.md](ARCHITECTURE.md) §Keamanan.
- **Integritas**: transaksi untuk tulisan multi-tabel; FK + unique; audit log dalam transaksi yang sama.
- **Konkurensi**: nilai memakai *optimistic concurrency* via kolom `version`. Klien mengirim `base_version` (atau `null` bila belum ada baris);
  server menolak `409` bila berbeda. Operasi draft idempoten lewat `sync_operations.id`.
- **Performa**: halaman penilaian memuat satu payload per kelas (tanpa N+1); rekap kelas 40 siswa × 100 materi < 500 ms di server kecil;
  PDF/Excel kelas penuh < 10 dtk. Tidak ada query dalam loop (dijaga tes `preventLazyLoading` di non-produksi).
- **Aksesibilitas & responsif**: kontras WCAG AA, fokus terlihat, bisa dipakai dengan keyboard, nyaman di ponsel (lihat [DESIGN.md](DESIGN.md)).
- **Bahasa**: seluruh UI dan pesan error berbahasa Indonesia; zona waktu `Asia/Jakarta` (dapat dikonfigurasi).
- **Portabilitas DB**: jalan di SQLite **dan** MySQL tanpa mengubah kode (ADR-002).
- **Keterpantauan**: log terstruktur, `/up` health check, halaman error ramah.

## 8. Kriteria penerimaan rilis 1.0

- [ ] Semua FR P0 selesai dan lolos tes fitur.
- [ ] `composer check` (Pint + Larastan level 6 + Pest) hijau di CI.
- [ ] **Uji paritas**: impor DB lama contoh → rekap/Excel/PDF identik (angka) dengan aplikasi lama untuk ≥ 3 kelas.
- [ ] Uji keamanan checklist ([AI_RULES.md](AI_RULES.md) §Keamanan) lulus; tidak ada rute tanpa auth selain `/login` dan `/up`.
- [ ] Restore dari backup terbukti memulihkan data dan snapshot pra-restore terbentuk.
- [ ] Dokumen deploy diikuti dari nol pada server bersih berhasil.

## 9. Perubahan dari aplikasi lama (disengaja)

| Lama | Baru | Alasan |
|---|---|---|
| Tanpa login (user pertama otomatis) | Login wajib, peran admin/guru | Aman di server |
| `updated_at` ISO sebagai versi konflik | Kolom integer `version` | Sederhana, tak bergantung jam |
| Backup JSON/SQLite diunggah dari UI | Backup file DB/arsip + restore khusus admin dengan maintenance mode | Lebih aman dan konsisten lintas DB |
| Tabel `individual_test_sessions*` | Dibuang | API-nya sudah dihapus di aplikasi lama |
| Electron/`.bat` | Dibuang | Target server |
| Kunci setting `schoolName` dst. | `school_name`, `school_address`, `teacher_name` | Konvensi snake_case |

## 10. Pertanyaan terbuka (butuh keputusan pemilik)

Lihat [DECISIONS.md](DECISIONS.md) bagian *Terbuka*. Eksekutor **tidak boleh mengambil keputusan** atas butir terbuka; lanjutkan dengan asumsi default yang tertulis.
