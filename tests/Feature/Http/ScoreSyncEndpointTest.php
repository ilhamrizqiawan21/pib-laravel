<?php

use App\Models\Assessment;
use App\Models\Score;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->student = Student::factory()->create();
    $this->assessment = Assessment::factory()->create();
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
