<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pib:create-admin
                            {--name= : Nama lengkap administrator}
                            {--email= : Alamat email administrator}
                            {--password= : Kata sandi administrator (minimal 10 karakter)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat atau memperbarui akun administrator PIB';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string|null $name */
        $name = $this->option('name');
        /** @var string|null $email */
        $email = $this->option('email');
        /** @var string|null $password */
        $password = $this->option('password');

        if (! $name) {
            $name = (string) $this->ask('Nama lengkap administrator', 'Administrator');
        }

        if (! $email) {
            $email = (string) $this->ask('Alamat email administrator', 'admin@example.com');
        }

        if (! $password) {
            $password = (string) $this->secret('Kata sandi (minimal 10 karakter)');
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:10'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
                'role' => Role::Admin,
                'is_active' => true,
            ]);

            $this->info("Akun administrator {$email} berhasil diperbarui.");
        } else {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => Role::Admin,
                'is_active' => true,
            ]);

            $this->info("Akun administrator {$email} berhasil dibuat.");
        }

        return self::SUCCESS;
    }
}
