<div>
    <x-slot:title>Akun &amp; Kata Sandi</x-slot:title>

    <x-page-header
        eyebrow="PENGATURAN"
        title="Akun & Kata Sandi"
        description="Kelola informasi akun dan perbarui kata sandi Anda."
    />

    <div class="max-w-xl">
        <x-card title="Ubah Kata Sandi" description="Pastikan kata sandi Anda minimal 10 karakter untuk keamanan akun.">
            @if ($statusMessage)
                <div class="mb-5">
                    <x-alert variant="success">
                        {{ $statusMessage }}
                    </x-alert>
                </div>
            @endif

            <form wire:submit="updatePassword" class="space-y-4">
                <x-field label="Kata Sandi Saat Ini" id="current_password" required :error="$errors->first('current_password')">
                    <x-input
                        type="password"
                        id="current_password"
                        wire:model="current_password"
                        required
                        autocomplete="current-password"
                        :has-error="$errors->has('current_password')"
                    />
                </x-field>

                <x-field label="Kata Sandi Baru" id="password" required help="Minimal 10 karakter." :error="$errors->first('password')">
                    <x-input
                        type="password"
                        id="password"
                        wire:model="password"
                        required
                        autocomplete="new-password"
                        :has-error="$errors->has('password')"
                    />
                </x-field>

                <x-field label="Ulangi Kata Sandi Baru" id="password_confirmation" required :error="$errors->first('password_confirmation')">
                    <x-input
                        type="password"
                        id="password_confirmation"
                        wire:model="password_confirmation"
                        required
                        autocomplete="new-password"
                        :has-error="$errors->has('password_confirmation')"
                    />
                </x-field>

                <div class="pt-2">
                    <x-button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span>Perbarui Kata Sandi</span>
                    </x-button>
                </div>
            </form>
        </x-card>
    </div>
</div>

