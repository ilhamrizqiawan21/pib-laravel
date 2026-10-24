<x-app-layout>
    <x-slot:title>Penilaian per Materi</x-slot:title>

    <div
        x-data="materiAssessmentComponent({
            classes: {{ Js::from($classes) }},
            initialClassId: {{ Js::from($initialClassId) }},
            initialAssessmentId: {{ Js::from($initialAssessmentId) }}
        })"
        class="assessment-page space-y-6"
    >
        <!-- Header Halaman -->
        <x-page-header
            eyebrow="PENILAIAN • PER MATERI"
            title="Penilaian per Materi"
            description="Pilih kelas dan materi, lalu nilai seluruh siswa dalam satu kelas secara berurutan."
        >
            <x-slot:actions>
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-xl border border-line bg-surface p-1 text-xs font-semibold shadow-2xs">
                        <a href="{{ route('penilaian.setoran') }}" class="rounded-lg px-3.5 py-1.5 text-muted hover:text-ink hover:bg-canvas transition-colors">
                            Per siswa (Setoran)
                        </a>
                        <a href="{{ route('penilaian.materi') }}" class="rounded-lg bg-brand-soft px-3.5 py-1.5 text-brand-strong shadow-xs">
                            Per materi
                        </a>
                    </span>
                </div>
            </x-slot:actions>
        </x-page-header>

        <!-- Pesan Galat / Status -->
        <template x-if="errorMessage">
            <x-alert variant="danger">
                <span x-text="errorMessage"></span>
                <button type="button" @click="loadClassProgress()" class="ml-2 font-semibold underline">Coba lagi</button>
            </x-alert>
        </template>

        <template x-if="statusMessage">
            <x-alert variant="warning">
                <span x-text="statusMessage"></span>
            </x-alert>
        </template>

        <!-- Bar Pemilih Kelas & Materi -->
        <div class="bg-surface rounded-xl border border-line p-5 shadow-xs space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Dropdown Kelas -->
                <div>
                    <label for="materi-class-select" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
                        Pilih Kelas
                    </label>
                    <select
                        id="materi-class-select"
                        x-model="classId"
                        @change="changeClass($event.target.value)"
                        class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    >
                        <option value="">-- Pilih Kelas --</option>
                        @foreach ($classes as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }} &bull; {{ $c->academicYear?->name ?? 'Tahun Ajaran' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dropdown Materi -->
                <div>
                    <label for="materi-select" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1.5">
                        Pilih Materi Penilaian
                    </label>
                    <select
                        id="materi-select"
                        :value="assessmentId || ''"
                        @change="changeAssessment($event.target.value)"
                        :disabled="!classId || !data || data.materials.length === 0"
                        class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand disabled:opacity-50"
                    >
                        <option value="">-- Pilih Materi --</option>
                        <template x-if="data && data.materials">
                            <template x-for="m in data.materials" :key="m.id">
                                <option :value="m.id" x-text="m.chapter + ' › ' + m.subchapter + ' › ' + m.title"></option>
                            </template>
                        </template>
                    </select>
                </div>
            </div>

            <!-- Kartu Info & Kemajuan Materi Terpilih -->
            <template x-if="selectedMaterial">
                <div class="pt-4 border-t border-line flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-ink" x-text="selectedMaterial.title"></h3>
                            <template x-if="selectedMaterial.weight !== 1.0">
                                <x-badge variant="brand" x-text="selectedMaterial.weight + 'x'"></x-badge>
                            </template>
                        </div>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="selectedMaterial.chapter + ' &rsaquo; ' + selectedMaterial.subchapter"></p>
                    </div>

                    <div class="w-64 space-y-1.5">
                        <div class="flex items-center justify-between text-xs font-medium text-ink">
                            <span>Progres Dinilai</span>
                            <span class="font-bold text-brand-strong" x-text="materialStats.assessedCount + ' / ' + materialStats.totalCount + ' Siswa (' + materialStats.percentage + '%)'"></span>
                        </div>
                        <div class="w-full bg-line rounded-full h-2 overflow-hidden">
                            <div
                                class="bg-brand h-2 rounded-full transition-all duration-300"
                                :style="'width: ' + materialStats.percentage + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Filter & Pencarian Siswa -->
        <template x-if="classId && selectedMaterial">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                <!-- Filter Status -->
                <div class="inline-flex rounded-lg border border-line bg-surface p-1 text-xs">
                    <button
                        type="button"
                        @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-surface-alt font-bold text-ink shadow-2xs' : 'text-ink-muted hover:text-ink'"
                        class="rounded-md px-3 py-1.5 transition-colors"
                    >
                        Semua Siswa
                    </button>
                    <button
                        type="button"
                        @click="filter = 'unassessed'"
                        :class="filter === 'unassessed' ? 'bg-surface-alt font-bold text-ink shadow-2xs' : 'text-ink-muted hover:text-ink'"
                        class="rounded-md px-3 py-1.5 transition-colors"
                    >
                        Belum Dinilai
                    </button>
                    <button
                        type="button"
                        @click="filter = 'drafts'"
                        :class="filter === 'drafts' ? 'bg-surface-alt font-bold text-ink shadow-2xs' : 'text-ink-muted hover:text-ink'"
                        class="rounded-md px-3 py-1.5 transition-colors"
                    >
                        Ada Draft
                    </button>
                </div>

                <!-- Cari Siswa -->
                <div class="w-full sm:w-64">
                    <x-search-field
                        x-model="query"
                        placeholder="Cari nama atau NIS siswa..."
                    />
                </div>
            </div>
        </template>

        <!-- Loading State -->
        <template x-if="loading">
            <div class="p-12 bg-surface rounded-xl border border-line">
                <x-loading-state message="Memuat daftar siswa dan materi..." />
            </div>
        </template>

        <!-- Empty State: Belum ada kelas / materi dipilih -->
        <template x-if="!loading && !classId">
            <div class="p-12 bg-surface rounded-xl border border-line text-center">
                <x-empty-state
                    title="Pilih kelas terlebih dahulu"
                    description="Pilih kelas untuk mulai menilai materi secara menyeluruh."
                />
            </div>
        </template>

        <template x-if="!loading && classId && !selectedMaterial">
            <div class="p-12 bg-surface rounded-xl border border-line text-center">
                <x-empty-state
                    title="Pilih materi yang akan dinilai"
                    description="Pilih salah satu materi dari menu dropdown di atas untuk memuat daftar siswa kelas."
                />
            </div>
        </template>

        <!-- Empty State: Hasil Filter Kosong -->
        <template x-if="!loading && classId && selectedMaterial && filteredStudents.length === 0">
            <div class="p-12 bg-surface rounded-xl border border-line text-center">
                <x-empty-state
                    title="Tidak ada siswa yang cocok"
                    description="Tidak ada data siswa yang sesuai dengan filter atau kata kunci pencarian Anda."
                />
            </div>
        </template>

        <!-- Tabel Siswa Terisi -->
        <template x-if="!loading && classId && selectedMaterial && filteredStudents.length > 0">
            <div class="bg-surface rounded-xl border border-line shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-line bg-canvas/80 text-[11px] font-bold uppercase tracking-wider text-muted">
                                <th scope="col" class="py-3 px-4 w-12 text-center">No</th>
                                <th scope="col" class="py-3 px-4">Nama Siswa</th>
                                <th scope="col" class="py-3 px-4 w-32">NIS</th>
                                <th scope="col" class="py-3 px-4 w-36">Status</th>
                                <th scope="col" class="py-3 px-4 w-44">Jumlah Kesalahan</th>
                                <th scope="col" class="py-3 px-4 w-24 text-right">Nilai</th>
                                <th scope="col" class="py-3 px-4 w-32 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/60">
                            <template x-for="(st, idx) in filteredStudents" :key="st.id">
                                <tr class="hover:bg-canvas/50 transition-colors">
                                    <td class="py-3 px-4 text-center text-xs font-mono text-muted" x-text="idx + 1"></td>
                                    <td class="py-3 px-4">
                                        <div class="font-semibold text-ink" x-text="st.name"></div>
                                    </td>
                                    <td class="py-3 px-4 text-xs font-mono text-muted" x-text="st.nis || '—'"></td>
                                    <td class="py-3 px-4">
                                        <template x-if="getRowMeta(st).statusLabel">
                                            <span
                                                class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-[11px]"
                                                :class="{
                                                    'bg-brand-soft text-brand-strong': getRowMeta(st).statusTone === 'brand',
                                                    'bg-success-soft text-emerald-700': getRowMeta(st).statusTone === 'success',
                                                    'bg-warning-soft text-amber-700': getRowMeta(st).statusTone === 'warning',
                                                    'bg-danger-soft text-danger': getRowMeta(st).statusTone === 'danger',
                                                    'bg-canvas text-muted': getRowMeta(st).statusTone === 'neutral'
                                                }"
                                                x-text="getRowMeta(st).statusLabel"
                                            ></span>
                                        </template>
                                        <template x-if="!getRowMeta(st).statusLabel">
                                            <span class="text-xs text-muted italic">Belum dinilai</span>
                                        </template>
                                    </td>
                                    <td class="py-3 px-4">
                                        <!-- Stepper Kesalahan -->
                                        <div class="materi-score-control flex items-center rounded-lg border border-line bg-surface overflow-hidden shadow-2xs w-36">
                                            <button
                                                type="button"
                                                @click="step(st, -1)"
                                                :aria-label="'Kurangi kesalahan ' + st.name"
                                                :disabled="getRowMeta(st).isSaving"
                                                class="px-2.5 py-1 text-muted hover:text-ink hover:bg-canvas border-r border-line disabled:opacity-40 active:bg-line transition-colors cursor-pointer"
                                                title="Kurangi kesalahan"
                                            >
                                                -
                                            </button>
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                :aria-label="'Jumlah kesalahan ' + st.name"
                                                :data-student-row="idx"
                                                :value="getRawMistakes(st.id)"
                                                @input="onInputMistakes(st, $event.target.value)"
                                                @keydown.enter.prevent="commit(st, idx)"
                                                @keydown.arrow-up.prevent="step(st, 1)"
                                                @keydown.arrow-down.prevent="step(st, -1)"
                                                @focus="$event.target.select()"
                                                :disabled="getRowMeta(st).isSaving"
                                                placeholder="0"
                                                class="w-full text-center text-sm font-semibold text-ink py-1 focus:outline-none focus:bg-brand-light/20"
                                            />
                                            <button
                                                type="button"
                                                @click="step(st, 1)"
                                                :aria-label="'Tambah kesalahan ' + st.name"
                                                :disabled="getRowMeta(st).isSaving"
                                                class="px-2.5 py-1 text-muted hover:text-ink hover:bg-canvas border-l border-line disabled:opacity-40 active:bg-line transition-colors cursor-pointer"
                                                title="Tambah kesalahan"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="text-base font-extrabold text-ink" x-text="'= ' + getRowMeta(st).scoreDisplay"></span>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="materi-row-actions flex flex-wrap items-center justify-center gap-2">
                                            <!-- Tombol 90 -->
                                            <button
                                                type="button"
                                                @click="setPerfect90(st, idx)"
                                                :aria-label="'Nilai 90 untuk ' + st.name"
                                                :disabled="getRowMeta(st).isSaving"
                                                title="Lancar: 0 kesalahan (nilai 90), langsung simpan dan lanjut"
                                                class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100 font-bold text-xs tracking-wider shadow-2xs disabled:opacity-50 active:scale-95 transition-all cursor-pointer"
                                            >
                                                90
                                            </button>
                                            <button type="button" @click="openDetails(st)" :aria-label="'Detail nilai ' + st.name" class="rounded-lg border border-line px-2.5 py-1 text-xs font-semibold text-brand-strong hover:bg-brand-soft active:scale-95 transition-all cursor-pointer">Detail</button>

                                            <!-- Tombol Discard Draft jika ada -->
                                            <template x-if="getRowMeta(st).draft">
                                                <button
                                                    type="button"
                                                    @click="discard(st)"
                                                    :aria-label="'Batalkan perubahan ' + st.name"
                                                    class="p-1.5 text-ink-muted hover:text-danger rounded hover:bg-danger-light"
                                                    title="Batalkan perubahan lokal"
                                                >
                                                    <x-icon name="refresh-cw" size="14" />
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    <x-modal name="score-details-materi" title="Detail nilai">
        <x-score-details-fields />
        <x-slot:footer>
            <button type="button" @click="close()" class="rounded-lg border border-line px-4 py-2 text-sm">Batal</button>
            <button type="button" @click="saveDetails()" :disabled="savingDetails" class="rounded-lg bg-brand-strong px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">Simpan detail</button>
        </x-slot:footer>
    </x-modal>
    <x-modal name="confirm-clear-materi" title="Kosongkan nilai?" :danger="true">
        <p>Nilai tersimpan akan berubah menjadi Belum dinilai. Riwayat perubahan tetap tercatat.</p>
        <x-slot:footer>
            <button type="button" @click="cancelClear(); close()" class="rounded-lg border border-line px-4 py-2 text-sm">Batal</button>
            <button type="button" @click="confirmClear()" class="rounded-lg bg-danger px-4 py-2 text-sm font-semibold text-white">Kosongkan nilai</button>
        </x-slot:footer>
    </x-modal>
    </div>

    <script>
        try {
            localStorage.setItem('pib_assessment_mode', 'materi');
            document.cookie = 'pib_assessment_mode=materi; path=/; max-age=31536000; SameSite=Lax';
        } catch {}
    </script>
</x-app-layout>
