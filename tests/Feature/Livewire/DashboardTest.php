<?php

use App\Livewire\Dashboard;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->year = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => '7A']);
    Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Farhan']);
});

test('tamu diarahkan ke login saat mengakses /dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('pengguna dapat membuka beranda dashboard dan melihat statistik ringkas', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertOk()
        ->assertSee('RINGKASAN KERJA')
        ->assertSee('Siswa Aktif')
        ->assertSee('Kelas Aktif')
        ->assertSee('Prioritas Kelas');
});

test('pengguna dapat mengubah filter periode tahun ajaran pada dashboard', function () {
    $yearGenap = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Genap', 'is_active' => false]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->set('selected_academic_year_id', $yearGenap->id)
        ->assertSet('selected_academic_year_id', $yearGenap->id);
});
