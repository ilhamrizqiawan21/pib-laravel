<?php

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subchapter;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['is_active' => true, 'role' => 'teacher']);

    $this->year = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => 'VII A']);
    $this->student = Student::factory()->create([
        'school_class_id' => $this->class->id,
        'name' => 'Ahmad Dahlan',
        'gender' => 'L',
        'nis' => '1001',
    ]);

    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Wudhu', 'display_order' => 1]);
    $this->sub = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'title' => 'Praktik']);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $this->sub->id, 'title' => 'Niat', 'weight' => 1.0]);

    Score::factory()->create([
        'student_id' => $this->student->id,
        'assessment_id' => $this->assessment->id,
        'score' => 90,
        'mistakes' => 0,
    ]);
});

test('tamu tidak dapat mengunduh pdf rapor siswa', function () {
    $this->get('/laporan/pdf/siswa/'.$this->student->id)
        ->assertRedirect('/login');
});

test('mengunduh pdf rapor siswa yang tidak ada menghasilkan 404', function () {
    $this->actingAs($this->user)
        ->get('/laporan/pdf/siswa/999999')
        ->assertNotFound();
});

test('guru dapat mengunduh pdf rapor siswa dengan nama berkas berslug', function () {
    $response = $this->actingAs($this->user)
        ->get('/laporan/pdf/siswa/'.$this->student->id);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'inline; filename="rapor-ahmad-dahlan.pdf"');

    // Pastikan konten adalah PDF valid
    expect(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
});
