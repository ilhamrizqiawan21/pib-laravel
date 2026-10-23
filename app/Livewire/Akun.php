<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;

#[Layout('layouts.app')]
class Akun extends Component
{
    #[Rule(['required', 'string', 'current_password'])]
    public string $current_password = '';

    #[Rule(['required', 'string', 'min:10', 'confirmed'])]
    public string $password = '';

    public string $password_confirmation = '';

    public ?string $statusMessage = null;

    public function updatePassword(AuditLogger $auditLogger): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        $auditLogger->record(
            entity: 'user',
            entityId: $user->id,
            action: 'UPDATE',
            summary: 'Ubah kata sandi',
            meta: [
                'field' => 'password',
                'ip' => request()->ip(),
            ],
            userId: $user->id
        );

        $this->reset(['current_password', 'password', 'password_confirmation']);

        $this->statusMessage = 'Kata sandi berhasil diperbarui.';
    }

    public function render(): View
    {
        return view('livewire.akun');
    }
}
