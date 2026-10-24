<?php

use App\Livewire\Akun;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman akun', function () {
    $this->get('/akun')->assertRedirect('/login');
});

test('tamu diarahkan ke login saat mengakses halaman pengaturan', function () {
    $this->get('/pengaturan')->assertRedirect('/login');
});

test('pengguna dapat melihat form ubah password di halaman akun', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user)->get('/akun')
        ->assertOk()
        ->assertSee('Pengaturan')
        ->assertSee('Nama Pengguna')
        ->assertSee('Email')
        ->assertSee('Nama Sekolah')
        ->assertSee('Kata Sandi Saat Ini')
        ->assertSee('Kata Sandi Baru');
});

test('pengguna dapat membuka halaman pengaturan dan mengubah profil serta nama sekolah', function () {
    $user = User::factory()->create([
        'name' => 'Guru Lama',
        'email' => 'lama@example.test',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('name', 'Guru Baru')
        ->set('email', 'baru@example.test')
        ->set('school_name', 'SMP Teladan')
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertSee('Profil berhasil diperbarui');

    expect($user->fresh()->only(['name', 'email']))->toBe([
        'name' => 'Guru Baru',
        'email' => 'baru@example.test',
    ]);
    expect(Setting::query()->where('key', 'school_name')->value('value'))->toBe('SMP Teladan');
});

test('pengguna dapat mengunggah foto profil yang tervalidasi', function () {
    Storage::fake('public');
    $user = User::factory()->create(['is_active' => true]);
    $photo = UploadedFile::fake()->image('profil.jpg', 200, 200);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('profile_photo', $photo)
        ->call('updateProfile')
        ->assertHasNoErrors();

    $path = $user->fresh()->profile_photo_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

test('pengaturan menolak email duplikat dan foto bukan gambar', function () {
    Storage::fake('public');
    $user = User::factory()->create(['is_active' => true]);
    User::factory()->create(['email' => 'dipakai@example.test']);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('email', 'dipakai@example.test')
        ->set('profile_photo', UploadedFile::fake()->create('payload.pdf', 100, 'application/pdf'))
        ->call('updateProfile')
        ->assertHasErrors(['email', 'profile_photo']);
});

test('pengguna dapat mengubah kata sandi dengan benar dan tercatat di audit', function () {
    $user = User::factory()->create([
        'password' => Hash::make('passwordLama123'),
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('current_password', 'passwordLama123')
        ->set('password', 'passwordBaru123')
        ->set('password_confirmation', 'passwordBaru123')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSee('Kata sandi berhasil diperbarui');

    expect(Hash::check('passwordBaru123', $user->fresh()->password))->toBeTrue();

    $audit = AuditLog::query()
        ->where('entity', 'user')
        ->where('entity_id', (string) $user->id)
        ->where('action', 'UPDATE')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->summary)->toContain('kata sandi');
    expect($audit->meta)->not->toHaveKey('password');
    expect($audit->meta)->not->toHaveKey('current_password');
});

test('ubah password gagal jika password lama salah', function () {
    $user = User::factory()->create([
        'password' => Hash::make('passwordBenar123'),
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('current_password', 'passwordSalah')
        ->set('password', 'passwordBaru123')
        ->set('password_confirmation', 'passwordBaru123')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('passwordBenar123', $user->fresh()->password))->toBeTrue();
});

test('ubah password gagal jika password baru kurang dari 10 karakter', function () {
    $user = User::factory()->create([
        'password' => Hash::make('passwordBenar123'),
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('current_password', 'passwordBenar123')
        ->set('password', 'pendek')
        ->set('password_confirmation', 'pendek')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

test('ubah password gagal jika konfirmasi password tidak cocok', function () {
    $user = User::factory()->create([
        'password' => Hash::make('passwordBenar123'),
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Akun::class)
        ->set('current_password', 'passwordBenar123')
        ->set('password', 'passwordBaru123')
        ->set('password_confirmation', 'berbedaBanget123')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});
