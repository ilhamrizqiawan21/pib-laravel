# DESIGN — Sistem Desain & Spesifikasi Layar

Tujuan: **modern, bersih, konsisten, cepat dipakai di kelas**, bukan "CRUD klasik". Visual mengikuti aplikasi lama (teal), disalin ke
`resources/css/app.css` sebagai CSS variable + dipetakan ke Tailwind 4 (`@theme`). Referensi implementasi lama: `~/Projects/penilaian-pib/app/globals.css`, `shell.css`, `workspace.css`, `ui.tsx`.

## 1. Token

```css
:root{
  --ink:#0f172a; --muted:#64748b; --canvas:#f1f5f9; --surface:#ffffff; --line:#e2e8f0;
  --brand:#0d9488; --brand-strong:#0f766e; --brand-soft:#ccfbf1;
  --success:#10b981; --success-soft:#ecfdf5;
  --warning:#d97706; --warning-soft:#fffbeb;
  --danger:#ef4444;  --danger-soft:#fef2f2;
  --radius:14px;
  --shadow:0 1px 3px 0 rgb(0 0 0/.1),0 1px 2px -1px rgb(0 0 0/.1);
  --shadow-md:0 4px 6px -1px rgb(0 0 0/.1),0 2px 4px -2px rgb(0 0 0/.1);
  --shadow-lg:0 10px 15px -3px rgb(0 0 0/.1),0 4px 6px -4px rgb(0 0 0/.1);
  --focus:#5eead4;
}
```
- Font: **Inter Variable** (lokal). Skala: 12/13/14(base)/16/20/24/30. Judul halaman 24–30 semi-bold; *eyebrow* 12 uppercase tracking lebar warna `--brand-strong`.
- Spasi grid 4px; padding halaman `34px 36px` (desktop), `16px` (ponsel). Lebar konten maks `1320px`.
- Fokus: `outline:3px solid var(--focus); outline-offset:2px` pada semua elemen interaktif.
- Gelap: **tidak** untuk 1.0 (P2). Token disusun agar mudah ditambah (`:root[data-theme=dark]`).
- Kontras teks ≥ WCAG AA. Jangan membawa informasi hanya dengan warna — pasangkan ikon/teks (mis. status).
- Ikon: **Lucide** (SVG inline via Blade component `<x-icon name="…">`), stroke 1.7, ukuran 20.
- Favicon: monogram **P** putih pada latar `--brand-strong`; sumber SVG dan ICO kompatibilitas ada di `public/favicon.svg` dan `public/favicon.ico`.

## 2. Layout aplikasi

- **Sidebar kiri 252px**, bisa diciutkan (ikon saja; pilihan disimpan di `localStorage` `pib-sidebar-collapsed`, di-set oleh skrip `<head>` agar tanpa kedip).
  Ponsel: sidebar menjadi *drawer* dengan tombol hamburger di topbar.
- **Topbar 68px**, sticky, kaca (`rgba(255,255,255,.85)` + blur): nama sekolah (kiri), periode aktif, menu pengguna (kanan: nama, Akun, Keluar).
- **Skip link** "Lewati ke konten" di awal. Landmark: `nav`, `main#konten`.
- **Indikator navigasi** tipis di atas (progress bar) saat pindah halaman (wire:navigate).
- Menu (urutan & label **baku**):
  - *Kerja harian*: **Beranda** · **Penilaian** · **Rekap**
  - *Kelola data*: **Sekolah & periode** · **Kelas** · **Siswa** · **Materi**
  - *Laporan*: **Laporan & backup**
  - *Admin* (hanya admin): **Pengguna** · **Audit**
  - "Penilaian" aktif untuk `/penilaian/*`; membuka mode terakhir (default: per siswa).
- Indikator draft global (`DraftIndicator`): lencana "N nilai belum terkirim" bila ada antrean, klik → buka halaman penilaian terkait.

## 3. Komponen (Blade components di `resources/views/components`)

