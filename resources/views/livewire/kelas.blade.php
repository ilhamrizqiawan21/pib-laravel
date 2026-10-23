<div>
    <x-slot:title>Data Kelas</x-slot:title>

    <x-page-header
        eyebrow="DATA INDUK"
        title="Data Kelas"
        description="Kelola kelas per tahun ajaran, tingkat kelas, dan wali kelas."
    >
        <x-slot:actions>
            <x-button variant="primary" size="sm" wire:click="openCreateModal">
                <x-icon name="plus" size="16" />
                <span>Tambah Kelas</span>
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
            <div class="w-full md:w-64">
                <select
                    aria-label="Filter tahun ajaran kelas"
                    wire:model.live="filter_academic_year_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">
                            {{ $year->label() }} {{ $year->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="w-full md:w-72">
            <x-search-field
                wire:model.live.debounce.300ms="search"
                placeholder="Cari nama kelas..."
            />
        </div>
    </div>

    <!-- 4 Keadaan Layar -->
    <div wire:loading.delay class="w-full mb-6">
        <x-loading-state :rows="4" />
    </div>

    <div wire:loading.remove>
        @if ($classes->isEmpty())
            <x-empty-state
                icon="graduation-cap"
                title="Belum ada data kelas"
                description="{{ $search ? 'Tidak ada kelas yang cocok dengan kata kunci pencarian.' : 'Silakan tambahkan data kelas untuk tahun ajaran yang dipilih.' }}"
            >
                @if (! $search)
                    <x-slot:action>
                        <x-button variant="primary" size="sm" wire:click="openCreateModal">
                            <x-icon name="plus" size="16" />
                            <span>Tambah Kelas Pertama</span>
                        </x-button>
                    </x-slot:action>
                @endif
            </x-empty-state>
        @else
            <x-card>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-ink">
                        <thead class="bg-canvas text-xs uppercase tracking-wider text-muted font-semibold border-b border-line">
                            <tr>
                                <th class="px-4 py-3">Nama Kelas</th>
                                <th class="px-4 py-3">Tingkat</th>
                                <th class="px-4 py-3">Wali Kelas</th>
                                <th class="px-4 py-3">Tahun Ajaran</th>
                                <th class="px-4 py-3">Jumlah Siswa</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($classes as $class)
                                <tr class="hover:bg-canvas/50 transition-colors {{ ! $class->is_active ? 'opacity-60 bg-slate-50/50' : '' }}">
                                    <td class="px-4 py-3.5 font-semibold text-ink">
                                        {{ $class->name }}
                                    </td>
                                    <td class="px-4 py-3.5 text-muted">
                                        {{ $class->grade_level ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-muted">
                                        {{ $class->homeroom_teacher ?: '-' }}
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-muted">
                                        {{ $class->academicYear?->label() }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <x-badge variant="neutral">
                                            {{ $class->students_count }} siswa
                                        </x-badge>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <button
                                            type="button"
                                            wire:click="toggleClassActive({{ $class->id }})"
                                            class="cursor-pointer select-none"
                                            title="Klik untuk mengubah status aktif"
                                        >
                                            <x-status-badge :status="$class->is_active ? 'active' : 'inactive'" />
                                        </button>
                                    </td>
                                    <td class="px-4 py-3.5 text-right space-x-1">
                                        <x-button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="openEditModal({{ $class->id }})"
                                            title="Ubah data kelas"
                                        >
                                            <x-icon name="edit" size="15" />
                                        </x-button>
                                        <x-button
                                            variant="danger-ghost"
                                            size="sm"
                                            wire:click="deleteClass({{ $class->id }})"
                                            wire:confirm="Yakin ingin menghapus kelas ini? Tindakan ini hanya berhasil jika kelas tidak memiliki siswa."
                                            title="Hapus kelas"
                                        >
                                            <x-icon name="trash-2" size="15" />
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    </div>

    <!-- Modal Form Kelas (Tambah / Ubah) -->
    <x-modal name="modal-form-kelas" :title="$editing_id ? 'Ubah Data Kelas' : 'Tambah Kelas Baru'">
        <form wire:submit="saveClass" class="space-y-4">
            <x-field label="Tahun Ajaran" id="form_academic_year_id" required :error="$errors->first('form_academic_year_id')">
                <select
                    id="form_academic_year_id"
                    wire:model="form_academic_year_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    required
                >
                    <option value="">Pilih Tahun Ajaran</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">
                            {{ $year->label() }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Nama Kelas" id="form_name" required help="Maksimal 50 karakter (contoh: VII-A, 8 B)." :error="$errors->first('form_name')">
                <x-input
                    type="text"
                    id="form_name"
                    wire:model="form_name"
                    placeholder="VII-A"
                    required
                    :has-error="$errors->has('form_name')"
                />
            </x-field>

            <x-field label="Tingkat Kelas" id="form_grade_level" help="Contoh: 7, 8, 9 atau VII, VIII, IX." :error="$errors->first('form_grade_level')">
                <x-input
                    type="text"
                    id="form_grade_level"
                    wire:model="form_grade_level"
                    placeholder="7"
                    :has-error="$errors->has('form_grade_level')"
                />
            </x-field>

            <x-field label="Wali Kelas" id="form_homeroom_teacher" help="Maksimal 100 karakter." :error="$errors->first('form_homeroom_teacher')">
                <x-input
                    type="text"
                    id="form_homeroom_teacher"
                    wire:model="form_homeroom_teacher"
                    placeholder="Ust. Abdullah, S.Pd"
                    :has-error="$errors->has('form_homeroom_teacher')"
                />
            </x-field>

            <div class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-ink">
                    <input type="checkbox" wire:model="form_is_active" class="rounded border-line text-brand-strong focus:ring-focus">
                    <span class="font-medium">Kelas aktif</span>
                </label>
            </div>

            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-data @click="$dispatch('close-modal', 'modal-form-kelas')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">
                    {{ $editing_id ? 'Simpan Perubahan' : 'Tambah Kelas' }}
                </x-button>
            </x-slot:footer>
        </form>
    </x-modal>
</div>

