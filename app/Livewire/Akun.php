<?php

namespace App\Livewire;

use App\Models\User;
use App\Repositories\SettingsRepository;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule as ValidationRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Akun extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public string $school_name = '';

    public ?UploadedFile $profile_photo = null;

    #[Rule(['required', 'string', 'current_password'])]
    public string $current_password = '';

    #[Rule(['required', 'string', 'min:10', 'confirmed'])]
    public string $password = '';

    public string $password_confirmation = '';

    public ?string $statusMessage = null;

    public function mount(SettingsRepository $settings): void
    {
        /** @var User $user */
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->school_name = $settings->schoolName();
    }

    public function updateProfile(SettingsRepository $settings, AuditLogger $auditLogger): void
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', ValidationRule::unique('users', 'email')->ignore($user->id)],
            'school_name' => ['nullable', 'string', 'max:150'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $newPhotoPath = null;
        if ($this->profile_photo instanceof UploadedFile) {
            $newPhotoPath = $this->profile_photo->store('profile-photos', 'public');
        }

        $oldPhotoPath = $user->profile_photo_path;
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'profile_photo_path' => $newPhotoPath ?? $oldPhotoPath,
        ]);

        $settings->setMany([
            'school_name' => $validated['school_name'] ?? '',
        ], userId: $user->id);

        if ($newPhotoPath !== null && $oldPhotoPath !== null) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $auditLogger->record(
            entity: 'user',
            entityId: $user->id,
            action: 'UPDATE',
            summary: 'Perbarui profil pengguna',
            meta: [
                'fields' => ['name', 'email', 'profile_photo'],
            ],
            userId: $user->id
        );

        $this->reset('profile_photo');
        $this->statusMessage = 'Profil berhasil diperbarui.';
    }

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
