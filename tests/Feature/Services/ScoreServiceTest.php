<?php

use App\Enums\SyncStatus;
use App\Exceptions\ScoreConflictException;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AuditLog;
use App\Models\Chapter;
use App\Models\CurriculumTemplate;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subchapter;
use App\Models\SyncOperation;
use App\Models\User;
use App\Services\ScoreService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->service = app(ScoreService::class);
    $this->user = User::factory()->create();
    $year = AcademicYear::factory()->create();
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id]);
    $subchapter = Subchapter::factory()->create(['chapter_id' => $chapter->id]);
    $this->student = Student::factory()->create(['school_class_id' => $class->id]);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $subchapter->id]);
});

test('merekam nilai baru menghitung score dan menaikkan versi menjadi 1', function () {
    $opId = 'op-12345678';
    $deviceId = 'device-abc';

    $score = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 5,
        baseVersion: null,
        opId: $opId,
        deviceId: $deviceId,
        extras: ['initials' => 'IR', 'note' => 'Bacaan lancar']
    );

    expect($score)->not->toBeNull();
    expect($score->mistakes)->toBe(5);
    expect($score->score)->toBe(85); // 90 - 5 = 85
    expect($score->version)->toBe(1);
    expect($score->assessor_id)->toBe($this->user->id);
    expect($score->initials)->toBe('IR');
    expect($score->note)->toBe('Bacaan lancar');
    expect($score->assessed_at)->not->toBeNull();

    // Verifikasi sync_operation
    $syncOp = SyncOperation::find($opId);
    expect($syncOp)->not->toBeNull();
    expect($syncOp->status)->toBe(SyncStatus::Synced);

    // Verifikasi audit log
    expect(AuditLog::where('action', 'UPSERT')->where('entity', 'score')->exists())->toBeTrue();
});

test('memperbarui nilai yang ada menaikkan versi', function () {
    $firstScore = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 10,
        baseVersion: null,
        opId: 'op-step-1',
        deviceId: 'device-abc'
    );

    expect($firstScore->version)->toBe(1);
    expect($firstScore->score)->toBe(80);

    $updatedScore = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 2,
        baseVersion: 1,
        opId: 'op-step-2',
        deviceId: 'device-abc'
    );

    expect($updatedScore->version)->toBe(2);
    expect($updatedScore->score)->toBe(88);
    expect($updatedScore->mistakes)->toBe(2);
});

test('mengosongkan nilai memperbarui mistakes dan score menjadi null tetapi baris tetap ada', function () {
    $initialScore = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 0,
        baseVersion: null,
        opId: 'op-create',
        deviceId: 'device-abc'
    );

    expect($initialScore->score)->toBe(90);

    $clearedScore = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: null,
        baseVersion: 1,
        opId: 'op-clear',
        deviceId: 'device-abc'
    );

    expect($clearedScore)->not->toBeNull();
    expect($clearedScore->mistakes)->toBeNull();
    expect($clearedScore->score)->toBeNull();
    expect($clearedScore->assessed_at)->toBeNull();
    expect($clearedScore->version)->toBe(2);

    expect(Score::where('student_id', $this->student->id)->where('assessment_id', $this->assessment->id)->exists())->toBeTrue();
    expect(AuditLog::where('action', 'CLEAR')->where('entity', 'score')->exists())->toBeTrue();
});

test('mengosongkan nilai yang belum pernah ada adalah no-op', function () {
    $score = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: null,
        baseVersion: null,
        opId: 'op-noop',
        deviceId: 'device-abc'
    );

    expect($score)->toBeNull();
    expect(Score::where('student_id', $this->student->id)->where('assessment_id', $this->assessment->id)->exists())->toBeFalse();
});

test('mendeteksi konflik versi optimistik ketika base version tidak cocok', function () {
    // Buat nilai dengan versi 1
    $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 10,
        baseVersion: null,
        opId: 'op-initial',
        deviceId: 'device-1'
    );

    // Klien lain mencoba update dengan baseVersion = null (mengira belum ada)
    expect(fn () => $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 5,
        baseVersion: null,
        opId: 'op-conflict-1',
        deviceId: 'device-2'
    ))->toThrow(ScoreConflictException::class);

    // Klien lain mencoba update dengan baseVersion lama yang sudah usang (misal versi 99)
    expect(fn () => $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 5,
        baseVersion: 99,
        opId: 'op-conflict-2',
        deviceId: 'device-2'
    ))->toThrow(ScoreConflictException::class);
});

test('operasi yang sama dengan op_id yang sudah SYNCED bersifat idempoten', function () {
    $opId = 'op-idempotent-test';

    $score1 = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 4,
        baseVersion: null,
        opId: $opId,
        deviceId: 'device-1'
    );

    $versionAfterFirst = $score1->version;
    $auditCountAfterFirst = AuditLog::count();

    // Panggil lagi dengan opId yang sama
    $score2 = $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 4,
        baseVersion: null,
        opId: $opId,
        deviceId: 'device-1'
    );

    expect($score2->id)->toBe($score1->id);
    expect($score2->version)->toBe($versionAfterFirst);
    // Tidak menambah audit log ganda
    expect(AuditLog::count())->toBe($auditCountAfterFirst);
});

test('menolak kesalahan di luar rentang 0 sampai 90', function () {
    expect(fn () => $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $this->assessment->id,
        mistakes: 95,
        baseVersion: null,
        opId: 'op-invalid',
        deviceId: 'device-1'
    ))->toThrow(InvalidArgumentException::class);
});

test('menolak siswa atau materi yang tidak ada', function () {
    expect(fn () => $this->service->record(
        user: $this->user,
        studentId: 999999,
        assessmentId: $this->assessment->id,
        mistakes: 0,
        baseVersion: null,
        opId: 'op-notfound',
        deviceId: 'device-1'
    ))->toThrow(ModelNotFoundException::class);
});

test('menolak pasangan siswa dan materi dari tahun ajaran berbeda', function () {
    $otherAssessment = Assessment::factory()->create();

    expect(fn () => $this->service->record(
        user: $this->user,
        studentId: $this->student->id,
        assessmentId: $otherAssessment->id,
        mistakes: 1,
        baseVersion: null,
        opId: 'op-other-year',
        deviceId: 'device-1'
    ))->toThrow(ModelNotFoundException::class);

    expect(Score::query()->count())->toBe(0);
});
