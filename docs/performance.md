# Audit performa T-903

## Metode

Benchmark dapat diulang dengan membuat SQLite kosong di direktori sementara sistem:

```sh
DB_CONNECTION=sqlite DB_DATABASE=/tmp/pib-performance.sqlite php artisan migrate:fresh --force
DB_CONNECTION=sqlite DB_DATABASE=/tmp/pib-performance.sqlite php scripts/performance-audit.php
```

Skrip menolak database non-SQLite, database in-memory, file di luar direktori sementara, skema yang belum dimigrasikan, atau database yang sudah berisi siswa. Fixture berisi 5 kelas, 40 siswa per kelas, 100 materi, dan 20.000 baris nilai. Rekap, Excel, dan payload Setoran mengukur satu kelas; PDF mengukur laporan seluruh tahun ajaran. Rekap, Excel, dan PDF masing-masing diukur tiga kali. Angka waktu utama adalah median; skrip juga mencetak rentang, memori puncak, serta rincian proses Excel.

## Hasil

Diukur pada 2026-10-23 di workstation Intel Core i5-10210U, 8 logical CPU, RAM 15 GiB, PHP 8.5.12 (`memory_limit=512M`, OPcache CLI aktif), dan SQLite 3.51.2. Waktu aktual bergantung pada host dan bukan jaminan untuk server produksi.

| Operasi | Median | Min–maks | Hasil tambahan | Kriteria PRD |
| --- | ---: | ---: | --- | ---: |
| Rekap kelas, 40 siswa × 100 materi | 27,34 ms | 26,00–29,13 ms | Memori puncak 40 MiB | < 500 ms |
| Excel seluruh kelas, 200 siswa × 100 materi | 2,13 dtk | 2,09–2,22 dtk | 5 sheet, 220 KB; buat 1,59 dtk, simpan 0,52 dtk; memori puncak 99 MiB | < 10 dtk |
| PDF seluruh kelas, 200 siswa × 100 materi | 9,91 dtk | 9,81–10,27 dtk | 1,11 MB; memori puncak 394 MiB | < 10 dtk |
| Payload Setoran, satu kelas | 316 ms | satu pengukuran | 3 query; 40 siswa, 100 materi, 4.000 nilai; JSON 585 KB | satu payload, tanpa N+1 |

Median semua operasi memenuhi anggaran PRD. PDF berada dekat batas: satu dari tiga pengukuran melewati 10 detik dan pemakaian memorinya paling tinggi. Deploy PHP disarankan memakai `memory_limit=512M` agar laporan PDF kelas penuh dapat dirender. Verifikasi kembali pada server target sebelum menerima beban lebih besar.

## Perubahan terkait

- Penulisan baris dan styling Excel dibatch per rentang; zebra memakai conditional formatting sehingga waktu ekspor turun dari sekitar 11,6 detik menjadi sekitar 2,1 detik pada fixture ini.
- Tabel PDF memakai layout tetap dan baris boleh terpotong antarhalaman, yang menghindari upaya mempertahankan setiap baris pada satu halaman.
- `ReportGrouper` menghitung jumlah materi/nilai langsung dalam satu lintasan, tanpa membuat dua array sementara per siswa dan bab.