`page-header` (eyebrow, title, description, aksi) · `card` · `button` (primary/ghost/danger, `loading`) · `field`/`input`/`select`/`search-field` ·
`modal` (focus-trap, Esc, konfirmasi bahaya) · `toast` (Alpine store, `role=status`) · `badge`/`status-badge` · `progress-bar` ·
`empty-state` (judul, penjelasan, **aksi berikutnya**) · `error-state` (coba lagi) · `loading-state` (skeleton) · `data-table` (sortir/cari, header sticky,
ponsel → kartu) · `kbd` · `alert` (info/warning/danger/success).
Aturan: semua teks UI bahasa Indonesia; tombol destruktif selalu lewat modal konfirmasi menyebut dampak ("Hapus permanen 12 nilai siswa ini?").

## 4. Layar

Tiap layar wajib punya **4 keadaan**: memuat, kosong (dengan aksi berikutnya), galat (dengan coba lagi), dan terisi.

### 4.1 Login (`/login`)
Kartu tengah, logo + nama aplikasi, email, password, tombol "Masuk". Error generik "Email atau password salah". Rate-limit → "Terlalu banyak percobaan. Coba lagi dalam N detik." Tanpa tautan registrasi.

### 4.2 Beranda (`/dashboard`)
- Header: eyebrow "Ringkasan kerja", judul "Selamat datang, {guru}", deskripsi periode aktif. Pemilih periode (aktif / semua / tahun ajaran tertentu).
- **Checklist penyiapan** (muncul bila belum lengkap): Sekolah → Tahun ajaran → Kelas → Siswa → Materi → Nilai; tiap langkah tautan ke layar terkait.
- Kartu statistik: kelas, siswa, nilai terisi, progres total (bar).
- **Prioritas kelas** (≤5, progres terendah) dengan bar + tautan "Lanjutkan"; tombol besar **Lanjutkan penilaian terakhir**.
- **Aktivitas terbaru** (5 audit terakhir).

### 4.3 Penilaian → Setoran (`/penilaian/setoran`) — layar terpenting
Dua panel: **kiri daftar siswa** (260px), **kanan daftar materi**.
- Atas: tab mode `Per siswa | Per materi`; pemilih kelas (diingat).
- Panel siswa: kotak cari (`/` memfokuskan), tiap baris: nama, posisi `11/21`, titik oranye bila ada draft belum terkirim. Dinilai-hari-ini di atas. `↑/↓` pilih, Enter buka.
- Panel materi: dikelompokkan per **bab › subbab** (sticky header). Baris materi: judul, bobot (bila ≠1), input angka kesalahan (`inputmode=numeric`, lebar kecil),
  nilai hasil (`= 87`) tebal, tombol **90** ("Lancar: 0 kesalahan"), status (✓ tersimpan, ⏳ menunggu, ⚠ konflik, ✕ gagal), tombol batalkan/refresh pada draft.
  Materi terlewat diberi lencana **Terlewat** (kuning). Kursor awal di materi berikutnya.
- Perilaku: ketik angka → Enter = kirim + fokus ke baris berikutnya; angka tak valid → pesan "Isi bilangan bulat 0–90." tanpa kirim; kolom kosong+Enter = kosongkan nilai (dengan konfirmasi ringan bila ada nilai).
- Kondisi: belum pilih kelas ("Pilih kelas…"), tanpa materi (tautan ke Materi), tanpa siswa (tautan ke Siswa), belum pilih siswa (petunjuk `/`).
- Ponsel: panel siswa jadi *bottom sheet*/dropdown; satu kolom materi; tombol 90 ≥ 44px.
- Implementasi: pada ponsel, daftar siswa tertutup secara bawaan di balik tombol "Ganti siswa"; pemilih kelas dan siswa terpilih tetap terlihat, sehingga editor nilai muncul di layar pertama. Tombol "Detail" membuka paraf, catatan, dan tanggal; pengosongan nilai tersimpan meminta konfirmasi.

### 4.4 Penilaian → Per materi (`/penilaian/materi`)
Pilih bab/subbab/materi → tabel siswa kelas (nama, input kesalahan, nilai, paraf, catatan, tanggal). Tombol ± langkah 1; Tab/Enter turun ke siswa berikutnya. Progres "18/32 siswa".
Paraf, catatan, dan tanggal dibuka melalui tombol "Detail" per siswa; pengosongan nilai tersimpan meminta konfirmasi.

