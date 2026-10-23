<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var string $name */
        $name = config('pib.admin.name', 'Administrator');
        /** @var string $email */
        $email = config('pib.admin.email', 'admin@example.com');
        /** @var string|null $password */
        $password = config('pib.admin.password');

        if (blank($password)) {
            $password = 'Admin12345!';
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => Role::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );
    }
}
