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
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => '8B']);
    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Bab Thaharah']);
    $this->subchapter = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'title' => 'Wudhu']);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $this->subchapter->id, 'title' => 'Membasuh Muka', 'weight' => 1.0]);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Siti']);
});

test('tamu diarahkan ke login saat mengakses /penilaian/materi', function () {
    $this->get('/penilaian/materi')->assertRedirect('/login');
});

test('pengguna dapat membuka halaman penilaian per materi', function () {
    $this->actingAs($this->user)
        ->get('/penilaian/materi')
        ->assertOk()
        ->assertSee('Penilaian per Materi')
        ->assertSee('8B')
        ->assertSee('Per siswa')
        ->assertSee('Per materi');
});

test('halaman penilaian per materi dapat menerima parameter query classId dan assessmentId', function () {
    $this->actingAs($this->user)
        ->get("/penilaian/materi?classId={$this->class->id}&assessmentId={$this->assessment->id}")
        ->assertOk()
        ->assertSee('8B');
});