### 4.5 Rekap (`/rekap`)
Filter bar (tahun ajaran/semester, kelas, siswa, bab, subbab, materi) — **bagian URL query** sehingga bisa dibagikan/ditandai. Ringkasan per siswa: nama, NIS, terisi/total, total, rata-rata, **status** (badge), progres.
Klik siswa → panel detail (per bab › subbab › materi: kesalahan, nilai, bobot, tanggal, paraf, catatan). Tab "Matriks" siswa × subbab/materi dengan sel berwarna menurut nilai (legenda: kosong/rendah/sedang/tinggi — jangan mengandalkan warna saja, tampilkan angka).

### 4.6 Sekolah & periode (`/sekolah`)
Form identitas (nama sekolah, alamat, nama guru) + daftar tahun ajaran/semester (tambah, ubah nama, jadikan aktif, hapus bila kosong).

### 4.7 Kelas (`/kelas`), Siswa (`/siswa`)
Tabel + pencarian + filter kelas; tambah/ubah lewat modal; nonaktifkan; hapus permanen (modal bahaya). Siswa: tombol **Impor** (unggah → pratinjau → hasil dengan baris gagal), **Unduh template**.

### 4.8 Materi (`/materi`)
Pilih template/tahun ajaran → daftar bab → subbab → materi (breadcrumb). Edit inline judul/bobot/deskripsi, tombol naik/turun (atau seret) untuk urutan, nonaktifkan,
**Salin struktur periode** (pilih sumber → target → pratinjau jumlah → konfirmasi). Bobot ditampilkan hanya bila ≠ 1.

### 4.9 Laporan & backup (`/laporan`)
Bagian 1 *Unduh hasil*: filter (sama dengan Rekap) + tombol **Excel** dan **PDF** (+ pilih siswa untuk PDF per siswa).
Bagian 2 *Backup*: status terakhir (waktu, ukuran), tombol **Unduh backup**; admin: **Pulihkan** (unggah → ringkasan → modal konfirmasi ketik "PULIHKAN").

### 4.10 Akun (`/akun`) · Admin Pengguna/Audit
Ubah password (lama, baru, ulangi) · tabel pengguna (buat, nonaktifkan, reset password, peran) · tabel audit (cari, filter entitas/aksi, paginasi).

## 5. Gerak & umpan balik

- Transisi 150–240 ms `cubic-bezier(.2,.75,.25,1)`; hormati `prefers-reduced-motion`.
- Setiap aksi tulis memberi umpan balik: tombol `loading`, lalu **toast** (sukses/gagal). Penyimpanan nilai: ikon status di baris, bukan toast.
- Hindari animasi dekoratif; skeleton untuk muat awal; tidak ada spinner layar penuh.

## 6. Aksesibilitas (wajib)

Label terhubung ke input; `aria-live=polite` untuk toast/status simpan; modal menjebak fokus dan mengembalikannya; tabel memakai `<th scope>`;
semua fungsi bisa dengan keyboard; target sentuh ≥ 44px di ponsel; pesan error spesifik per kolom dan dibacakan (`aria-describedby`).

## 7. Salinan (copy)

Nada ramah, ringkas, bahasa Indonesia baku sehari-hari. Istilah baku: *Setoran, Materi, Bab, Subbab, Periode (tahun ajaran + semester), Nilai, Kesalahan, Belum dinilai, Terlewat, Rekap, Laporan*. Hindari istilah teknis ("sync", "payload") di UI — pakai "Menunggu koneksi", "Tersimpan".

## 8. Responsif

Breakpoint: `≥1024` sidebar tetap · `640–1023` sidebar ciut/drawer · `<640` satu kolom, tabel → kartu. Diuji pada 360×800 dan 1366×768.

Implementasi T-904: tabel data memakai `resources/js/responsive-tables.js` untuk menyalin nama kolom ke label kartu pada lebar <640px. Matriks rekap memakai `data-scroll-table` agar susunan siswa × materi tetap dapat digeser. Dialog dan drawer mengembalikan fokus ke pemicu ketika ditutup; semua tombol utama di ponsel memiliki tinggi sentuh minimal 44px. Alias kelas warna lama dipetakan ke token §1 di `resources/css/app.css`.
