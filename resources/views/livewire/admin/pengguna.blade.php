<div>
    <x-slot:title>Admin Pengguna</x-slot:title>

    <!-- Header Halaman -->
    <x-page-header
        eyebrow="ADMINISTRASI SISTEM"
        title="Admin Pengguna"
        description="Kelola akun guru dan admin, ubah peran, serta kontrol akses masuk."
    >
        <x-slot:actions>
            <x-button type="button" variant="primary" size="sm" wire:click="$set('showCreateModal', true)">
                <x-icon name="plus" size="16" />
                <span>Tambah Pengguna</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Alert / Notifikasi -->
    @if(session('message'))
        <div class="mb-6">
            <x-alert variant="success">
                {{ session('message') }}
            </x-alert>
        </div>
    @endif

    @error('action')
        <div class="mb-6">
            <x-alert variant="danger">
                {{ $message }}
            </x-alert>
        </div>
    @enderror

    <!-- Tabel Pengguna -->
    <x-card>
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="w-full max-w-xs">
                <input
                    type="search"
                    aria-label="Cari nama atau email pengguna"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau email..."
                    class="w-full text-xs rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
            </div>
        </div>

        <div class="overflow-x-auto border border-line rounded-xl">
            <table class="w-full text-left text-sm text-ink">
                <thead>
                    <tr class="border-b border-line bg-canvas/80 text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="px-4 py-3">Nama Pengguna</th>
                        <th class="px-4 py-3">Alamat Email</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($users as $user)
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-ink">
                                {{ $user->name }}
                                @if(auth()->id() === $user->id)
                                    <span class="ml-1 text-[10px] text-brand-strong font-semibold bg-brand-soft px-1.5 py-0.5 rounded">(Anda)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-muted font-mono text-xs">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @if($user->role === \App\Enums\Role::Admin)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Admin</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Guru</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium text-red-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="user-actions inline-flex items-center gap-2">
                                    <!-- Ubah Peran -->
                                    @if($user->role === \App\Enums\Role::Admin)
                                        <x-button type="button" variant="secondary" size="xs" wire:click="changeRole({{ $user->id }}, 'teacher')" class="user-action-button" title="Ubah menjadi guru">
                                            <x-icon name="user-cog" size="14" />
                                            <span>Jadikan Guru</span>
                                        </x-button>
                                    @else
                                        <x-button type="button" variant="secondary" size="xs" wire:click="changeRole({{ $user->id }}, 'admin')" class="user-action-button" title="Ubah menjadi administrator">
                                            <x-icon name="user-cog" size="14" />
                                            <span>Jadikan Admin</span>
                                        </x-button>
                                    @endif

                                    <!-- Reset Sandi -->
                                    <x-button type="button" variant="secondary" size="xs" wire:click="openResetModal({{ $user->id }})" class="user-action-button text-blue-700" title="Reset sandi pengguna">
                                        <x-icon name="key" size="14" />
                                        <span>Reset Sandi</span>
                                    </x-button>

                                    <!-- Nonaktifkan / Aktifkan -->
                                    @if($user->is_active)
                                        <x-button type="button" variant="danger-ghost" size="xs" wire:click="toggleActive({{ $user->id }})" class="user-action-button" title="Nonaktifkan pengguna">
                                            <x-icon name="ban" size="14" />
                                            <span>Nonaktifkan</span>
                                        </x-button>
                                    @else
                                        <x-button type="button" variant="secondary" size="xs" wire:click="toggleActive({{ $user->id }})" class="user-action-button text-emerald-700" title="Aktifkan pengguna">
                                            <x-icon name="check" size="14" />
                                            <span>Aktifkan</span>
                                        </x-button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-muted text-sm">
                                Tidak ada data pengguna yang sesuai pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-line mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>

    <!-- Modal Tambah Pengguna -->
    @if($showCreateModal)
        <div x-data="dialogFocus()" @keydown.tab="trap($event)" @keydown.escape.prevent="$wire.set('showCreateModal', false)" role="dialog" aria-modal="true" aria-labelledby="create-user-title" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-surface border border-line rounded-[14px] shadow-lg max-w-md w-full p-5 sm:p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h3 id="create-user-title" class="text-base font-bold text-ink tracking-tight">Tambah Pengguna Baru</h3>
                    <button type="button" aria-label="Tutup dialog tambah pengguna" wire:click="$set('showCreateModal', false)" class="text-muted hover:text-ink hover:bg-canvas p-2 rounded-xl transition-colors cursor-pointer">
                        <x-icon name="x" size="18" />
                    </button>
                </div>

                <form wire:submit="createUser" class="space-y-4">
                    <div>
                        <label for="create-name" class="block text-xs font-semibold text-ink mb-1">Nama Lengkap</label>
                        <input type="text" id="create-name" wire:model="name" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                        @error('name') <span class="text-xs text-danger mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-email" class="block text-xs font-semibold text-ink mb-1">Alamat Email</label>
                        <input type="email" id="create-email" wire:model="email" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                        @error('email') <span class="text-xs text-danger mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-role" class="block text-xs font-semibold text-ink mb-1">Peran Akses</label>
                        <select id="create-role" wire:model="role" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                            <option value="teacher">Guru</option>
                            <option value="admin">Administrator</option>
                        </select>
                        @error('role') <span class="text-xs text-danger mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-password" class="block text-xs font-semibold text-ink mb-1">Kata Sandi (Minimal 8 Karakter)</label>
                        <input type="password" id="create-password" wire:model="password" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                        @error('password') <span class="text-xs text-danger mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-password-confirmation" class="block text-xs font-semibold text-ink mb-1">Ulangi Kata Sandi</label>
                        <input type="password" id="create-password-confirmation" wire:model="password_confirmation" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-line">
                        <x-button type="button" variant="secondary" size="sm" wire:click="$set('showCreateModal', false)">Batal</x-button>
                        <x-button type="submit" variant="primary" size="sm">Simpan Pengguna</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Reset Password -->
    @if($showResetModal)
        <div x-data="dialogFocus()" @keydown.tab="trap($event)" @keydown.escape.prevent="$wire.set('showResetModal', false)" role="dialog" aria-modal="true" aria-labelledby="reset-user-title" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
            <div class="bg-surface border border-line rounded-[14px] shadow-lg max-w-md w-full p-5 sm:p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h3 id="reset-user-title" class="text-base font-bold text-ink tracking-tight">Reset Sandi Pengguna</h3>
                    <button type="button" aria-label="Tutup dialog reset sandi" wire:click="$set('showResetModal', false)" class="text-muted hover:text-ink hover:bg-canvas p-2 rounded-xl transition-colors cursor-pointer">
                        <x-icon name="x" size="18" />
                    </button>
                </div>

                <form wire:submit="resetPassword" class="space-y-4">
                    <div>
                        <label for="new-password" class="block text-xs font-semibold text-ink mb-1">Kata Sandi Baru (Min. 8 Karakter)</label>
                        <input type="password" id="new-password" wire:model="newPassword" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                        @error('newPassword') <span class="text-xs text-danger mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="new-password-confirmation" class="block text-xs font-semibold text-ink mb-1">Ulangi Kata Sandi Baru</label>
                        <input type="password" id="new-password-confirmation" wire:model="newPassword_confirmation" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-line">
                        <x-button type="button" variant="secondary" size="sm" wire:click="$set('showResetModal', false)">Batal</x-button>
                        <x-button type="submit" variant="primary" size="sm">Reset Sandi</x-button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
