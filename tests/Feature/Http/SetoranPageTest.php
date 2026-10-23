<?php

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subchapter;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->year = AcademicYear::factory()->create(['is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => '7A']);
    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Bab Shalat']);
    $this->subchapter = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'title' => 'Shalat Fardhu']);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $this->subchapter->id, 'title' => 'Ruku', 'weight' => 1.0]);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Fulan']);
});

test('tamu diarahkan ke login saat mengakses /penilaian dan /penilaian/setoran', function () {
    $this->get('/penilaian')->assertRedirect('/login');
    $this->get('/penilaian/setoran')->assertRedirect('/login');
});

test('/penilaian dialihkan ke /penilaian/setoran secara default', function () {
    $this->actingAs($this->user)
        ->get('/penilaian')
        ->assertRedirect('/penilaian/setoran');
});

test('/penilaian dialihkan ke /penilaian/materi bila cookie mode adalah materi', function () {
    $this->actingAs($this->user)
        ->withCookie('pib_assessment_mode', 'materi')
        ->get('/penilaian')
        ->assertRedirect('/penilaian/materi');
});

test('pengguna dapat membuka halaman setoran dan melihat dropdown kelas serta aset skrip', function () {
    $this->actingAs($this->user)
        ->get('/penilaian/setoran')
        ->assertOk()
        ->assertSee('Setoran')
        ->assertSee('7A')
        ->assertSee('Per siswa')
        ->assertSee('Per materi');
});

test('halaman setoran dapat menerima parameter query classId dan studentId', function () {
    $this->actingAs($this->user)
        ->get("/penilaian/setoran?classId={$this->class->id}&studentId={$this->student->id}")
        ->assertOk()
        ->assertSee('7A');
});

test('layout memuat indikator draft global di topbar', function () {
    $this->actingAs($this->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('draftIndicatorComponent');
});
