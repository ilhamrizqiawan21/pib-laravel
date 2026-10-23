<?php

use App\Livewire\Kelas;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Livewire\Livewire;

test('tamu diarahkan ke login saat mengakses halaman kelas', function () {
    $this->get('/kelas')->assertRedirect('/login');
});

test('pengguna dapat membuka halaman kelas dan melihat data kelas', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $class = SchoolClass::factory()->create([
        'academic_year_id' => $year->id,
        'name' => 'VII-A',
        'grade_level' => '7',
        'homeroom_teacher' => 'Ust. Hamzah',
    ]);

    $this->actingAs($user)->get('/kelas')
        ->assertOk()
        ->assertSee('Data Kelas')
        ->assertSee('VII-A')
        ->assertSee('Ust. Hamzah');
});

test('pengguna dapat membuat kelas baru lewat form ui', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);

    Livewire::actingAs($user)
        ->test(Kelas::class)
        ->set('form_academic_year_id', $year->id)
        ->set('form_name', 'VIII-B')
        ->set('form_grade_level', '8')
        ->set('form_homeroom_teacher', 'Ust. Salman')
        ->call('saveClass')
        ->assertHasNoErrors();

    expect(SchoolClass::query()->where('name', 'VIII-B')->exists())->toBeTrue();
});

test('pengguna tidak dapat menghapus kelas yang memiliki siswa', function () {
    $user = User::factory()->create();
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
    Student::factory()->create(['school_class_id' => $class->id]);

    Livewire::actingAs($user)
        ->test(Kelas::class)
        ->call('deleteClass', $class->id)
        ->assertSee('tidak dapat dihapus karena masih memiliki siswa');

    expect(SchoolClass::query()->whereKey($class->id)->exists())->toBeTrue();
});
