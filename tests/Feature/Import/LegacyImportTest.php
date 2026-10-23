<?php

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->fixturePath = base_path('tests/Fixtures/legacy-sample.sqlite');
    expect(File::exists($this->fixturePath))->toBeTrue();
});

test('pib:import-legacy gagal bila berkas sqlite tidak ditemukan', function () {
    $exitCode = Artisan::call('pib:import-legacy', ['path' => 'berkas-palsu.sqlite']);
    expect($exitCode)->not->toBe(0);
});

test('pib:import-legacy menolak jika database tujuan berisi data tanpa opsi --force', function () {
    User::factory()->create();

    $exitCode = Artisan::call('pib:import-legacy', [
        'path' => $this->fixturePath,
    ]);

    expect($exitCode)->not->toBe(0);
});

test('pib:import-legacy dengan --dry-run tidak menyimpan perubahan ke database', function () {
    // Pastikan db bersih
    Student::query()->delete();
    SchoolClass::query()->delete();
    AcademicYear::query()->delete();

    $exitCode = Artisan::call('pib:import-legacy', [
        'path' => $this->fixturePath,
        '--dry-run' => true,
        '--force' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Student::count())->toBe(0);
    expect(SchoolClass::count())->toBe(0);
});

test('pib:import-legacy berhasil mengimpor seluruh data dari fixture lama dengan --force', function () {
    // Bersihkan database
    Student::query()->delete();
    SchoolClass::query()->delete();
    AcademicYear::query()->delete();

    $exitCode = Artisan::call('pib:import-legacy', [
        'path' => $this->fixturePath,
        '--force' => true,
    ]);

    expect($exitCode)->toBe(0);

    // Verifikasi jumlah entitas
    expect(AcademicYear::count())->toBe(2);
    expect(SchoolClass::count())->toBe(3);
    expect(Student::count())->toBe(30);
    expect(Assessment::count())->toBe(9);

    // Verifikasi settings terkonversi ke snake_case
    expect(Setting::where('key', 'school_name')->value('value'))->toBe('SMP Islam Terpadu Contoh');
    expect(Setting::where('key', 'school_address')->value('value'))->toContain('Bandung');
    expect(Setting::where('key', 'teacher_name')->value('value'))->toContain('Ustadz Abdullah');

    // Verifikasi user LOCAL_ONLY dinonaktifkan
    $legacyUser = User::where('email', 'lama@pib.local')->first();
    expect($legacyUser)->not->toBeNull();
    expect($legacyUser->is_active)->toBeFalse();
});
