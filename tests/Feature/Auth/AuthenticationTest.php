<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('test@example.com|127.0.0.1');
});

test('layar login dapat ditampilkan kepada tamu', function () {
    $response = $this->get('/login');

    $response->assertOk();
    $response->assertSee('PIB Penilaian');
    $response->assertSee('Masuk');
});

test('pengguna aktif dapat masuk dengan kredensial yang benar', function () {
    $user = User::factory()->create([
        'email' => 'guru@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'guru@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('login gagal jika password salah dan dicatat di audit log', function () {
    $user = User::factory()->create([
        'email' => 'guru@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    $response = $this->post('/login', [
        'email' => 'guru@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();

    $audit = AuditLog::query()
        ->where('entity', 'user')
        ->where('action', 'LOGIN_FAILED')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->meta)->not->toHaveKey('password');
});

test('pengguna nonaktif ditolak masuk dengan pesan generik', function () {
    User::factory()->create([
        'email' => 'nonaktif@example.com',
        'password' => Hash::make('password123'),
        'is_active' => false,
    ]);

    $response = $this->post('/login', [
        'email' => 'nonaktif@example.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('rate limit membatasi upaya login maks 5 kali per menit', function () {
    User::factory()->create([
        'email' => 'target@example.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'target@example.com',
            'password' => 'wrong-password',
        ]);
    }

    // Percobaan ke-6 harus ditahan oleh rate limiter
    $response = $this->post('/login', [
        'email' => 'target@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $errors = session('errors')->get('email');
    expect($errors[0])->toContain('Terlalu banyak');
});

test('pengguna yang dinonaktifkan saat sesi berjalan diputus oleh middleware', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();

    // Ubah status pengguna menjadi nonaktif
    $user->update(['is_active' => false]);

    $responseAfterDeactivation = $this->get('/dashboard');
    $responseAfterDeactivation->assertRedirect('/login');
    $this->assertGuest();
});

test('pengguna dapat keluar dari aplikasi (logout)', function () {
    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post('/logout');

    $response->assertRedirect('/login');
    $this->assertGuest();
});
