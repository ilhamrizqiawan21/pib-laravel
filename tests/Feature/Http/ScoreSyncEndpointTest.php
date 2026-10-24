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
    $this->user = User::factory()->create();
    $year = AcademicYear::factory()->create();
    $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
    $template = CurriculumTemplate::factory()->create(['academic_year_id' => $year->id]);
    $chapter = Chapter::factory()->create(['curriculum_template_id' => $template->id]);
    $subchapter = Subchapter::factory()->create(['chapter_id' => $chapter->id]);
    $this->student = Student::factory()->create(['school_class_id' => $class->id]);
    $this->assessment = Assessment::factory()->create(['subchapter_id' => $subchapter->id]);
});

test('tamu ditolak saat mengakses POST /penilaian/sync', function () {
    $this->postJson('/penilaian/sync', [
        'id' => 'op-12345678',
        'device_id' => 'dev-1',
        'student_id' => $this->student->id,
        'assessment_id' => $this->assessment->id,
        'mistakes' => 5,
    ])->assertUnauthorized();
});

test('menyimpan nilai berhasil mengembalikan respons 200 SYNCED', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/penilaian/sync', [
            'id' => 'op-test-1234',
            'device_id' => 'dev-1',
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'mistakes' => 4,
            'base_version' => null,
            'initials' => 'IR',
            'note' => 'Bagus',
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'SYNCED',
            'score' => 86,
            'mistakes' => 4,
            'version' => 1,
        ])
        ->assertJsonStructure(['status', 'score', 'mistakes', 'version', 'assessed_at']);

    expect(Score::where('student_id', $this->student->id)->where('assessment_id', $this->assessment->id)->value('score'))->toBe(86);
});

test('respons simpan nilai mengembalikan paraf dan catatan untuk memperbarui layar', function () {
    $this->actingAs($this->user)
        ->postJson('/penilaian/sync', [
            'id' => 'op-metadata-response',
            'device_id' => 'dev-1',
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'mistakes' => 2,
            'base_version' => null,
            'initials' => 'IR',
            'note' => 'Bacaan lancar',
        ])
        ->assertOk()
        ->assertJsonPath('initials', 'IR')
        ->assertJsonPath('note', 'Bacaan lancar');
});

test('idempoten ketika mengirim op id yang sama berkali-kali', function () {
    $payload = [
        'id' => 'op-idempotent-request',
        'device_id' => 'dev-1',
        'student_id' => $this->student->id,
        'assessment_id' => $this->assessment->id,
        'mistakes' => 4,
        'base_version' => null,
    ];

    $res1 = $this->actingAs($this->user)->postJson('/penilaian/sync', $payload);
    $res1->assertOk();

    $res2 = $this->actingAs($this->user)->postJson('/penilaian/sync', $payload);
    $res2->assertOk();
    expect($res2->json('version'))->toBe($res1->json('version'));
});

test('mengembalikan 409 Conflict bila base_version tidak cocok dengan versi server', function () {
    // Nilai awal versi 1
    Score::factory()->create([
        'student_id' => $this->student->id,
        'assessment_id' => $this->assessment->id,
        'mistakes' => 5,
        'score' => 85,
        'version' => 1,
    ]);

    // Request dengan base_version = null (klien mengira belum ada nilai)
    $response = $this->actingAs($this->user)
        ->postJson('/penilaian/sync', [
            'id' => 'op-conflict-test',
            'device_id' => 'dev-2',
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'mistakes' => 3,
            'base_version' => null,
        ]);

    $response->assertStatus(409)
        ->assertJsonStructure([
            'error',
            'current' => ['score', 'mistakes', 'version'],
        ])
        ->assertJsonPath('current.version', 1)
        ->assertJsonPath('current.score', 85);
});

test('mengembalikan 404 bila siswa atau materi tidak ditemukan', function () {
    $this->actingAs($this->user)
        ->postJson('/penilaian/sync', [
            'id' => 'op-not-found',
            'device_id' => 'dev-1',
            'student_id' => 999999,
            'assessment_id' => $this->assessment->id,
            'mistakes' => 0,
        ])
        ->assertNotFound();
});

test('menolak nilai untuk materi dari tahun ajaran siswa yang berbeda', function () {
    $otherAssessment = Assessment::factory()->create();

    $this->actingAs($this->user)
        ->postJson('/penilaian/sync', [
            'id' => 'op-cross-year-test',
            'device_id' => 'dev-1',
            'student_id' => $this->student->id,
            'assessment_id' => $otherAssessment->id,
            'mistakes' => 1,
            'base_version' => null,
        ])
        ->assertNotFound();

    expect(Score::query()->count())->toBe(0);
});

test('mengembalikan 422 bila payload tidak valid', function () {
    $this->actingAs($this->user)
        ->postJson('/penilaian/sync', [
            'id' => 'short', // kurang dari 8 karakter
            'device_id' => 'dev-1',
            'student_id' => $this->student->id,
            'assessment_id' => $this->assessment->id,
            'mistakes' => 120, // lebih dari 90
        ])
        ->assertUnprocessable();
});
