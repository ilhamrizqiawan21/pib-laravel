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
use App\Repositories\SettingsRepository;
use App\Services\AuditLogger;
use App\Services\DashboardService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->service = app(DashboardService::class);
    $this->settings = app(SettingsRepository::class);
});

test('checklist onboarding mendeteksi kelengkapan langkah awal', function () {
    // Awal: belum ada data sekolah
    $setup = $this->service->getSetupSteps();
    expect($setup['isComplete'])->toBeFalse();
    expect($setup['steps'][0]['done'])->toBeFalse(); // Identitas sekolah

    // Isi nama sekolah
    $this->settings->setMany(['school_name' => 'SMP Al-Hikmah']);
    $setup = $this->service->getSetupSteps();
    expect($setup['steps'][0]['done'])->toBeTrue();

    // Buat tahun ajaran, kelas, siswa, materi, dan nilai
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
    $student = Student::factory()->create(['school_class_id' => $class->id]);

    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id]);
    $sub = Subchapter::factory()->create(['chapter_id' => $chapter->id]);
    $assessment = Assessment::factory()->create(['subchapter_id' => $sub->id]);

    Score::factory()->create(['student_id' => $student->id, 'assessment_id' => $assessment->id, 'score' => 85]);

    $setup = $this->service->getSetupSteps();
    expect($setup['isComplete'])->toBeTrue();
    expect($setup['steps'][1]['done'])->toBeTrue(); // Tahun ajaran
    expect($setup['steps'][2]['done'])->toBeTrue(); // Kelas
    expect($setup['steps'][3]['done'])->toBeTrue(); // Siswa
    expect($setup['steps'][4]['done'])->toBeTrue(); // Materi
    expect($setup['steps'][5]['done'])->toBeTrue(); // Nilai
});

test('service menghitung kemajuan kelas dan memprioritaskan kelas dengan progres terendah', function () {
    $year = AcademicYear::factory()->create(['is_active' => true]);
    $classA = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => '7A']);
    $classB = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => '7B']);

    $studentA = Student::factory()->create(['school_class_id' => $classA->id]);
    $studentB = Student::factory()->create(['school_class_id' => $classB->id]);

    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id]);
    $sub = Subchapter::factory()->create(['chapter_id' => $chapter->id]);
    $materi = Assessment::factory()->create(['subchapter_id' => $sub->id]);

    // Class A sudah dinilai (100%), Class B belum dinilai (0%)
    Score::factory()->create(['student_id' => $studentA->id, 'assessment_id' => $materi->id, 'score' => 90]);

    $priority = $this->service->getPriorityClasses($year->id);
    expect($priority)->toHaveCount(1);
    expect($priority[0]['id'])->toBe($classB->id);
    expect($priority[0]['percent'])->toBe(0);

    $summary = $this->service->getSummary($year->id);
    expect($summary['total_students'])->toBe(2);
    expect($summary['total_classes'])->toBe(2);
    expect($summary['total_assessed'])->toBe(1);
    expect($summary['total_expected'])->toBe(2);
    expect($summary['overall_percent'])->toBe(50);
});

test('service mengambil 5 aktivitas audit log terbaru', function () {
    $user = User::factory()->create();

    app(AuditLogger::class)->record(
        entity: 'score',
        entityId: '1',
        action: 'UPSERT',
        summary: 'Menilai materi Shalat Subuh',
        userId: $user->id
    );

    $activities = $this->service->getRecentActivities(5);
    expect($activities)->not->toBeEmpty();
    expect($activities[0]->summary)->toBe('Menilai materi Shalat Subuh');
});

test('progres kelas mengabaikan nilai materi periode lain tanpa query per kelas', function () {
    $year = AcademicYear::factory()->active()->create();
    $otherYear = AcademicYear::factory()->create();
    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id]);
    $subchapter = Subchapter::factory()->create(['chapter_id' => $chapter->id]);
    Assessment::factory()->create(['subchapter_id' => $subchapter->id]);
    $otherTemplate = CurriculumTemplate::factory()->create(['academic_year_id' => $otherYear->id]);
    $otherChapter = Chapter::factory()->create(['curriculum_template_id' => $otherTemplate->id]);
    $otherSubchapter = Subchapter::factory()->create(['chapter_id' => $otherChapter->id]);
    $otherAssessment = Assessment::factory()->create(['subchapter_id' => $otherSubchapter->id]);

    foreach (range(1, 5) as $index) {
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => 'VII '.$index]);
        $student = Student::factory()->create(['school_class_id' => $class->id]);
        Score::factory()->create(['student_id' => $student->id, 'assessment_id' => $otherAssessment->id]);
    }

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $rows = $this->service->getClassProgress($year->id);

    expect($rows)->toHaveCount(5);
    expect(array_sum(array_column($rows, 'assessed_count')))->toBe(0);
    expect($queries)->toBeLessThanOrEqual(5);
});
