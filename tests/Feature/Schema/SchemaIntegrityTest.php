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
use App\Services\AuditLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Kontrak integritas data (docs/SCHEMA.md). SQLite perlu foreign key aktif; Laravel mengaktifkannya default.

it('satu nilai per siswa per materi', function () {
    $score = Score::factory()->create();

    expect(fn () => Score::factory()->create([
        'student_id' => $score->student_id,
        'assessment_id' => $score->assessment_id,
    ]))->toThrow(QueryException::class);
});

it('nilai boleh kosong tanpa menghapus baris (versi tetap ada)', function () {
    $score = Score::factory()->cleared()->create();

    expect($score->fresh())
        ->mistakes->toBeNull()
        ->score->toBeNull()
        ->version->toBe(2);
});

it('materi yang sudah bernilai tidak bisa terhapus lewat cascade kurikulum', function () {
    $score = Score::factory()->create();
    $template = $score->assessment->subchapter->chapter->curriculumTemplate;

    expect(fn () => $template->delete())->toThrow(QueryException::class);
    expect(Score::count())->toBe(1);
});

it('struktur kurikulum tanpa nilai terhapus berantai', function () {
    $assessment = Assessment::factory()->create();
    $template = $assessment->subchapter->chapter->curriculumTemplate;

    $template->delete();

    expect(Chapter::count())->toBe(0)
        ->and(Subchapter::count())->toBe(0)
        ->and(Assessment::count())->toBe(0);
});

it('menghapus siswa ikut menghapus nilainya', function () {
    $score = Score::factory()->create();

    $score->student->delete();

    expect(Score::count())->toBe(0);
});

it('tahun ajaran dan kelas yang dipakai tidak bisa dihapus', function () {
    $student = Student::factory()->create();

    expect(fn () => $student->schoolClass->delete())->toThrow(QueryException::class);
    expect(fn () => $student->schoolClass->academicYear->delete())->toThrow(QueryException::class);
});

it('NIS unik tetapi banyak siswa boleh tanpa NIS', function () {
    Student::factory()->create(['nis' => '1001']);
    expect(fn () => Student::factory()->create(['nis' => '1001']))->toThrow(QueryException::class);

    Student::factory()->count(2)->create(['nis' => null]);
    expect(Student::whereNull('nis')->count())->toBe(2);
});

it('tahun ajaran + semester unik, nama kelas unik per tahun ajaran', function () {
    $year = AcademicYear::factory()->create(['name' => '2026/2027']);
    expect(fn () => AcademicYear::factory()->create(['name' => '2026/2027']))->toThrow(QueryException::class);
    AcademicYear::factory()->genap()->create(['name' => '2026/2027']); // semester berbeda → boleh

    SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => '7A']);
    expect(fn () => SchoolClass::factory()->create(['academic_year_id' => $year->id, 'name' => '7A']))->toThrow(QueryException::class);
});

it('nama bab unik per template', function () {
    $template = CurriculumTemplate::factory()->create();
    Chapter::factory()->create(['curriculum_template_id' => $template->id, 'title' => 'Shalat']);
    expect(fn () => Chapter::factory()->create(['curriculum_template_id' => $template->id, 'title' => 'Shalat']))->toThrow(QueryException::class);
});

it('menghapus penilai tidak menghapus nilai (assessor_id jadi NULL)', function () {
    $user = User::factory()->create();
    $score = Score::factory()->create(['assessor_id' => $user->id]);

    $user->delete();

    expect($score->fresh()->assessor_id)->toBeNull();
});

it('audit log menyimpan meta sebagai array', function () {
    $log = app(AuditLogger::class)->record('score', '1:2', 'UPSERT', 'Perubahan nilai', ['mistakes' => 3]);

    expect($log->fresh()->meta)->toBe(['mistakes' => 3]);
});

it('enum tercast dari kolom string', function () {
    $user = User::factory()->admin()->create();

    expect($user->isAdmin())->toBeTrue()
        ->and(AcademicYear::factory()->create()->semester->value)->toBe('Ganjil');
});

it('tabel inti ada', function () {
    foreach (['users', 'academic_years', 'school_classes', 'students', 'curriculum_templates', 'chapters', 'subchapters', 'assessments', 'scores', 'settings', 'audit_logs', 'sync_operations'] as $table) {
        expect(DB::getSchemaBuilder()->hasTable($table))->toBeTrue("tabel {$table} tidak ada");
    }
});
