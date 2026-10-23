<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-pib-600 mb-1">Administrasi Sistem</div>
            <h1 class="text-2xl font-bold text-gray-900">Admin Pengguna</h1>
            <p class="text-sm text-gray-500 mt-0.5">Kelola akun guru dan admin, ubah peran, serta kontrol akses masuk.</p>
        </div>
        <div>
            <button type="button" wire:click="$set('showCreateModal', true)" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-pib-600 hover:bg-pib-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Pengguna
            </button>
        </div>
    </div>

    <!-- Alert / Notifikasi -->
    @if(session('message'))
        <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('message') }}</span>
        </div>
    @endif

    @error('action')
        <div class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <!-- Tabel Pengguna -->
    <x-card>
        <x-slot:header>
            <div class="flex items-center justify-between gap-4">
                <div class="w-full max-w-xs">
                    <input type="search" aria-label="Cari nama atau email pengguna" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau email..." class="w-full text-xs rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                </div>
            </div>
        </x-slot:header>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Nama Pengguna</th>
                        <th class="px-4 py-3">Alamat Email</th>
                        <th class="px-4 py-3">Peran</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $user->name }}
                                @if(auth()->id() === $user->id)
                                    <span class="ml-1 text-[10px] text-pib-600 font-semibold bg-pib-50 px-1.5 py-0.5 rounded">(Anda)</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @if($user->role === \App\Enums\Role::Admin)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">Admin</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">Guru</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($user->is_active)
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-red-600">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <!-- Ubah Peran -->
                                    @if($user->role === \App\Enums\Role::Admin)
                                        <button type="button" wire:click="changeRole({{ $user->id }}, 'teacher')" class="text-xs text-gray-600 hover:text-pib-600 underline">Jadikan Guru</button>
                                    @else
                                        <button type="button" wire:click="changeRole({{ $user->id }}, 'admin')" class="text-xs text-gray-600 hover:text-purple-600 underline">Jadikan Admin</button>
                                    @endif

                                    <!-- Reset Password -->
                                    <button type="button" wire:click="openResetModal({{ $user->id }})" class="text-xs text-blue-600 hover:text-blue-800 underline">Reset Sandi</button>

                                    <!-- Toggle Active -->
                                    @if($user->is_active)
                                        <button type="button" wire:click="toggleActive({{ $user->id }})" class="text-xs text-red-600 hover:text-red-800 underline">Nonaktifkan</button>
                                    @else
                                        <button type="button" wire:click="toggleActive({{ $user->id }})" class="text-xs text-emerald-600 hover:text-emerald-800 underline">Aktifkan</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-sm">
                                Tidak ada data pengguna yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>

    <!-- Modal Tambah Pengguna -->
    @if($showCreateModal)
        <div x-data="dialogFocus()" @keydown.tab="trap($event)" @keydown.escape.prevent="$wire.set('showCreateModal', false)" role="dialog" aria-modal="true" aria-labelledby="create-user-title" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-4 sm:p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="create-user-title" class="text-base font-bold text-gray-900">Tambah Pengguna Baru</h3>
                    <button type="button" aria-label="Tutup dialog tambah pengguna" wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg">✕</button>
                </div>

                <form wire:submit="createUser" class="space-y-4">
                    <div>
                        <label for="create-name" class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap</label>
                        <input type="text" id="create-name" wire:model="name" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                        @error('name') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-email" class="block text-xs font-medium text-gray-700 mb-1">Alamat Email</label>
                        <input type="email" id="create-email" wire:model="email" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                        @error('email') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-role" class="block text-xs font-medium text-gray-700 mb-1">Peran Akses</label>
                        <select id="create-role" wire:model="role" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                            <option value="teacher">Guru</option>
                            <option value="admin">Administrator</option>
                        </select>
                        @error('role') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-password" class="block text-xs font-medium text-gray-700 mb-1">Kata Sandi (Minimal 8 Karakter)</label>
                        <input type="password" id="create-password" wire:model="password" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                        @error('password') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="create-password-confirmation" class="block text-xs font-medium text-gray-700 mb-1">Ulangi Kata Sandi</label>
                        <input type="password" id="create-password-confirmation" wire:model="password_confirmation" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-medium text-white bg-pib-600 hover:bg-pib-700 rounded-lg">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Reset Password -->
    @if($showResetModal)
        <div x-data="dialogFocus()" @keydown.tab="trap($event)" @keydown.escape.prevent="$wire.set('showResetModal', false)" role="dialog" aria-modal="true" aria-labelledby="reset-user-title" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-4 sm:p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 id="reset-user-title" class="text-base font-bold text-gray-900">Reset Sandi Pengguna</h3>
                    <button type="button" aria-label="Tutup dialog reset sandi" wire:click="$set('showResetModal', false)" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg">✕</button>
                </div>

                <form wire:submit="resetPassword" class="space-y-4">
                    <div>
                        <label for="new-password" class="block text-xs font-medium text-gray-700 mb-1">Kata Sandi Baru (Min. 8 Karakter)</label>
                        <input type="password" id="new-password" wire:model="newPassword" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                        @error('newPassword') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="new-password-confirmation" class="block text-xs font-medium text-gray-700 mb-1">Ulangi Kata Sandi Baru</label>
                        <input type="password" id="new-password-confirmation" wire:model="newPassword_confirmation" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t">
                        <button type="button" wire:click="$set('showResetModal', false)" class="px-4 py-2 text-xs font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                        <button type="submit" class="px-4 py-2 text-xs font-medium text-white bg-pib-600 hover:bg-pib-700 rounded-lg">Reset Sandi</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

