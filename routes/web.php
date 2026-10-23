<?php

use App\Http\Controllers\Admin\RestoreController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScoreProgressController;
use App\Http\Controllers\ScoreSyncController;
use App\Http\Controllers\StudentTemplateController;
use App\Http\Middleware\EnsureActiveUser;
use App\Livewire\Admin\Audit;
use App\Livewire\Admin\Pengguna;
use App\Livewire\Akun;
use App\Livewire\Dashboard;
use App\Livewire\Kelas;
use App\Livewire\Laporan;
use App\Livewire\Materi;
use App\Livewire\Rekap;
use App\Livewire\Sekolah;
use App\Livewire\Siswa;
use App\Livewire\SiswaImpor;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', EnsureActiveUser::class])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/akun', Akun::class)->name('akun');
    Route::get('/sekolah', Sekolah::class)->name('sekolah');
    Route::get('/kelas', Kelas::class)->name('kelas');
    Route::get('/materi', Materi::class)->name('materi');
    Route::get('/siswa', Siswa::class)->name('siswa');
    Route::get('/siswa/impor', SiswaImpor::class)->name('siswa.impor');
    Route::get('/siswa/template', [StudentTemplateController::class, 'download'])->name('siswa.template');

    // Penilaian halaman & JSON endpoints
    Route::get('/penilaian', [AssessmentController::class, 'index'])->name('penilaian');
    Route::get('/penilaian/setoran', [AssessmentController::class, 'setoran'])->name('penilaian.setoran');
    Route::get('/penilaian/materi', [AssessmentController::class, 'materi'])->name('penilaian.materi');
    Route::get('/penilaian/kelas/{class}/progress', [ScoreProgressController::class, 'show'])->name('penilaian.class-progress');
    Route::post('/penilaian/sync', [ScoreSyncController::class, 'sync'])
        ->middleware('throttle:120,1')
        ->name('penilaian.sync');

    // Rekapitulasi
    Route::get('/rekap', Rekap::class)->name('rekap');

    // Laporan & Unduhan
    Route::get('/laporan', Laporan::class)->name('laporan');
    Route::get('/laporan/excel', [ReportController::class, 'excel'])->name('laporan.excel');
    Route::get('/laporan/pdf', [ReportController::class, 'pdf'])->name('laporan.pdf');
    Route::get('/laporan/pdf/siswa/{student}', [ReportController::class, 'studentPdf'])->name('laporan.pdf.student');

    // Backup & Status
    Route::get('/backup/status', [BackupController::class, 'status'])->name('backup.status');
    Route::get('/backup/unduh', [BackupController::class, 'download'])->name('backup.download');
    Route::post('/backup/manual', [BackupController::class, 'createManual'])->name('backup.manual');

    // Area Admin (Khusus Admin)
    Route::middleware('can:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengguna', Pengguna::class)->name('pengguna');
        Route::get('/audit', Audit::class)->name('audit');
        Route::post('/restore', [RestoreController::class, 'restore'])->name('restore');
    });
});
