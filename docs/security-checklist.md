# Audit Keamanan Rilis 1.0

Tanggal audit: 2026-10-23. Acuan: `docs/AI_RULES.md` §5, `docs/ARCHITECTURE.md` §Rute/Keamanan, PRD §7,
`routes/web.php`, serta rute bawaan Laravel dan Livewire yang terlihat dari `php artisan route:list -vv`.

## Inventaris rute aplikasi

`auth` berarti middleware `auth` dan `EnsureActiveUser`. Semua rute aplikasi di bawah juga melewati middleware `web`
(CSRF, sesi, binding, dan `SecurityHeaders`), kecuali keterangan lain.

| Metode dan path | Akses | Validasi / perlindungan khusus | Bukti pemeriksaan |
|---|---|---|---|
| `GET /` | `auth` | Redirect ke dashboard setelah login | `RouteAccessAuditTest` |
| `GET /dashboard`, `/akun`, `/sekolah`, `/kelas`, `/materi`, `/siswa`, `/siswa/impor` | `auth` | Livewire memvalidasi semua aksi tulis | route middleware + tes tiap komponen |
| `GET /siswa/template` | `auth` | Unduhan CSV konstan, tanpa input pengguna | `StudentImportTest` |
| `GET /penilaian`, `/penilaian/setoran`, `/penilaian/materi` | `auth` | Hanya membaca payload kelas | `SetoranPageTest`, `MateriAssessmentPageTest` |
| `GET /penilaian/kelas/{class}/progress` | `auth` | Binding kelas; 404 bila tidak ditemukan | `ClassProgressServiceTest` |
| `POST /penilaian/sync` | `auth` | `ScoreSyncRequest`, CSRF, 120/menit/pengguna | `ScoreSyncEndpointTest` |
| `GET /rekap`, `/laporan` | `auth` | Filter Livewire divalidasi | `RekapTest`, `LaporanTest` |
| `GET /laporan/excel`, `/laporan/pdf` | `auth` | `ReportFilterRequest::validated()` | `ReportExcelTest`, `ReportPdfClassTest` |
| `GET /laporan/pdf/siswa/{student}` | `auth` | Binding siswa; nama berkas di-slug | `ReportPdfStudentTest` |
| `GET /backup/status`, `/backup/unduh` | `auth` | Status tidak mengungkap path server; unduh tanpa cache; audit unduh | `BackupDownloadTest` |
| `POST /backup/manual` | `auth` | CSRF, throttle 5/menit, audit backup | `BackupDownloadTest`, `RouteAccessAuditTest` |
| `POST /logout` | `auth` | CSRF; sesi dibatalkan dan token diganti | `AuthenticationTest` |
| `GET /admin/pengguna`, `/admin/audit` | `auth` + `can:admin` | Semua aksi Livewire mengulang `Authorize` | `PenggunaTest`, `AuditTest` |
| `POST /admin/restore` | `auth` + `can:admin` | CSRF, throttle 5/menit, JSON saja, maks. 50 MB, konfirmasi `PULIHKAN` | `AdminRestoreTest` |
| `GET /login`, `POST /login` | Tamu (`guest`) | CSRF; validasi; 5 percobaan/menit/email+IP dan 30 permintaan/menit/IP; pesan generik; audit gagal tanpa password | `AuthenticationTest`, `RouteAccessAuditTest` |
| `GET /up` | Publik | Health check Laravel, tanpa data aplikasi | `docs/DEPLOYMENT.md` T-901: HTTP 200 |

## Rute infrastruktur framework

| Rute | Akses dan hasil audit |
|---|---|
| `GET livewire-*/livewire.js`, peta sumber, CSS/JS komponen | Publik, hanya aset kode aplikasi; tidak membawa data pengguna. |
| `POST livewire-*/update` | CSRF dari grup `web`, header `X-Livewire` wajib, checksum snapshot Livewire, `auth`, `Authorize`, `EnsureActiveUser` persisten; dibatasi 120/menit. |
| `POST livewire-*/upload-file` | Grup `web`/CSRF, URL bertanda tangan, throttle bawaan 60/menit; komponen impor hanya menerima CSV/XLSX maks. 2 MB dan layanan membatasi 1000 baris. |
| `GET livewire-*/preview-file/{filename}` | URL bertanda tangan sementara; tanpa signature ditolak 401. |
| `GET|HEAD /storage/{path}`, `PUT /storage/{path}` | Rute dinonaktifkan (`filesystems.disks.local.serve=false`); berkas lokal tetap privat dan tidak disajikan lewat HTTP. |

## Checklist AI_RULES §5

