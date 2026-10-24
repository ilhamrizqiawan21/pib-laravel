<div>
    <x-slot:title>Pengaturan</x-slot:title>

    <x-page-header
        eyebrow="PENGATURAN"
        title="Pengaturan"
        description="Kelola profil pengguna, identitas sekolah, dan keamanan akun."
    />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <x-card title="Profil" description="Informasi ini digunakan untuk sapaan dan identitas laporan.">
            @if ($statusMessage)
                <div class="mb-5">
                    <x-alert variant="success">{{ $statusMessage }}</x-alert>
                </div>
            @endif

            <form wire:submit="updateProfile" class="space-y-4">
                <div class="flex items-center gap-4">
                    @if ($profile_photo)
                        <img src="{{ $profile_photo->temporaryUrl() }}" alt="Pratinjau foto profil" class="h-16 w-16 rounded-full object-cover" />
                    @elseif (auth()->user()?->profile_photo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) }}" alt="Foto profil {{ auth()->user()->name }}" class="h-16 w-16 rounded-full object-cover" />
                    @else
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-soft text-xl font-bold text-brand-strong">
                            {{ substr($name ?: 'U', 0, 1) }}
                        </div>
                    @endif
                    <div>
                        <label for="profile_photo" class="inline-flex cursor-pointer rounded-lg border border-line bg-surface px-3 py-2 text-xs font-semibold text-ink hover:bg-canvas">
                            Pilih foto profil
                        </label>
                        <input id="profile_photo" type="file" wire:model="profile_photo" accept="image/jpeg,image/png,image/webp" class="sr-only" />
                        <p class="mt-1 text-[11px] text-muted">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                        @error('profile_photo') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                <x-field label="Nama Pengguna" id="name" required :error="$errors->first('name')">
                    <x-input id="name" wire:model="name" required autocomplete="name" :has-error="$errors->has('name')" />
                </x-field>

                <x-field label="Email" id="email" required :error="$errors->first('email')">
                    <x-input type="email" id="email" wire:model="email" required autocomplete="email" :has-error="$errors->has('email')" />
                </x-field>

                <x-field label="Nama Sekolah" id="school_name" help="Maksimal 150 karakter." :error="$errors->first('school_name')">
                    <x-input id="school_name" wire:model="school_name" :has-error="$errors->has('school_name')" />
                </x-field>

                <x-button type="submit" variant="primary" wire:loading.attr="disabled">Simpan Profil</x-button>
            </form>
        </x-card>

        <x-card title="Ubah Kata Sandi" description="Pastikan kata sandi Anda minimal 10 karakter untuk keamanan akun.">
            <form wire:submit="updatePassword" class="space-y-4">
                <x-field label="Kata Sandi Saat Ini" id="current_password" required :error="$errors->first('current_password')">
                    <x-input type="password" id="current_password" wire:model="current_password" required autocomplete="current-password" :has-error="$errors->has('current_password')" />
                </x-field>

                <x-field label="Kata Sandi Baru" id="password" required help="Minimal 10 karakter." :error="$errors->first('password')">
                    <x-input type="password" id="password" wire:model="password" required autocomplete="new-password" :has-error="$errors->has('password')" />
                </x-field>

                <x-field label="Ulangi Kata Sandi Baru" id="password_confirmation" required :error="$errors->first('password_confirmation')">
                    <x-input type="password" id="password_confirmation" wire:model="password_confirmation" required autocomplete="new-password" :has-error="$errors->has('password_confirmation')" />
                </x-field>

                <x-button type="submit" variant="primary" wire:loading.attr="disabled">Perbarui Kata Sandi</x-button>
            </form>
        </x-card>
    </div>
</div>
