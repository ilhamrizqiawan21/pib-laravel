<?php

use App\DTO\RecapFilter;
use App\Enums\CompletionStatus;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subchapter;
use App\Services\RecapService;

beforeEach(function () {
    $this->service = app(RecapService::class);

    // Setup data uji konsistensi sesuai kasus porting dari recap-consistency.test.ts
    // Tahun ajaran 1: 2026/2027 Ganjil
    $this->year1 = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    // Tahun ajaran 2: 2026/2027 Genap
    $this->year2 = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Genap', 'is_active' => false]);

    $this->class1 = SchoolClass::factory()->create(['academic_year_id' => $this->year1->id, 'name' => 'VIII A']);
    $this->class2 = SchoolClass::factory()->create(['academic_year_id' => $this->year2->id, 'name' => 'VIII A']);

    // Siswa di kelas 1: Ani (id 1) dan Budi (id 2)
    $this->student1 = Student::factory()->create(['school_class_id' => $this->class1->id, 'name' => 'Ani', 'is_active' => true]);
    $this->student2 = Student::factory()->create(['school_class_id' => $this->class1->id, 'name' => 'Budi', 'is_active' => true]);

    // Siswa di kelas 2: Caca (id 3 - tanpa kurikulum)
    $this->student3 = Student::factory()->create(['school_class_id' => $this->class2->id, 'name' => 'Tanpa materi', 'is_active' => true]);

    // Kurikulum untuk Tahun Ajaran 1
    $this->template1 = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year1->id, 'name' => 'Template PAI']);
    $this->chapter1 = Chapter::factory()->create(['curriculum_template_id' => $this->template1->id, 'display_order' => 1, 'title' => 'Bab A']);
    $this->chapter2 = Chapter::factory()->create(['curriculum_template_id' => $this->template1->id, 'display_order' => 2, 'title' => 'Bab B']);

    $this->sub1 = Subchapter::factory()->create(['chapter_id' => $this->chapter1->id, 'display_order' => 1, 'title' => 'Sub A']);
    $this->sub2 = Subchapter::factory()->create(['chapter_id' => $this->chapter2->id, 'display_order' => 1, 'title' => 'Sub B']);

    $this->m1 = Assessment::factory()->create(['subchapter_id' => $this->sub1->id, 'display_order' => 1, 'title' => 'Materi A', 'weight' => 1.0, 'is_active' => true]);
    $this->m2 = Assessment::factory()->create(['subchapter_id' => $this->sub2->id, 'display_order' => 1, 'title' => 'Materi B', 'weight' => 3.0, 'is_active' => true]);

    // Nilai:
    // Ani: m1 = 90 (kesalahan 0), m2 = 60 (kesalahan 30)
    // Rata-rata Ani = (90*1 + 60*3) / (1 + 3) = (90 + 180) / 4 = 270 / 4 = 67.5
    Score::factory()->create(['student_id' => $this->student1->id, 'assessment_id' => $this->m1->id, 'mistakes' => 0, 'score' => 90]);
    Score::factory()->create(['student_id' => $this->student1->id, 'assessment_id' => $this->m2->id, 'mistakes' => 30, 'score' => 60]);

    // Budi: m1 = 0 (kesalahan 90), m2 = belum dinilai (null)
    // Rata-rata Budi = (0*1) / 1 = 0.0
    Score::factory()->create(['student_id' => $this->student2->id, 'assessment_id' => $this->m1->id, 'mistakes' => 90, 'score' => 0]);
});

test('rekap menghitung statistik per siswa secara akurat dengan rata-rata berbobot', function () {
    $filter = new RecapFilter(academicYearId: $this->year1->id);
    $recap = $this->service->getRecap($filter);

    expect($recap['students'])->toHaveCount(2);

    $ani = collect($recap['students'])->firstWhere('id', $this->student1->id);
    expect($ani['expected'])->toBe(2);
    expect($ani['assessed'])->toBe(2);
    expect($ani['total'])->toBe(150);
    expect($ani['average'])->toBe(67.5);
    expect($ani['percent'])->toBe(100);
    expect($ani['status'])->toBe(CompletionStatus::Complete);

    $budi = collect($recap['students'])->firstWhere('id', $this->student2->id);
    expect($budi['expected'])->toBe(2);
    expect($budi['assessed'])->toBe(1);
    expect($budi['total'])->toBe(0);
    expect($budi['average'])->toBe(0.0);
    expect($budi['percent'])->toBe(50);
    expect($budi['status'])->toBe(CompletionStatus::Partial);
});

test('siswa tanpa materi dipertahankan dengan status materi belum tersedia', function () {
    $filter = new RecapFilter(academicYearId: $this->year2->id);
    $recap = $this->service->getRecap($filter);

    expect($recap['students'])->toHaveCount(1);
    $caca = $recap['students'][0];
    expect($caca['name'])->toBe('Tanpa materi');
    expect($caca['expected'])->toBe(0);
    expect($caca['assessed'])->toBe(0);
    expect($caca['total'])->toBe(0);
    expect($caca['average'])->toBeNull();
    expect($caca['percent'])->toBe(0);
    expect($caca['status'])->toBe(CompletionStatus::NoMaterial);
});

test('rekap menghitung ringkasan kelas secara tepat', function () {
    $filter = new RecapFilter(classId: $this->class1->id);
    $recap = $this->service->getRecap($filter);

    expect($recap['classes'])->toHaveCount(1);
    $classA = $recap['classes'][0];
    // Kelas 1: 2 siswa, masing-masing 2 materi = 4 expected.
    // Yang dinilai: Ani 2 materi (90, 60), Budi 1 materi (0) = 3 assessed.
    // Total skor = 90 + 60 + 0 = 150.
    // Total bobot terisi: Ani m1(1) + Ani m2(3) + Budi m1(1) = 5.
    // Rata-rata kelas = (90*1 + 60*3 + 0*1) / 5 = 270 / 5 = 54.0.
    expect($classA['students_count'])->toBe(2);
    expect($classA['expected'])->toBe(4);
    expect($classA['assessed'])->toBe(3);
    expect($classA['total'])->toBe(150);
    expect($classA['average'])->toBe(54.0);
    expect($classA['percent'])->toBe(75);
    expect($classA['status'])->toBe(CompletionStatus::Partial);
});

test('rekap dapat memfilter detail siswa tertentu', function () {
    $filter = new RecapFilter(classId: $this->class1->id);
    $details = $this->service->getStudentDetails($this->student2->id, $filter);

    expect($details)->toHaveCount(2);
    expect($details[0]['student_id'])->toBe($this->student2->id);
    expect($details[0]['assessment_id'])->toBe($this->m1->id);
    expect($details[0]['score'])->toBe(0);
    expect($details[0]['mistakes'])->toBe(90);

    expect($details[1]['assessment_id'])->toBe($this->m2->id);
    expect($details[1]['score'])->toBeNull();
});

test('filter nama siswa bekerja dengan pencarian parsial case-insensitive', function () {
    $filter = new RecapFilter(student: 'an');
    $recap = $this->service->getRecap($filter);

    $names = collect($recap['students'])->pluck('name')->all();
    expect($names)->toContain('Ani');
    expect($names)->toContain('Tanpa materi');
    expect($names)->not->toContain('Budi');
});
