<?php

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\SettingsRepository;
use Illuminate\Support\Facades\Cache;

test('settings repository membaca nilai bawaan dan database dengan benar', function () {
    $repo = app(SettingsRepository::class);

    expect($repo->get('school_name', 'Sekolah Bawaan'))->toBe('Sekolah Bawaan');

    Setting::create(['key' => 'school_name', 'value' => 'SMP Islam Terpadu']);
    Cache::flush();

    expect($repo->get('school_name'))->toBe('SMP Islam Terpadu');
});

test('settings repository menyimpan banyak nilai dan menghapus cache', function () {
    $user = User::factory()->create();
    $repo = app(SettingsRepository::class);

    $repo->setMany([
        'school_name' => 'SMP Bina Insan Mandiri',
        'school_address' => 'Jl. Pendidikan No. 123',
        'teacher_name' => 'Ust. Abdullah',
    ], userId: $user->id);

    // Cek di database
    expect(Setting::query()->where('key', 'school_name')->value('value'))->toBe('SMP Bina Insan Mandiri');
    expect(Setting::query()->where('key', 'school_address')->value('value'))->toBe('Jl. Pendidikan No. 123');
    expect(Setting::query()->where('key', 'teacher_name')->value('value'))->toBe('Ust. Abdullah');

    // Cek pembacaan dari repository
    expect($repo->schoolName())->toBe('SMP Bina Insan Mandiri');
    expect($repo->schoolAddress())->toBe('Jl. Pendidikan No. 123');
    expect($repo->teacherName())->toBe('Ust. Abdullah');

    // Audit log harus tercatat
    $audit = AuditLog::query()
        ->where('entity', 'settings')
        ->where('action', 'UPDATE')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->summary)->toContain('identitas sekolah');
});
