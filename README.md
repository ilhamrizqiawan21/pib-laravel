# PIB Penilaian (Laravel)

Aplikasi untuk guru mencatat **Penilaian Praktik Ibadah (PIB)** siswa: input jumlah kesalahan → nilai otomatis (`90 − kesalahan`), rekap, laporan Excel/PDF, backup.
Penulisan ulang aplikasi Next.js lama ke **Laravel** agar ringan dan aman di-deploy ke server.

**Stack:** PHP 8.5 · Laravel 13 · Livewire 4 · Alpine.js · Tailwind CSS 4 · SQLite (portabel ke MySQL) · Pest · Larastan · Pint

## Mulai cepat

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite && php artisan migrate
composer check          # Pint + Larastan (level 6) + Pest — harus hijau
```

## Dokumen

| Dokumen | Isi |
|---|---|
| [docs/PRD.md](docs/PRD.md) | Produk, aturan nilai, persyaratan, kriteria rilis |
| [docs/SCHEMA.md](docs/SCHEMA.md) · [docs/ERD.md](docs/ERD.md) | Skema database & diagram relasi |
| [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) | Struktur kode, endpoint, keamanan, performa, pengujian |
| [docs/DESIGN.md](docs/DESIGN.md) | Sistem desain & spesifikasi layar |
| [docs/AI_RULES.md](docs/AI_RULES.md) · [GEMINI.md](GEMINI.md) | Aturan & panduan untuk agen AI eksekutor |
| [docs/TODO.md](docs/TODO.md) | Rencana eksekusi per fase |
| [docs/DECISIONS.md](docs/DECISIONS.md) | Keputusan arsitektur & pertanyaan terbuka |
| [docs/LEGACY_MAP.md](docs/LEGACY_MAP.md) | Peta dari aplikasi lama ke Laravel |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Panduan deploy server |

## Status

Fase 0 (fondasi) selesai: skema, model, domain nilai, dokumen, dan gerbang mutu. Fitur dikerjakan bertahap sesuai [docs/TODO.md](docs/TODO.md).
