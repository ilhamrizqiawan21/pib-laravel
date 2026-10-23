# DEPLOYMENT

Target: satu server Linux (VPS/on-prem). Asumsi default di DECISIONS Q1–Q5. Dokumen ini dilengkapi/diuji pada TODO T-901.

## Prasyarat
PHP 8.3+ (dikembangkan di 8.5) dengan ekstensi: `bcmath ctype curl dom fileinfo gd intl mbstring openssl pdo_sqlite pdo_mysql tokenizer xml zip` · Composer 2 · Node 22+ (hanya untuk build aset; boleh dibuild di CI/laptop) · nginx atau Caddy · `sqlite3`/`mysqldump` untuk backup.

## Langkah (SQLite)
```bash
git clone <repo> /var/www/pib && cd /var/www/pib
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# edit .env: APP_ENV=production APP_DEBUG=false APP_URL=https://… SESSION_SECURE_COOKIE=true
touch database/database.sqlite
php artisan migrate --force
php artisan pib:create-admin           # interaktif; atau isi PIB_ADMIN_* lalu db:seed --class=AdminSeeder
npm ci && npm run build                # atau unggah public/build dari CI
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
chown -R www-data:www-data storage bootstrap/cache database
```
## Web server
- Root = `public/`. Hanya `index.php` yang dieksekusi. Tolak akses ke `.env`, `storage`, `database`, dotfile.
- HTTPS wajib (Caddy otomatis, atau nginx + certbot). Teruskan `Host`, `X-Forwarded-Proto/For`; set `TrustProxies` untuk IP proxy.
- `client_max_body_size 12m` (restore/impor). PHP: `upload_max_filesize=12M`, `post_max_size=12M`, `memory_limit=256M`, OPcache aktif.

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

## Pemeriksaan pasca-deploy
`/up` → 200 · login admin · buat data uji · unduh Excel/PDF · jalankan backup manual · cek `storage/logs` kosong dari error · `composer audit`.
