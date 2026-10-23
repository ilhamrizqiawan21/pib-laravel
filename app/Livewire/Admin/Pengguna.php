<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Admin Pengguna'])]
class Pengguna extends Component
{
    use WithPagination;

    public string $search = '';

    // Form Tambah Pengguna
    public string $name = '';

    public string $email = '';

    public string $role = 'teacher';

    public string $password = '';

    public string $password_confirmation = '';

    // Form Reset Password
    public ?int $resetUserId = null;

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public bool $showCreateModal = false;

    public bool $showResetModal = false;

    public function createUser(AuditLogger $auditLogger): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['teacher', 'admin'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => Role::from($validated['role']),
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $auditLogger->record(
            entity: 'user',
            entityId: $user->id,
            action: 'CREATE',
            summary: "Pengguna {$user->name} ({$user->email}) dibuat dengan peran {$user->role->value}"
        );

        $this->reset(['name', 'email', 'role', 'password', 'password_confirmation', 'showCreateModal']);
        session()->flash('message', 'Pengguna berhasil dibuat.');
    }

    public function toggleActive(int $userId, AuditLogger $auditLogger): void
    {
        /** @var User|null $user */
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        // Jika admin aktif akan dinonaktifkan, periksa apakah dia admin terakhir yang aktif
        if ($user->role === Role::Admin && $user->is_active) {
            $activeAdminCount = User::where('role', Role::Admin)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                $this->addError('action', 'Admin terakhir yang aktif tidak dapat dinonaktifkan.');

                return;
            }
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        $auditLogger->record(
            entity: 'user',
            entityId: $user->id,
            action: 'STATUS_CHANGE',
            summary: "Pengguna {$user->name} {$statusText}"
        );

        session()->flash('message', "Pengguna {$user->name} berhasil {$statusText}.");
    }

    public function changeRole(int $userId, string $newRole, AuditLogger $auditLogger): void
    {
        if (! in_array($newRole, ['teacher', 'admin'], true)) {
            return;
        }

        /** @var User|null $user */
        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $targetRole = Role::from($newRole);
        if ($user->role === $targetRole) {
            return;
        }

        // Jika menurunkan admin menjadi guru, pastikan ada admin aktif lain
        if ($user->role === Role::Admin && $targetRole === Role::Teacher) {
            $activeAdminCount = User::where('role', Role::Admin)->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                $this->addError('action', 'Admin terakhir tidak dapat diturunkan perannya.');

                return;
            }
        }

        $user->role = $targetRole;
        $user->save();

        $auditLogger->record(
            entity: 'user',
            entityId: $user->id,
            action: 'ROLE_CHANGE',
            summary: "Peran pengguna {$user->name} diubah menjadi {$newRole}"
        );

        session()->flash('message', "Peran {$user->name} berhasil diubah.");
    }

    public function openResetModal(int $userId): void
    {
        $this->resetUserId = $userId;
        $this->newPassword = '';
        $this->newPassword_confirmation = '';
        $this->showResetModal = true;
    }

    public function resetPassword(AuditLogger $auditLogger): void
    {
        $this->validate([
            'resetUserId' => ['required', 'exists:users,id'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'newPassword.required' => 'Password baru wajib diisi.',
            'newPassword.min' => 'Password baru minimal 8 karakter.',
            'newPassword.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        /** @var User|null $user */
        $user = User::find($this->resetUserId);
        if (! $user) {
            return;
        }

        $user->password = Hash::make($this->newPassword);
        $user->save();

        $auditLogger->record(
            entity: 'user',
            entityId: $user->id,
            action: 'RESET_PASSWORD',
            summary: "Password pengguna {$user->name} berhasil direset oleh administrator"
        );

        $this->reset(['resetUserId', 'newPassword', 'newPassword_confirmation', 'showResetModal']);
        session()->flash('message', "Password pengguna {$user->name} berhasil direset.");
    }

    public function render(): View
    {
        $query = User::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'LIKE', '%'.$this->search.'%')
                    ->orWhere('email', 'LIKE', '%'.$this->search.'%');
            });
        }

        $users = $query->orderByDesc('role')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.admin.pengguna', [
            'users' => $users,
        ]);
    }
}
