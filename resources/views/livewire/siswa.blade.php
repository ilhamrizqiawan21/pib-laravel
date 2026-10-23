<div>
    <x-slot:title>Data Siswa</x-slot:title>

    <x-page-header
        eyebrow="DATA INDUK"
        title="Data Siswa"
        description="Kelola daftar siswa, NIS, jenis kelamin, dan penempatan kelas."
    >
        <x-slot:actions>
            <x-button variant="secondary" size="sm" href="/siswa/impor">
                <x-icon name="upload" size="16" />
                <span>Impor Siswa</span>
            </x-button>
            <x-button variant="primary" size="sm" wire:click="openCreateModal">
                <x-icon name="plus" size="16" />
                <span>Tambah Siswa</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($statusMessage)
        <div class="mb-6">
            <x-alert variant="success">
                {{ $statusMessage }}
            </x-alert>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-6">
            <x-alert variant="danger">
                {{ $errorMessage }}
            </x-alert>
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <div class="w-56">
                <select
                    wire:model.live="filter_class_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Kelas</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-40">
                <select
                    wire:model.live="filter_active"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Nonaktif</option>
                </select>
            </div>
        </div>

        <div class="w-full md:w-72">
            <x-search-field
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama atau NIS..."
            />
        </div>
    </div>

    <!-- 4 Keadaan Layar -->
    <div wire:loading.delay class="w-full mb-6">
        <x-loading-state :rows="5" />
    </div>

    <div wire:loading.remove>
        @if ($students->isEmpty())
            <x-empty-state
                icon="user"
                title="Belum ada data siswa"
                description="{{ $search ? 'Tidak ada siswa yang cocok dengan filter atau kata kunci pencarian.' : 'Silakan tambahkan data siswa atau lakukan impor massal melalui file Excel/CSV.' }}"
            >
                @if (! $search)
                    <x-slot:action>
                        <div class="flex items-center gap-2">
                            <x-button variant="primary" size="sm" wire:click="openCreateModal">
                                <x-icon name="plus" size="16" />
                                <span>Tambah Siswa</span>
                            </x-button>
                            <x-button variant="secondary" size="sm" href="/siswa/impor">
                                <x-icon name="upload" size="16" />
                                <span>Impor Siswa</span>
                            </x-button>
                        </div>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-card>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink">
                        <thead class="bg-canvas text-xs uppercase tracking-wider text-muted font-semibold border-b border-line">
                            <tr>
                                <th class="px-4 py-3">Nama Siswa</th>
                                <th class="px-4 py-3">NIS</th>
                                <th class="px-4 py-3">NISN</th>
                                <th class="px-4 py-3">L/P</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Nilai Terisi</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($students as $student)
                                <tr class="hover:bg-canvas/50 transition-colors {{ ! $student->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
                                    <td class="px-4 py-3.5 font-semibold text-ink">
                                        {{ $student->name }}
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-xs text-muted">
                                        {{ $student->nis ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-xs text-muted">
                                        {{ $student->nisn ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-muted">
                                        {{ $student->gender?->value ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <x-badge variant="neutral">
                                            {{ $student->schoolClass->name }}
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-muted">
                                        {{ $student->scores_count }} materi
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <button
                                            type="button"
                                            wire:click="toggleStudentActive({{ $student->id }})"
                                            class="cursor-pointer select-none"
                                            title="Klik untuk mengubah status aktif"
                                        >
                                            <x-status-badge :status="$student->is_active ? 'active' : 'inactive'" />
                                        </button>
                                    </td>
                                    <td class="px-4 py-3.5 text-right space-x-1">
                                        <x-button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="openEditModal({{ $student->id }})"
                                            title="Ubah data siswa"
                                        >
                                            <x-icon name="edit" size="15" />
                                        </x-button>
                                        <x-button
                                            variant="danger-ghost"
                                            size="sm"
                                            wire:click="confirmDelete({{ $student->id }})"
                                            title="Hapus permanen siswa"
                                        >
                                            <x-icon name="trash-2" size="15" />
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="mt-4 pt-4 border-t border-line">
                        {{ $students->links() }}
                    </div>
                @endif
            </x-card>
        @endif
    </div>

    <!-- Modal Form Siswa (Tambah / Ubah) -->
    <x-modal name="modal-form-siswa" :title="$editing_id ? 'Ubah Data Siswa' : 'Tambah Siswa Baru'">
        <form wire:submit="saveStudent" class="space-y-4">
            <x-field label="Kelas" id="form_school_class_id" required :error="$errors->first('form_school_class_id')">
                <select
                    id="form_school_class_id"
                    wire:model="form_school_class_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    required
                >
                    <option value="">Pilih Kelas</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Nama Lengkap Siswa" id="form_name" required help="Maksimal 120 karakter." :error="$errors->first('form_name')">
                <x-input
                    type="text"
                    id="form_name"
                    wire:model="form_name"
                    placeholder="Nama siswa"
                    required
                    :has-error="$errors->has('form_name')"
                />
            </x-field>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="NIS (Opsional, Unik)" id="form_nis" help="Kosongkan bila belum ada." :error="$errors->first('form_nis')">
                    <x-input
                        type="text"
                        id="form_nis"
                        wire:model="form_nis"
                        placeholder="Contoh: 20240101"
                        :has-error="$errors->has('form_nis')"
                    />
                </x-field>

                <x-field label="NISN (Opsional)" id="form_nisn" :error="$errors->first('form_nisn')">
                    <x-input
                        type="text"
                        id="form_nisn"
                        wire:model="form_nisn"
                        placeholder="Contoh: 0012345678"
                        :has-error="$errors->has('form_nisn')"
                    />
                </x-field>
            </div>

            <x-field label="Jenis Kelamin" id="form_gender" :error="$errors->first('form_gender')">
                <select
                    id="form_gender"
                    wire:model="form_gender"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </x-field>

            <div class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-ink">
                    <input type="checkbox" wire:model="form_is_active" class="rounded border-line text-brand focus:ring-focus">
                    <span class="font-medium">Siswa aktif</span>
                </label>
            </div>

            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-data @click="$dispatch('close-modal', 'modal-form-siswa')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">
                    {{ $editing_id ? 'Simpan Perubahan' : 'Tambah Siswa' }}
                </x-button>
            </x-slot:footer>
        </form>
    </x-modal>

    <!-- Modal Konfirmasi Hapus Permanen (Modal Bahaya) -->
    <x-modal name="modal-konfirmasi-hapus" title="Hapus Permanen Siswa" danger>
        <div class="space-y-3">
            <p class="text-sm text-ink">
                Apakah Anda yakin ingin menghapus siswa <strong>{{ $deleting_name }}</strong> secara permanen?
            </p>

            @if ($deleting_scores_count > 0)
                <div class="p-3.5 rounded-xl bg-danger-soft border border-red-200 text-xs text-danger font-medium flex items-start gap-2.5">
                    <x-icon name="alert-triangle" size="18" class="shrink-0 mt-0.5" />
                    <div>
                        <strong>Peringatan:</strong> Siswa ini memiliki <strong>{{ $deleting_scores_count }} data nilai</strong> yang tersimpan. Menghapus siswa akan menghapus seluruh catatan nilai tersebut secara permanen dari basis data!
                    </div>
                </div>
            @else
                <p class="text-xs text-muted">
                    Siswa ini belum memiliki data nilai yang tersimpan.
                </p>
            @endif
        </div>

        <x-slot:footer>
            <x-button type="button" variant="secondary" size="sm" x-data @click="$dispatch('close-modal', 'modal-konfirmasi-hapus')">
                Batal
            </x-button>
            <x-button type="button" variant="danger" size="sm" wire:click="deleteConfirmed" wire:loading.attr="disabled">
                Hapus Permanen
            </x-button>
        </x-slot:footer>
    </x-modal>
</div>

