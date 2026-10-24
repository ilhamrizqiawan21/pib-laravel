<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Support\Facades\Hash;

test('perintah pib:create-admin dapat membuat admin secara non-interaktif', function () {
    $this->artisan('pib:create-admin', [
        '--name' => 'Super Admin',
        '--email' => 'superadmin@example.com',
        '--password' => 'secret12345',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'superadmin@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Super Admin');
    expect($user->role)->toBe(Role::Admin);
    expect($user->is_active)->toBeTrue();
    expect(Hash::check('secret12345', $user->password))->toBeTrue();
});

test('perintah pib:create-admin menolak password kurang dari 10 karakter', function () {
    $this->artisan('pib:create-admin', [
        '--name' => 'Admin Pendek',
        '--email' => 'pendek@example.com',
        '--password' => 'short',
    ])->assertFailed();

    expect(User::query()->where('email', 'pendek@example.com')->exists())->toBeFalse();
});

test('perintah pib:create-admin idempoten dan tidak menggandakan email', function () {
    $this->artisan('pib:create-admin', [
        '--name' => 'Admin Pertama',
        '--email' => 'admin@example.com',
        '--password' => 'passwordLama10',
    ])->assertSuccessful();

    $this->artisan('pib:create-admin', [
        '--name' => 'Admin Baru',
        '--email' => 'admin@example.com',
        '--password' => 'passwordBaru10',
    ])->assertSuccessful();

    expect(User::query()->where('email', 'admin@example.com')->count())->toBe(1);

    $user = User::query()->where('email', 'admin@example.com')->first();
    expect($user->name)->toBe('Admin Baru');
    expect(Hash::check('passwordBaru10', $user->password))->toBeTrue();
});

test('admin seeder membaca konfigurasi dan membuat admin', function () {
    config()->set('pib.admin.email', 'seederadmin@example.com');
    config()->set('pib.admin.name', 'Seeder Admin');
    config()->set('pib.admin.password', 'seederpassword10');

    $this->seed(AdminSeeder::class);

    $user = User::query()->where('email', 'seederadmin@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Seeder Admin');
    expect($user->role)->toBe(Role::Admin);
});

test('admin seeder menolak password bawaan kosong atau terlalu pendek', function (mixed $password) {
    config()->set('pib.admin.password', $password);

    expect(fn () => $this->seed(AdminSeeder::class))
        ->toThrow(RuntimeException::class, 'PIB_ADMIN_PASSWORD wajib diisi dan minimal 10 karakter');

    expect(User::query()->where('email', 'admin@example.com')->exists())->toBeFalse();
})->with([null, '', 'pendek']);
