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
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Ahmad', 'gender' => 'L', 'nis' => '1001']);

    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Wudhu', 'display_order' => 1]);
    $this->sub = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'title' => 'Praktik']);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $this->sub->id, 'title' => 'Niat', 'weight' => 1.0]);

    Score::factory()->create([
        'student_id' => $this->student->id,
        'assessment_id' => $this->assessment->id,
        'score' => 85,
        'mistakes' => 5,
    ]);
});

test('tamu tidak dapat mengunduh pdf kelas', function () {
    $this->get('/laporan/pdf')
        ->assertRedirect('/login');
});

test('filter tidak valid pada pdf kelas mengembalikan 422', function () {
    $this->actingAs($this->user)
        ->getJson('/laporan/pdf?academic_year_id=-1')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['academic_year_id']);
});

test('guru dapat mengunduh pdf kelas dengan header yang tepat', function () {
    $response = $this->actingAs($this->user)
        ->get('/laporan/pdf?academic_year_id='.$this->year->id.'&class_id='.$this->class->id);

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'inline; filename="laporan-pib.pdf"');

    // Pastikan konten dimulai dengan signature PDF (%PDF-)
    expect(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
});
