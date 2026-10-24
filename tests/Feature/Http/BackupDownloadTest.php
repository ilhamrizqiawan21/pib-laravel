<?php

use App\Models\AuditLog;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

beforeEach(function () {
    $this->teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
    $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    $this->class = SchoolClass::factory()->create(['name' => 'VII A']);
    $this->student = Student::factory()->create(['school_class_id' => $this->class->id, 'name' => 'Budi']);
});

test('tamu tidak dapat mengunduh backup atau melihat status', function () {
    $this->get('/backup/unduh')->assertRedirect('/login');
    $this->get('/backup/status')->assertRedirect('/login');
});

test('guru dapat melihat status backup dan status berisi informasi yang valid', function () {
    $response = $this->actingAs($this->teacher)->getJson('/backup/status');

    $response->assertOk()
        ->assertJsonStructure([
            'enabled',
            'count',
            'interval_hours',
            'keep',
        ])
        ->assertJsonMissingPath('dir');
});

test('guru dapat mengunduh backup json lengkap dan tercatat di audit log', function () {
    $response = $this->actingAs($this->teacher)->get('/backup/unduh');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
    $response->assertHeader('Content-Disposition');

    $content = json_decode($response->streamedContent(), true);
    expect($content)->toBeArray();
    expect($content['app'])->toBe('pib-penilaian');
    expect($content['data'])->toHaveKeys(['students', 'school_classes', 'users']);
    expect(count($content['data']['students']))->toBeGreaterThan(0);

    // Verifikasi audit log tercatat
    $lastAudit = AuditLog::query()->latest('id')->first();
    expect($lastAudit)->not->toBeNull();
    expect($lastAudit->entity)->toBe('backup');
    expect($lastAudit->action)->toBe('DOWNLOAD');
});

test('guru dapat memicu snapshot manual', function () {
    $response = $this->actingAs($this->teacher)->postJson('/backup/manual');

    $response->assertStatus(201)
        ->assertJson(['enabled' => true]);

    $lastAudit = AuditLog::query()->latest('id')->first();
    expect($lastAudit->action)->toBe('SNAPSHOT');
});
