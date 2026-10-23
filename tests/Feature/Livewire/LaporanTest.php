<?php

use App\Livewire\Laporan;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true, 'role' => 'teacher']);

    $this->year = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => 'VII A']);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Ahmad Dahlan']);
});

test('tamu diarahkan ke halaman login saat mengakses halaman laporan', function () {
    $this->get('/laporan')
        ->assertRedirect('/login');
});

test('halaman laporan dapat diakses oleh guru yang login', function () {
    $this->actingAs($this->user)
        ->get('/laporan')
        ->assertOk()
        ->assertSee('Laporan')
        ->assertSee('Unduh Excel')
        ->assertSee('Unduh PDF');
});

test('komponen livewire laporan merender filter dan tombol unduhan dengan parameter yang tepat', function () {
    Livewire::actingAs($this->user)
        ->test(Laporan::class)
        ->set('academicYearId', (string) $this->year->id)
        ->set('classId', (string) $this->class->id)
        ->set('selectedStudentId', (string) $this->student->id)
        ->assertSee('VII A')
        ->assertSee('Ahmad Dahlan')
        ->assertSee(route('laporan.excel', ['academic_year_id' => $this->year->id, 'class_id' => $this->class->id]))
        ->assertSee(route('laporan.pdf', ['academic_year_id' => $this->year->id, 'class_id' => $this->class->id]))
        ->assertSee(route('laporan.pdf.student', ['student' => $this->student->id]));
});

test('pemilih rapor memuat nama kelas tanpa lazy loading', function () {
    Model::preventLazyLoading(true);

    try {
        Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Siti Aminah']);

        Livewire::actingAs($this->user)
            ->test(Laporan::class)
            ->assertSee('VII A')
            ->assertSee('Siti Aminah');
    } finally {
        Model::preventLazyLoading(false);
    }
});
