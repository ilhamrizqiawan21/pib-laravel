<div>
    <x-slot:title>Sekolah & Periode</x-slot:title>

    <x-page-header
        eyebrow="DATA INDUK"
        title="Sekolah & periode"
        description="Kelola identitas sekolah dan daftar tahun ajaran/semester penilaian."
    />

    @if ($statusMessage)
        <div class="mb-6 max-w-4xl">
            <x-alert variant="success">
                {{ $statusMessage }}
            </x-alert>
        </div>
    @endif

    @if ($academicYearErrorMessage)
        <div class="mb-6 max-w-4xl">
            <x-alert variant="danger">
                {{ $academicYearErrorMessage }}
            </x-alert>
        </div>
    @endif

    <div class="space-y-8 max-w-4xl">
        <!-- Identitas Sekolah Card -->
        <x-card title="Identitas Sekolah" description="Informasi ini dicantumkan pada lembar laporan Excel dan berkas cetak PDF.">
            <form wire:submit="saveIdentity" class="space-y-4">
                <x-field label="Nama Sekolah" id="school_name" help="Maksimal 150 karakter." :error="$errors->first('school_name')">
                    <x-input
                        type="text"
                        id="school_name"
                        wire:model="school_name"
                        placeholder="Contoh: SMP Islam Terpadu Al-Ikhlas"
                        :has-error="$errors->has('school_name')"
                    />
                </x-field>

                <x-field label="Alamat Sekolah" id="school_address" help="Maksimal 250 karakter." :error="$errors->first('school_address')">
                    <x-input
                        type="text"
                        id="school_address"
                        wire:model="school_address"
                        placeholder="Contoh: Jl. Ki Hajar Dewantara No. 10, Jakarta"
                        :has-error="$errors->has('school_address')"
                    />
                </x-field>

                <x-field label="Nama Guru / Penilai" id="teacher_name" help="Maksimal 120 karakter." :error="$errors->first('teacher_name')">
                    <x-input
                        type="text"
                        id="teacher_name"
                        wire:model="teacher_name"
                        placeholder="Contoh: Ust. Ahmad Fauzi, S.Pd.I"
                        :has-error="$errors->has('teacher_name')"
                    />
                </x-field>

                <div class="pt-2">
                    <x-button type="submit" variant="primary" wire:loading.attr="disabled">
                        <x-icon name="check" size="16" />
                        <span>Simpan Identitas</span>
                    </x-button>
                </div>
            </form>
        </x-card>

        <!-- Tahun Ajaran & Semester Card -->
        <x-card title="Tahun Ajaran & Semester" description="Maksimal satu periode yang berstatus aktif dalam satu waktu.">
            <x-slot:actions>
                <x-button variant="primary" size="sm" x-data @click="$dispatch('open-modal', 'modal-tambah-tahun')">
                    <x-icon name="plus" size="16" />
                    <span>Tambah Periode</span>
                </x-button>
            </x-slot:actions>

            @if ($academicYears->isEmpty())
                <x-empty-state
                    icon="school"
                    title="Belum ada tahun ajaran"
                    description="Tambahkan tahun ajaran dan semester untuk memulai penilaian siswa."
                >
                    <x-slot:action>
                        <x-button variant="primary" size="sm" x-data @click="$dispatch('open-modal', 'modal-tambah-tahun')">
                            <x-icon name="plus" size="16" />
                            <span>Tambah Periode Pertama</span>
                        </x-button>
                    </x-slot:action>
                </x-empty-state>
            @else
                <div class="overflow-x-auto border border-line rounded-xl">
                    <table class="w-full text-left text-sm text-ink">
                        <thead class="bg-canvas text-xs uppercase tracking-wider text-muted font-semibold border-b border-line">
                            <tr>
                                <th class="px-4 py-3">Tahun Ajaran</th>
                                <th class="px-4 py-3">Semester</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($academicYears as $year)
                                <tr class="hover:bg-canvas/50 transition-colors">
                                    <td class="px-4 py-3.5 font-medium">
                                        {{ $year->name }}
                                    </td>
                                    <td class="px-4 py-3.5 text-muted">
                                        {{ $year->semester->value }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        @if ($year->is_active)
                                            <x-status-badge status="active" label="Aktif" />
                                        @else
                                            <x-button
                                                variant="secondary"
                                                size="sm"
                                                wire:click="activateAcademicYear({{ $year->id }})"
                                                wire:loading.attr="disabled"
                                            >
                                                Jadikan Aktif
                                            </x-button>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <x-button
                                            variant="danger-ghost"
                                            size="sm"
                                            wire:click="deleteAcademicYear({{ $year->id }})"
                                            wire:confirm="Yakin ingin menghapus tahun ajaran ini?"
                                            wire:loading.attr="disabled"
                                            title="Hapus periode"
                                        >
                                            <x-icon name="trash-2" size="15" />
                                        </x-button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    </div>

    <!-- Modal Tambah Tahun Ajaran -->
    <x-modal name="modal-tambah-tahun" title="Tambah Tahun Ajaran & Semester">
        <form wire:submit="createAcademicYear" class="space-y-4">
            <x-field label="Tahun Ajaran (Format YYYY/YYYY)" id="new_year_name" required help="Contoh: 2024/2025" :error="$errors->first('new_year_name')">
                <x-input
                    type="text"
                    id="new_year_name"
                    wire:model="new_year_name"
                    placeholder="2024/2025"
                    required
                    :has-error="$errors->has('new_year_name')"
                />
            </x-field>

            <x-field label="Semester" id="new_year_semester" required :error="$errors->first('new_year_semester')">
                <select
                    id="new_year_semester"
                    wire:model="new_year_semester"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="Ganjil">Ganjil</option>
                    <option value="Genap">Genap</option>
                </select>
            </x-field>

            <div class="pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-ink">
                    <input type="checkbox" wire:model="new_year_is_active" class="rounded border-line text-brand focus:ring-focus">
                    <span class="font-medium">Jadikan sebagai periode aktif saat ini</span>
                </label>
            </div>

            <x-slot:footer>
                <x-button type="button" variant="secondary" size="sm" x-data @click="$dispatch('close-modal', 'modal-tambah-tahun')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">
                    Simpan Periode
                </x-button>
            </x-slot:footer>
        </form>
    </x-modal>
</div>

