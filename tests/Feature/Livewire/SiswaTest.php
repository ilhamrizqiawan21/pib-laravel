<?php

use App\Livewire\Siswa;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman siswa', function () {
    $this->get('/siswa')->assertRedirect('/login');
});

test('pengguna dapat membuka halaman siswa dan melihat tabel siswa', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII-A']);
    $student = Student::factory()->create([
        'school_class_id' => $class->id,
        'name' => 'Ahmad Dahlan',
        'nis' => '1001',
    ]);

    $this->actingAs($user)->get('/siswa')
        ->assertOk()
        ->assertSee('Data Siswa')
        ->assertSee('Ahmad Dahlan')
        ->assertSee('1001');
});

test('pagination siswa memakai navigasi berbahasa indonesia dengan gaya PIB', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
    Student::factory()->count(26)->create(['school_class_id' => $class->id]);

    Livewire::actingAs($user)
        ->test(Siswa::class)
        ->assertSee('Navigasi halaman')
        ->assertSee('Menampilkan')
        ->assertSee('bg-brand-strong', false)
        ->assertSee('Halaman berikutnya');
});

test('pengguna dapat menambah siswa baru lewat UI', function () {
    $user = User::factory()->create();
    $class = SchoolClass::factory()->create();

    Livewire::actingAs($user)
        ->test(Siswa::class)
        ->set('form_school_class_id', $class->id)
        ->set('form_name', 'Siti Maryam')
        ->set('form_nis', '2002')
        ->set('form_gender', 'P')
        ->call('saveStudent')
        ->assertHasNoErrors();

    $student = Student::query()->where('nis', '2002')->first();
    expect($student)->not->toBeNull();
    expect($student->name)->toBe('Siti Maryam');
    expect($student->gender->value)->toBe('P');
});

test('modal konfirmasi hapus permanen menampilkan nama siswa', function () {
    $user = User::factory()->create();
    $student = Student::factory()->create(['name' => 'Siswa Yang Mau Dihapus']);

    Livewire::actingAs($user)
        ->test(Siswa::class)
        ->call('confirmDelete', $student->id)
        ->assertSet('deleting_id', $student->id)
        ->call('deleteConfirmed')
        ->assertHasNoErrors();

    expect(Student::query()->whereKey($student->id)->exists())->toBeFalse();
});
