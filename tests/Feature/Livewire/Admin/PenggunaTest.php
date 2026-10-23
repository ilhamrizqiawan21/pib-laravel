<?php

use App\Enums\Role;
use App\Livewire\Admin\Pengguna;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Utama',
        'email' => 'admin@sekolah.sch.id',
        'role' => Role::Admin,
        'is_active' => true,
    ]);

    $this->teacher = User::factory()->create([
        'name' => 'Guru PAI',
        'email' => 'guru@sekolah.sch.id',
        'role' => Role::Teacher,
        'is_active' => true,
    ]);
});

test('tamu dan guru dilarang mengakses halaman admin pengguna', function () {
    $this->get('/admin/pengguna')->assertRedirect('/login');

    $this->actingAs($this->teacher)
        ->get('/admin/pengguna')
        ->assertForbidden();
});

test('admin dapat mengakses halaman admin pengguna dan melihat tabel pengguna', function () {
    $this->actingAs($this->admin)
        ->get('/admin/pengguna')
        ->assertOk()
        ->assertSee('Admin Utama')
        ->assertSee('Guru PAI');
});

test('admin dapat membuat pengguna baru dengan validasi dan audit log', function () {
    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->set('name', 'Guru Baru')
        ->set('email', 'baru@sekolah.sch.id')
        ->set('role', 'teacher')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('createUser')
        ->assertHasNoErrors();

    $newUser = User::where('email', 'baru@sekolah.sch.id')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->name)->toBe('Guru Baru');
    expect($newUser->role)->toBe(Role::Teacher);
    expect($newUser->is_active)->toBeTrue();
    expect(Hash::check('password123', $newUser->password))->toBeTrue();

    // Verifikasi audit log
    $audit = AuditLog::where('entity', 'user')->where('action', 'CREATE')->first();
    expect($audit)->not->toBeNull();
});

test('admin tidak dapat membuat akun atau mereset password kurang dari sepuluh karakter', function () {
    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->set('name', 'Guru Baru')
        ->set('email', 'baru@sekolah.sch.id')
        ->set('password', '123456789')
        ->set('password_confirmation', '123456789')
        ->call('createUser')
        ->assertHasErrors(['password']);

    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->set('resetUserId', $this->teacher->id)
        ->set('newPassword', '123456789')
        ->set('newPassword_confirmation', '123456789')
        ->call('resetPassword')
        ->assertHasErrors(['newPassword']);
});

test('admin dapat menonaktifkan dan mengaktifkan kembali pengguna guru', function () {
    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->call('toggleActive', $this->teacher->id)
        ->assertHasNoErrors();

    expect($this->teacher->fresh()->is_active)->toBeFalse();

    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->call('toggleActive', $this->teacher->id)
        ->assertHasNoErrors();

    expect($this->teacher->fresh()->is_active)->toBeTrue();
});

test('admin terakhir tidak dapat dinonaktifkan', function () {
    // Hanya ada 1 admin ($this->admin)
    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->call('toggleActive', $this->admin->id)
        ->assertHasErrors(['action']);

    expect($this->admin->fresh()->is_active)->toBeTrue();
});

test('admin terakhir tidak dapat diturunkan perannya menjadi guru', function () {
    // Hanya ada 1 admin
    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->call('changeRole', $this->admin->id, 'teacher')
        ->assertHasErrors(['action']);

    expect($this->admin->fresh()->role)->toBe(Role::Admin);
});

test('admin dapat mengubah peran pengguna bila bukan admin terakhir', function () {
    // Buat admin kedua
    $secondAdmin = User::factory()->create([
        'role' => Role::Admin,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->call('changeRole', $secondAdmin->id, 'teacher')
        ->assertHasNoErrors();

    expect($secondAdmin->fresh()->role)->toBe(Role::Teacher);
});

test('admin dapat mereset password pengguna', function () {
    Livewire::actingAs($this->admin)
        ->test(Pengguna::class)
        ->set('resetUserId', $this->teacher->id)
        ->set('newPassword', 'rahasiaBaru123')
        ->set('newPassword_confirmation', 'rahasiaBaru123')
        ->call('resetPassword')
        ->assertHasNoErrors();

    expect(Hash::check('rahasiaBaru123', $this->teacher->fresh()->password))->toBeTrue();
});
