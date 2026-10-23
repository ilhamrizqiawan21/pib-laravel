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
use App\Services\ClassProgressService;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->year = AcademicYear::factory()->create(['is_active' => true]);
    $this->class = SchoolClass::factory()->create(['academic_year_id' => $this->year->id, 'name' => '7A']);
    $this->template = CurriculumTemplate::factory()->create(['academic_year_id' => $this->year->id]);

    $this->chapter = Chapter::factory()->create(['curriculum_template_id' => $this->template->id, 'display_order' => 1, 'title' => 'Bab Shalat']);
    $this->subchapter = Subchapter::factory()->create(['chapter_id' => $this->chapter->id, 'display_order' => 1, 'title' => 'Shalat Wajib']);

    $this->m1 = Assessment::factory()->create(['subchapter_id' => $this->subchapter->id, 'display_order' => 1, 'title' => 'Subuh', 'is_active' => true]);
    $this->m2 = Assessment::factory()->create(['subchapter_id' => $this->subchapter->id, 'display_order' => 2, 'title' => 'Dzuhur', 'is_active' => true]);
    $this->mInactive = Assessment::factory()->create(['subchapter_id' => $this->subchapter->id, 'display_order' => 3, 'title' => 'Ashar Nonaktif', 'is_active' => false]);

    $this->student1 = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Ahmad', 'is_active' => true]);
    $this->student2 = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Budi', 'is_active' => true]);
    $this->studentInactive = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Caca Nonaktif', 'is_active' => false]);

    // Ahmad punya nilai m1 (terisi) dan m2 (dikosongkan / mistakes & score null tapi baris ada)
    Score::factory()->create([
        'student_id' => $this->student1->id,
        'assessment_id' => $this->m1->id,
        'mistakes' => 2,
        'score' => 88,
        'version' => 1,
    ]);

    Score::factory()->create([
        'student_id' => $this->student1->id,
        'assessment_id' => $this->m2->id,
        'mistakes' => null,
        'score' => null,
        'version' => 2,
    ]);
});

test('service mengembalikan payload kemajuan kelas secara akurat tanpa N+1', function () {
    $service = app(ClassProgressService::class);

    DB::enableQueryLog();
    $progress = $service->getProgress($this->class);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Maksimal 3 query di dalam service (materials, students, scores)
    expect(count($queries))->toBeLessThanOrEqual(3);

    expect($progress['classId'])->toBe($this->class->id);
    expect($progress['className'])->toBe('7A');

    // Materials: hanya yang aktif
    expect($progress['materials'])->toHaveCount(2);
    expect($progress['materials'][0]['title'])->toBe('Subuh');
    expect($progress['materials'][1]['title'])->toBe('Dzuhur');

    // Students: hanya yang aktif, terurut nama
    expect($progress['students'])->toHaveCount(2);
    expect($progress['students'][0]['name'])->toBe('Ahmad');
    expect($progress['students'][1]['name'])->toBe('Budi');

    // Scores: termasuk baris yang nilainya null
    expect($progress['scores'])->toHaveCount(2);
    $score1 = collect($progress['scores'])->firstWhere('assessment_id', $this->m1->id);
    expect($score1['score'])->toBe(88);
    expect($score1['version'])->toBe(1);

    $score2 = collect($progress['scores'])->firstWhere('assessment_id', $this->m2->id);
    expect($score2['score'])->toBeNull();
    expect($score2['mistakes'])->toBeNull();
    expect($score2['version'])->toBe(2);
});

test('endpoint GET /penilaian/kelas/{class}/progress mengembalikan 200 JSON untuk pengguna terautentikasi', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson("/penilaian/kelas/{$this->class->id}/progress")
        ->assertOk()
        ->assertJsonStructure([
            'classId',
            'className',
            'materials' => [
                '*' => ['id', 'title', 'chapter_id', 'chapter', 'subchapter_id', 'subchapter'],
            ],
            'students' => [
                '*' => ['id', 'name', 'nis'],
            ],
            'scores' => [
                '*' => ['student_id', 'assessment_id', 'score', 'mistakes', 'version', 'assessed_at'],
            ],
        ])
        ->assertJsonPath('classId', $this->class->id)
        ->assertJsonPath('className', '7A');
});

test('endpoint GET /penilaian/kelas/{class}/progress menolak tamu dengan 401', function () {
    $this->getJson("/penilaian/kelas/{$this->class->id}/progress")
        ->assertUnauthorized();
});

test('endpoint GET /penilaian/kelas/{class}/progress mengembalikan 404 jika kelas tidak ada', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/penilaian/kelas/999999/progress')
        ->assertNotFound();
});
