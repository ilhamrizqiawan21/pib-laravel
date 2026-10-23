<?php

use App\Enums\Role;
use App\Models\User;

test('tamu diarahkan ke halaman login saat mengakses dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('layout memuat landmark nav, main dan skip link', function () {
    $user = User::factory()->create([
        'role' => Role::Teacher,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Lewati ke konten');
    $response->assertSee('id="konten"', false);
    $response->assertSee('<nav', false);
    $response->assertSee('<main', false);
});

test('menu baku tampil untuk guru dan menu admin disembunyikan', function () {
    $guru = User::factory()->create([
        'role' => Role::Teacher,
        'is_active' => true,
    ]);

    $response = $this->actingAs($guru)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Beranda');
    $response->assertSee('Penilaian');
    $response->assertSee('Rekap');
    $response->assertSee('Sekolah & periode', false);
    $response->assertSee('Kelas');
    $response->assertSee('Siswa');
    $response->assertSee('Materi');
    $response->assertSee('Laporan & backup', false);

    // Menu admin tidak boleh terlihat untuk guru
    $response->assertDontSee('Kelola Pengguna');
    $response->assertDontSee('/admin/audit');
});

test('menu admin tampil untuk pengguna dengan peran admin', function () {
    $admin = User::factory()->create([
        'role' => Role::Admin,
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Pengguna');
    $response->assertSee('Audit');
});

test('komponen dasar blade dapat dirender dengan benar', function () {
    $renderedHeader = Blade::render('<x-page-header eyebrow="PENGATURAN" title="Sekolah" description="Identitas sekolah" />');
    expect($renderedHeader)->toContain('PENGATURAN')
        ->toContain('Sekolah')
        ->toContain('Identitas sekolah');

    $renderedCard = Blade::render('<x-card title="Data Kelas">Konten kartu</x-card>');
    expect($renderedCard)->toContain('Data Kelas')
        ->toContain('Konten kartu');

    $renderedButton = Blade::render('<x-button variant="primary">Simpan</x-button>');
    expect($renderedButton)->toContain('Simpan')
        ->toContain('button');

    $renderedBadge = Blade::render('<x-badge variant="success">Aktif</x-badge>');
    expect($renderedBadge)->toContain('Aktif');

    $renderedIcon = Blade::render('<x-icon name="home" />');
    expect($renderedIcon)->toContain('<svg')
        ->toContain('stroke-width="1.7"');

    $renderedEmptyState = Blade::render('<x-empty-state title="Belum ada data" description="Silakan tambah siswa baru" action-label="Tambah Siswa" action-url="/siswa/baru" />');
    expect($renderedEmptyState)->toContain('Belum ada data')
        ->toContain('Silakan tambah siswa baru')
        ->toContain('Tambah Siswa');
});