| Pemeriksaan | Hasil | Catatan |
|---|---|---|
| Semua rute aplikasi di balik `auth` + `EnsureActiveUser`; admin memakai otorisasi admin | Lulus | `/` dipindah ke grup auth. Tes inventaris memeriksa semua rute aplikasi dan middleware Livewire persisten. Login dan `/up` adalah rute aplikasi publik yang diizinkan. |
| Semua input divalidasi; gunakan data tervalidasi | Lulus | Form Request dipakai untuk skor, laporan, restore; controller login memanggil `validate()`; komponen Livewire memakai `rules()`/`validate()`. Audit tidak menemukan `request()->all()` atau mass assignment dari request mentah. |
| IDOR | Lulus sesuai ADR-004 | Data memang dipakai bersama semua guru. ID path memakai route model binding dan service; akun admin/restore diberi gate. Tidak ada pemisahan kepemilikan per guru dalam desain rilis ini. |
| Metode tulis, CSRF, fetch | Lulus | Tidak ada aksi tulis aplikasi melalui GET. Tes memastikan POST utama memakai `PreventRequestForgery`; penyimpanan draft mengirim token melalui helper fetch. Upload framework memakai URL bertanda tangan. |
| Query berbasis input / SQL injection | Lulus | Query aplikasi memakai Eloquent/Query Builder dan binding. Tidak ditemukan `DB::raw` dengan input pengguna. Snapshot SQLite memakai nama file yang dibuat server dan escaping petik tunggal. |
| Output di-escape dan unduhan | Lulus | Blade memakai escape `{{ }}`; data sesi pada Alpine memakai `@js`; satu `{!! !!}` merender SVG dari peta ikon kode tetap. Nama PDF siswa dibuat dengan `Str::slug`; teks dinamis XLSX ditulis eksplisit sebagai string agar tidak menjadi formula. |
| Unggahan | Lulus | Impor membatasi CSV/XLSX, MIME, ekstensi, ukuran 2 MB, serta jumlah baris 1000. Restore membatasi JSON, MIME, ekstensi, ukuran 50 MB dan admin. Penyimpanan temporary Livewire ditandatangani; disk lokal tidak disajikan publik. |
| Rate limit | Lulus | Login 5/menit per email+IP serta 30 permintaan/menit/IP; sync nilai 120/menit/pengguna; Livewire update 120/menit; upload sementara 60/menit; backup manual dan restore 5/menit. |
| Audit dan log | Lulus | Perubahan nilai/master/impor/backup/restore/akun dicatat oleh service. Login gagal hanya menyimpan email, IP, dan user-agent; password/token tidak dicatat. Tes fitur memeriksa audit utama. |
| Log sensitif | Lulus dengan batasan | Tidak ditemukan kredensial di log aplikasi. Audit login menyimpan data identifikasi untuk investigasi; akses halaman audit hanya admin. |

## Perbaikan yang dilakukan

- Mengharuskan login pada `/` dan menambahkan pemeriksaan `EnsureActiveUser` ke logout.
- Mendaftarkan `EnsureActiveUser` sebagai middleware persisten Livewire agar sesi akun yang dinonaktifkan tidak dapat menjalankan aksi lanjutan.
- Membatasi endpoint pembaruan Livewire, backup manual, dan restore; menonaktifkan route serving disk lokal.
- Menolak impor siswa selain CSV/XLSX dan restore selain JSON.
- Menambahkan `tests/Feature/Security/RouteAccessAuditTest.php` untuk cakupan middleware rute, sesi Livewire, signed upload, CSRF, dan rate limit.

## Batas audit

Audit ini memeriksa kode, daftar middleware rute, dan tes otomatis. Pengujian manual browser, aksesibilitas, dan ukuran layar dikerjakan pada T-904.
Konfigurasi TLS/reverse proxy dan izin OS diperiksa saat pemasangan host sesuai catatan T-901.

## Audit backend lanjutan (2026-10-24)

- Restore menolak cadangan tanpa seluruh tabel aplikasi atau tanpa admin aktif sebelum data dihapus. Audit keberhasilan berada dalam transaksi pemulihan; kegagalan tidak membocorkan pesan exception database ke respons.
- `ScoreService` menolak pasangan siswa dan materi dari periode berbeda serta siswa, kelas, atau materi nonaktif dengan 404.
- Impor siswa memilih kelas pada tahun ajaran aktif bila nama kelas berulang, dan melaporkan baris ambigu bila tidak ada periode aktif.
- Backup MySQL memakai JSON lengkap, termasuk snapshot pra-restore; migrasi, backup, dan restore berhasil diuji pada database MySQL 8.4.10 sementara. Restore juga menolak melanjutkan bila mode pemeliharaan gagal aktif.
- Kredensial bawaan `Admin12345!` dihapus. `AdminSeeder` sekarang gagal secara aman bila `PIB_ADMIN_PASSWORD` kosong atau kurang dari 10 karakter.
- Endpoint status backup tidak lagi membocorkan path absolut server. Pesan toast di-encode untuk konteks JavaScript, dan teks dinamis ekspor XLSX dipaksa menjadi string untuk mencegah formula injection.
- Login memiliki lapisan throttle IP global selain pembatas email+IP. `composer audit` dan `npm audit` melaporkan 0 advisori/kerentanan pada lockfile tanggal audit.
- Gerbang lokal: `composer check` lulus (Pint, Larastan 0 error, Pest 261 tes/1177 assertion). Hasil CI perlu diverifikasi setelah perubahan ini di-push.
