<?php

use App\Enums\Role;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
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

test('pib:import-legacy menormalkan waktu ISO UTC untuk kolom tanggal MySQL', function () {
    $source = tempnam(sys_get_temp_dir(), 'pib-legacy-');
    expect($source)->not->toBeFalse();
    File::copy($this->fixturePath, $source);

    try {
        $sqlite = new PDO('sqlite:'.$source);
        $scoreId = $sqlite->query('SELECT id FROM scores ORDER BY id LIMIT 1')->fetchColumn();
        $sqlite->prepare("UPDATE scores SET assessed_at = ?, updated_at = ?, initials = '', note = '' WHERE id = ?")
            ->execute(['2026-09-08T03:09:53.875Z', '2026-09-08T03:09:53.874Z', $scoreId]);
        $sqlite = null;

        $exitCode = Artisan::call('pib:import-legacy', ['path' => $source, '--force' => true]);
        $imported = DB::table('scores')->where('id', $scoreId)->first();

        expect($exitCode)->toBe(0);
        expect($imported->assessed_at)->toBe('2026-09-08 10:09:53');
        expect($imported->updated_at)->toBe('2026-09-08 10:09:53');
        expect($imported->initials)->toBe('');
        expect($imported->note)->toBe('');
    } finally {
        File::delete($source);
    }
});

test('pib:import-legacy menjaga admin tujuan saat id pengguna lama bertabrakan', function () {
    $admin = User::factory()->create(['id' => 1, 'role' => Role::Admin, 'email' => 'admin-tujuan@example.test']);

    $exitCode = Artisan::call('pib:import-legacy', ['path' => $this->fixturePath, '--force' => true]);

    expect($exitCode)->toBe(0);
    expect($admin->fresh()?->email)->toBe('admin-tujuan@example.test');
    expect($admin->fresh()?->role)->toBe(Role::Admin);

    $legacyUser = User::query()->where('email', 'guru@pib.local')->firstOrFail();
    expect($legacyUser->id)->not->toBe($admin->id);
    expect(DB::table('scores')->where('assessor_id', $legacyUser->id)->count())->toBe(129);
    expect(DB::table('audit_logs')->where('user_id', $legacyUser->id)->count())->toBe(1);
});
