<?php

use App\Livewire\Akun;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman akun', function () {
    $this->get('/akun')->assertRedirect('/login');
});

test('pengguna dapat melihat form ubah password di halaman akun', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($user)->get('/akun')
        ->assertOk()
        ->assertSee('Akun &amp; Kata Sandi', false)
        ->assertSee('Kata Sandi Saat Ini')
        ->assertSee('Kata Sandi Baru');
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
