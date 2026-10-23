<?php

use App\DTO\RecapFilter;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subchapter;
use App\Services\RecapService;
use App\Services\ReportGrouper;

beforeEach(function () {
    $this->recapService = app(RecapService::class);
    $this->grouper = app(ReportGrouper::class);

    $this->year = AcademicYear::factory()->create(['name' => '2026/2027', 'semester' => 'Ganjil', 'is_active' => true]);
    $this->classA = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => 'VII A']);
    $this->classB = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => 'VII B']);

    $this->student1 = Student::factory()->create(['school_class_id' => $this->classA->id, 'name' => 'Ahmad', 'gender' => 'L', 'nis' => '1001']);
    $this->student2 = Student::factory()->create(['school_class_id' => $this->classA->id, 'name' => 'Budi', 'gender' => 'L', 'nis' => '1002']);
    $this->student3 = Student::factory()->create(['school_class_id' => $this->classB->id, 'name' => 'Citra', 'gender' => 'P', 'nis' => '1003']);

    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);
    $this->chapter1 = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Wudhu', 'display_order' => 1]);
    $this->chapter2 = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'title' => 'Shalat', 'display_order' => 2]);

    $this->sub1 = Subchapter::factory()->create(['chapter_id' => $this->chapter1->id, 'title' => 'Gerakan Wudhu']);
    $this->sub2 = Subchapter::factory()->create(['chapter_id' => $this->chapter2->id, 'title' => 'Bacaan Shalat']);

    $this->m1 = Assessment::factory()->create(['subchapter_id' => $this->sub1->id, 'title' => 'Mencuci Muka', 'weight' => 1.0]);
    $this->m2 = Assessment::factory()->create(['subchapter_id' => $this->sub2->id, 'title' => 'Iftitah', 'weight' => 2.0]);

    Score::factory()->create(['student_id' => $this->student1->id, 'assessment_id' => $this->m1->id, 'score' => 90, 'mistakes' => 0]);
    Score::factory()->create(['student_id' => $this->student1->id, 'assessment_id' => $this->m2->id, 'score' => 80, 'mistakes' => 10]);

    // Budi hanya dinilai m1
    Score::factory()->create(['student_id' => $this->student2->id, 'assessment_id' => $this->m1->id, 'score' => 70, 'mistakes' => 20]);
});

test('groupAverageRows mengelompokkan baris berdasarkan bab dan kelas secara deterministik', function () {
    $rows = $this->recapService->getRows(new RecapFilter(academicYearId: $this->year->id));
    $groups = $this->grouper->groupAverageRows($rows);

    expect($groups)->toHaveCount(2);

    // Bab Shalat & Bab Wudhu urut alfabetis
    $chapterLabels = array_column($groups, 'label');
    expect($chapterLabels)->toContain('Shalat', 'Wudhu');

    $wudhu = collect($groups)->firstWhere('label', 'Wudhu');
    expect($wudhu)->not->toBeNull();
    expect($wudhu['classes'])->toHaveCount(2); // VII A dan VII B

    $classA = collect($wudhu['classes'])->firstWhere('name', 'VII A · 2026/2027 · Ganjil');
    expect($classA)->not->toBeNull();
    expect($classA['rows'])->toHaveCount(2); // Ahmad dan Budi

    $ahmad = collect($classA['rows'])->firstWhere('name', 'Ahmad');
    expect($ahmad['average'])->toBe(90.0);
    expect($ahmad['status'])->toBe('Dinilai');

    $budi = collect($classA['rows'])->firstWhere('name', 'Budi');
    expect($budi['average'])->toBe(70.0);
    expect($budi['status'])->toBe('Dinilai'); // karena dalam bab Wudhu hanya ada 1 materi dan sudah dinilai
});

test('averageStats menghitung ringkasan statistik berbobot secara tepat', function () {
    $rows = $this->recapService->getRows(new RecapFilter(academicYearId: $this->year->id));
    $groups = $this->grouper->groupAverageRows($rows);
    $allAveragedRows = [];
    foreach ($groups as $group) {
        foreach ($group['classes'] as $cls) {
            foreach ($cls['rows'] as $r) {
                $allAveragedRows[] = $r;
            }
        }
    }

    $stats = $this->grouper->averageStats($allAveragedRows);
    expect($stats['scored'])->toBe(3); // Ahmad m1 & m2, Budi m1
    expect($stats['total'])->toBe(240.0); // 90 + 80 + 70
    expect($stats['average'])->not->toBeNull();
});

test('formatScope menghasilkan deskripsi cakupan yang tepat', function () {
    $filter = new RecapFilter(
        academicYearId: $this->year->id,
        classId: $this->classA->id,
        chapter: 'Wudhu'
    );

    $scope = $this->grouper->formatScope($filter);
    expect($scope)->toContain('2026/2027 · Ganjil');
    expect($scope)->toContain('Kelas: VII A');
    expect($scope)->toContain('Bab: Wudhu');
});
