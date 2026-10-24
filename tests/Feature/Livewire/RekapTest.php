<?php

use App\Livewire\Rekap;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subchapter;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->year = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => '9A']);

    $this->student1 = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Aisyah']);
    $this->student2 = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Bilal']);

    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Bab Shalat']);
    $this->subchapter = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'title' => 'Shalat Sunnah']);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $this->subchapter->id, 'title' => 'Tahajjud', 'weight' => 2.0]);

    Score::factory()->create([
        'student_id' => $this->student1->id,
        'assessment_id' => $this->assessment->id,
        'mistakes' => 5,
        'score' => 85,
    ]);
});

test('tamu diarahkan ke login saat mengakses /rekap', function () {
    $this->get('/rekap')->assertRedirect('/login');
});

test('pengguna dapat membuka halaman rekap dan melihat ringkasan siswa', function () {
    Livewire::actingAs($this->user)
        ->test(Rekap::class)
        ->assertOk()
        ->assertSee('Rekapitulasi Nilai')
        ->assertSee('Aisyah')
        ->assertSee('Bilal')
        ->assertSee('85')
        ->assertSee('9A');
});

test('filter kelas dan pencarian siswa bekerja pada halaman rekap', function () {
    $otherClass = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => '9B']);
    $otherStudent = Student::factory()->create(['school_class_id' => $otherClass->id, 'name' => 'Zaid']);

    Livewire::actingAs($this->user)
        ->test(Rekap::class)
        ->set('class_id', $this->class->id)
        ->assertSee('Aisyah')
        ->assertDontSee('Zaid')
        ->set('student', 'Bilal')
        ->assertSee('Bilal')
        ->assertDontSee('Aisyah');
});

test('tautan laporan membawa filter kelas dan siswa ke halaman rekap', function () {
    Livewire::withQueryParams([
        'academic_year_id' => $this->year->id,
        'class_id' => $this->class->id,
        'student' => 'Bilal',
    ])->actingAs($this->user)
        ->test(Rekap::class)
        ->assertSet('academic_year_id', $this->year->id)
        ->assertSet('class_id', $this->class->id)
        ->assertSet('student', 'Bilal');
});

test('tab matriks menampilkan siswa dan materi dalam format grid', function () {
    Livewire::actingAs($this->user)
        ->test(Rekap::class)
        ->set('class_id', $this->class->id)
        ->set('active_tab', 'matrix')
        ->assertSee('Tahajjud')
        ->assertSee('Aisyah')
        ->assertSee('85');
});

test('pengguna dapat membuka panel detail nilai siswa', function () {
    Livewire::actingAs($this->user)
        ->test(Rekap::class)
        ->set('selected_student_id', $this->student1->id)
        ->assertSee('Detail Penilaian:')
        ->assertSee('Tahajjud')
        ->assertSee('85');
});
