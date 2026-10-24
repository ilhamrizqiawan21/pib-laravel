# DEPLOYMENT

Target: satu server Linux (VPS/on-prem). Asumsi default di DECISIONS Q1–Q5. Dokumen ini diuji pada checkout bersih untuk TODO T-901.

## Prasyarat
PHP 8.3+ (dikembangkan di 8.5) dengan ekstensi: `bcmath ctype curl dom fileinfo gd intl mbstring openssl pdo_sqlite pdo_mysql tokenizer xml zip` · Composer 2 · Node 22+ (hanya untuk build aset; boleh dibuild di CI/laptop) · nginx atau Caddy.

## Langkah (SQLite)
```bash
git clone <repo> /var/www/pib && cd /var/www/pib
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# edit .env: APP_ENV=production APP_DEBUG=false APP_URL=https://… SESSION_SECURE_COOKIE=true
touch database/database.sqlite
php artisan migrate --force
php artisan pib:create-admin           # interaktif; atau isi PIB_ADMIN_PASSWORD (min. 10 karakter) lalu jalankan AdminSeeder
npm ci && npm run build                # atau unggah public/build dari CI
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chown -R www-data:www-data storage bootstrap/cache database
```
## Web server
- Root = `public/`. Hanya `index.php` yang dieksekusi. Tolak akses ke `.env`, `storage`, `database`, dotfile.
- HTTPS wajib (Caddy otomatis, atau nginx + certbot). Teruskan `Host`, `X-Forwarded-Proto/For`; set `TrustProxies` untuk IP proxy.
- `client_max_body_size 52m` (restore maksimal 50 MB; impor maksimal 2 MB). PHP: `upload_max_filesize=50M`, `post_max_size=52M`, `memory_limit=512M`, OPcache aktif. Laporan PDF seluruh kelas memakai sekitar 394 MiB pada audit performa; gunakan setidaknya 512M untuk merender kelas penuh.

## Penjadwal & backup
Cron (satu baris): `* * * * * www-data cd /var/www/pib && php artisan schedule:run >> /dev/null 2>&1`
Jadwal (routes/console.php): backup berkala bila ada perubahan, pembersihan backup lama, pemantauan backup. Arahkan disk backup ke lokasi **di luar server** untuk produksi.

## Pembaruan
```bash
php artisan down --retry=60
git pull && composer install --no-dev -o && npm ci && npm run build
php artisan migrate --force      # snapshot otomatis sebelum migrasi (T-703)
php artisan config:cache route:cache view:cache event:cache && php artisan up
```
Rollback: `php artisan down`, pulihkan snapshot DB, checkout rilis sebelumnya, `up`.

## MySQL (opsional)
Buat DB `utf8mb4_unicode_ci`, set `DB_CONNECTION=mysql` + kredensial (pengguna khusus, hak minimal), `php artisan migrate --force`, lalu impor data.
Backup terjadwal dan snapshot pra-restore pada MySQL berformat JSON, bukan berkas SQLite. Jalankan uji backup lalu restore pada server MySQL target sebelum mengandalkannya untuk produksi.

## Pemeriksaan pasca-deploy
`/up` → 200 · login admin · buat data uji · unduh Excel/PDF · jalankan backup manual · cek `storage/logs` kosong dari error · `composer audit`.

## Catatan verifikasi T-901
Pada 2026-10-23, langkah SQLite dijalankan pada checkout bersih dalam container PHP 8.5.12 (Composer 2.10.3, Alpine Linux).
Ekstensi deploy dipasang, lalu `composer install --no-dev --optimize-autoloader`, pembuatan `.env` dan app key, migrasi,
pembuatan admin non-interaktif, cache config/route/view/event, serta `php artisan route:list` berhasil. Pada checkout bersih
terpisah, `npm ci` dan `npm run build` berhasil dengan Node 22.23.1; server Artisan mengembalikan `/up` HTTP 200 dan
`/login` HTTP 200. Gerbang `composer check` juga lulus: Pint, Larastan 0 error, Pest 226 tes.

Pengujian container ini memverifikasi langkah instalasi dan bootstrap aplikasi. Konfigurasi nginx/Caddy, HTTPS, izin pengguna
`www-data`, serta alur login interaktif melalui browser tetap perlu diperiksa pada host target.
