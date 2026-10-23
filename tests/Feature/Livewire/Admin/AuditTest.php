<?php

use App\Enums\Role;
use App\Livewire\Admin\Audit;
use App\Models\User;
use App\Services\AuditLogger;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Utama',
        'role' => Role::Admin,
        'is_active' => true,
    ]);

    $this->teacher = User::factory()->create([
        'name' => 'Guru PAI',
        'role' => Role::Teacher,
        'is_active' => true,
    ]);

    $logger = app(AuditLogger::class);

    // Buat beberapa entri audit log
    $logger->record(
        entity: 'score',
        entityId: '10',
        action: 'UPSERT',
        summary: 'Penilaian materi Al-Fatihah siswa Ani',
        meta: ['score' => 90, 'mistakes' => 0],
        userId: $this->teacher->id
    );

    $logger->record(
        entity: 'backup',
        entityId: 'local',
        action: 'SNAPSHOT',
        summary: 'Backup otomatis dibuat',
        meta: null,
        userId: $this->admin->id
    );

    $logger->record(
        entity: 'user',
        entityId: '2',
        action: 'CREATE',
        summary: 'Pengguna baru didaftarkan',
        meta: null,
        userId: $this->admin->id
    );
});

test('tamu dan guru dilarang mengakses halaman admin audit', function () {
    $this->get('/admin/audit')->assertRedirect('/login');

    $this->actingAs($this->teacher)
        ->get('/admin/audit')
        ->assertForbidden();
});

test('admin dapat mengakses halaman audit dan melihat riwayat aktivitas', function () {
    $this->actingAs($this->admin)
        ->get('/admin/audit')
        ->assertOk()
        ->assertSee('Riwayat Audit')
        ->assertSee('Penilaian materi Al-Fatihah siswa Ani')
        ->assertSee('Backup otomatis dibuat');
});

test('admin dapat memfilter audit log berdasarkan entitas dan aksi', function () {
    Livewire::actingAs($this->admin)
        ->test(Audit::class)
        ->set('entity', 'score')
        ->assertSee('Penilaian materi Al-Fatihah siswa Ani')
        ->assertDontSee('Backup otomatis dibuat')
        ->set('entity', '')
        ->set('action', 'SNAPSHOT')
        ->assertSee('Backup otomatis dibuat')
        ->assertDontSee('Penilaian materi Al-Fatihah siswa Ani');
});

test('admin dapat mencari audit log berdasarkan kata kunci', function () {
    Livewire::actingAs($this->admin)
        ->test(Audit::class)
        ->set('search', 'Al-Fatihah')
        ->assertSee('Penilaian materi Al-Fatihah siswa Ani')
        ->assertDontSee('Backup otomatis dibuat');
});
