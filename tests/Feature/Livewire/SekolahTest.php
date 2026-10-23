<?php

use App\Livewire\Sekolah;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman sekolah', function () {
    $this->get('/sekolah')->assertRedirect('/login');
});

test('pengguna dapat membuka halaman sekolah dan melihat data identitas', function () {
    $user = User::factory()->create();

    Setting::create(['key' => 'school_name', 'value' => 'SMP Teladan']);
    Setting::create(['key' => 'school_address', 'value' => 'Jl. Merdeka No. 45']);
    Setting::create(['key' => 'teacher_name', 'value' => 'Ust. Fatih']);

    $this->actingAs($user)->get('/sekolah')
        ->assertOk()
        ->assertSee('Sekolah &amp; periode', false)
        ->assertSee('SMP Teladan')
        ->assertSee('Jl. Merdeka No. 45')
        ->assertSee('Ust. Fatih');
});

test('pengguna dapat memperbarui identitas sekolah dengan validasi panjang', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Sekolah::class)
        ->set('school_name', 'SMP Negeri 1 Cibinong')
        ->set('school_address', 'Jl. Raya Jakarta Bogor')
        ->set('teacher_name', 'Drs. Supardi')
        ->call('saveIdentity')
        ->assertHasNoErrors()
        ->assertSee('Identitas sekolah berhasil disimpan');

    expect(Setting::query()->where('key', 'school_name')->value('value'))->toBe('SMP Negeri 1 Cibinong');

    // Validasi panjang karakter melebihi batas (150/250/120)
    Livewire::actingAs($user)
        ->test(Sekolah::class)
        ->set('school_name', str_repeat('a', 151))
        ->set('school_address', str_repeat('b', 251))
        ->set('teacher_name', str_repeat('c', 121))
        ->call('saveIdentity')
        ->assertHasErrors(['school_name', 'school_address', 'teacher_name']);
});

test('pengguna dapat menambah dan mengaktifkan tahun ajaran dari ui', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Sekolah::class)
        ->set('new_year_name', '2026/2027')
        ->set('new_year_semester', 'Ganjil')
        ->set('new_year_is_active', true)
        ->call('createAcademicYear')
        ->assertHasNoErrors();

    $year = AcademicYear::query()->where('name', '2026/2027')->first();
    expect($year)->not->toBeNull();
    expect($year->is_active)->toBeTrue();
});
