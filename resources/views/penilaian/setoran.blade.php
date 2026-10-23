<x-app-layout>
    <x-slot:title>Setoran Penilaian</x-slot:title>

    <div
        x-data="setoranComponent({
            classes: {{ Js::from($classes) }},
            initialClassId: {{ Js::from($initialClassId) }},
            initialStudentId: {{ Js::from($initialStudentId) }}
        })"
        class="space-y-6"
    >
    <!-- Header Halaman -->
    <x-page-header
        eyebrow="PENILAIAN &bull; PER SISWA"
        title="Setoran"
        description="Pilih siswa yang maju, ketik jumlah kesalahan, lalu tekan Enter. Nilai langsung tersimpan dan kursor pindah ke materi berikutnya."
    >
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-lg border border-line bg-surface p-1 text-xs font-medium">
                    <a href="{{ route('penilaian.setoran') }}" class="rounded-md bg-brand-light px-3 py-1.5 font-semibold text-brand shadow-xs">
                        Per siswa (Setoran)
                    </a>
                    <a href="{{ route('penilaian.materi') }}" class="rounded-md px-3 py-1.5 text-ink-muted hover:text-ink">
                        Per materi
                    </a>
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Pesan Peringatan Status / Galat -->
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

    <!-- Layout Dua Panel -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Panel Kiri: Daftar Siswa (4 kolom desktop) -->
        <aside class="lg:col-span-4 bg-surface rounded-xl border border-line shadow-xs overflow-hidden flex flex-col h-[calc(100vh-220px)] min-h-[500px]">
            <!-- Pemilih Kelas -->
            <div class="p-4 border-b border-line space-y-3 bg-surface-alt/50">
                <div>
                    <label for="setoran-class-select" class="block text-xs font-semibold uppercase tracking-wider text-ink-muted mb-1">
                        Pilih Kelas
                    </label>
                    <select
                        id="setoran-class-select"
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

                <!-- Kotak Pencarian Siswa (Shortcut '/') -->
                <div class="relative" x-show="classId && data">
                    <input
                        type="search"
                        id="search-siswa-input"
                        x-model="query"
                        placeholder="Cari siswa... (tekan /)"
                        class="w-full rounded-lg border border-line bg-surface pl-9 pr-8 py-1.5 text-xs text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    />
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-ink-muted">
                        <x-icon name="search" size="14" />
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none">
                        <x-kbd>/</x-kbd>
                    </div>
                </div>
            </div>

            <!-- List Siswa (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-2 space-y-4">
                <template x-if="loading">
                    <div class="p-4">
                        <x-loading-state message="Memuat siswa..." />
                    </div>
                </template>

                <template x-if="!loading && !classId">
                    <div class="p-6 text-center text-xs text-ink-muted">
                        Silakan pilih kelas terlebih dahulu untuk melihat daftar siswa.
                    </div>
                </template>

                <template x-if="!loading && classId && data && data.students.length === 0">
                    <div class="p-6 text-center text-xs text-ink-muted">
                        Belum ada siswa aktif di kelas ini.
                    </div>
                </template>

                <template x-if="!loading && classId && data && data.students.length > 0">
                    <div class="space-y-4">
                        <!-- Grup: Dinilai Hari Ini -->
                        <template x-if="filteredStudents.today.length > 0">
                            <div>
                                <div class="px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-brand">
                                    Dinilai Hari Ini (<span x-text="filteredStudents.today.length"></span>)
                                </div>
                                <ul class="space-y-1 mt-1">
                                    <template x-for="st in filteredStudents.today" :key="st.id">
                                        <li>
                                            <button
                                                type="button"
                                                @click="selectStudent(st.id)"
                                                :class="st.id === studentId ? 'bg-brand-light border-brand text-brand font-semibold shadow-xs' : 'hover:bg-canvas text-ink border-transparent'"
                                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg border text-left text-xs transition-colors"
                                            >
                                                <div class="flex items-center gap-2 overflow-hidden">
                                                    <span
                                                        x-show="hasPendingDraft(st.id)"
                                                        class="w-2 h-2 rounded-full bg-amber-500 shrink-0"
                                                        title="Ada nilai belum terkirim"
                                                    ></span>
                                                    <div class="truncate">
                                                        <div class="truncate font-medium" x-text="st.name"></div>
                                                        <div class="text-[10px] text-ink-muted" x-text="st.nis || 'Tanpa NIS'"></div>
                                                    </div>
                                                </div>
                                                <span class="text-[11px] font-mono shrink-0 ml-2 text-ink-muted" x-text="studentProgressLabel(st.id)"></span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>

                        <!-- Grup: Siswa Lainnya -->
                        <div>
                            <div class="px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-ink-muted">
                                Siswa (<span x-text="filteredStudents.others.length"></span>)
                            </div>
                            <ul class="space-y-1 mt-1">
                                <template x-for="st in filteredStudents.others" :key="st.id">
                                    <li>
                                        <button
                                            type="button"
                                            @click="selectStudent(st.id)"
                                            :class="st.id === studentId ? 'bg-brand-light border-brand text-brand font-semibold shadow-xs' : 'hover:bg-canvas text-ink border-transparent'"
                                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg border text-left text-xs transition-colors"
                                        >
                                            <div class="flex items-center gap-2 overflow-hidden">
                                                <span
                                                    x-show="hasPendingDraft(st.id)"
                                                    class="w-2 h-2 rounded-full bg-amber-500 shrink-0"
                                                    title="Ada nilai belum terkirim"
                                                ></span>
                                                <div class="truncate">
                                                    <div class="truncate font-medium" x-text="st.name"></div>
                                                    <div class="text-[10px] text-ink-muted" x-text="st.nis || 'Tanpa NIS'"></div>
                                                </div>
                                            </div>
                                            <span class="text-[11px] font-mono shrink-0 ml-2 text-ink-muted" x-text="studentProgressLabel(st.id)"></span>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </template>
            </div>
        </aside>

        <!-- Panel Kanan: Daftar Materi & Input Nilai (8 kolom desktop) -->
        <main class="lg:col-span-8 bg-surface rounded-xl border border-line shadow-xs overflow-hidden flex flex-col min-h-[500px]">
            <!-- Header Siswa Terpilih -->
            <template x-if="selectedStudent">
                <div class="p-5 border-b border-line bg-surface-alt/40 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-bold text-ink" x-text="selectedStudent.name"></h2>
                            <x-badge variant="brand" x-text="'Kelas ' + (data?.className || '')"></x-badge>
                        </div>
                        <p class="text-xs text-ink-muted mt-0.5" x-text="selectedStudent.nis ? 'NIS: ' + selectedStudent.nis : 'Tanpa NIS'"></p>
                    </div>

                    <div class="w-48 text-right space-y-1">
                        <div class="flex items-center justify-between text-xs font-medium text-ink">
                            <span>Kemajuan</span>
                            <span class="font-bold text-brand" x-text="position.assessed + ' / ' + position.total + ' Materi'"></span>
                        </div>
                        <div class="w-full bg-line rounded-full h-2 overflow-hidden">
                            <div
                                class="bg-brand h-2 rounded-full transition-all duration-300"
                                :style="'width: ' + (position.total ? (position.assessed / position.total * 100) : 0) + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- State: Belum ada kelas / siswa dipilih -->
            <template x-if="!classId">
                <div class="p-12 text-center">
                    <x-empty-state
                        title="Pilih kelas terlebih dahulu"
                        description="Pilih kelas pada panel kiri untuk memuat daftar siswa dan materi praktik ibadah."
                    />
                </div>
            </template>

            <template x-if="classId && !selectedStudent && !loading">
                <div class="p-12 text-center">
                    <x-empty-state
                        title="Pilih siswa yang akan dinilai"
                        description="Klik salah satu siswa dari daftar di sebelah kiri atau tekan tombol '/' untuk mencari nama siswa."
                    />
                </div>
            </template>

            <!-- State: Kelas Belum Memiliki Kurikulum -->
            <template x-if="classId && selectedStudent && data && data.materials.length === 0 && !loading">
                <div class="p-12 text-center">
                    <x-empty-state
                        title="Belum ada kurikulum materi"
                        description="Tahun ajaran kelas ini belum memiliki materi kurikulum aktif."
                    >
                        <x-slot:action>
                            <a href="{{ route('materi') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-brand-strong">
                                <x-icon name="plus" size="16" />
                                <span>Susun Materi Kurikulum</span>
                            </a>
                        </x-slot:action>
                    </x-empty-state>
                </div>
            </template>

            <!-- Konten Daftar Materi (Accordion Bab & Subbab) -->
            <template x-if="selectedStudent && data && data.materials.length > 0">
                <div class="p-5 space-y-4 overflow-y-auto max-h-[calc(100vh-280px)]">
                    <template x-for="group in groups" :key="group.key">
                        <div class="rounded-xl border border-line bg-surface overflow-hidden shadow-xs">
                            <!-- Header Subbab (Klik untuk toggle) -->
                            <button
                                type="button"
                                @click="toggleGroup(group.key)"
                                class="w-full px-4 py-3 bg-surface-alt border-b border-line flex items-center justify-between text-left hover:bg-canvas transition-colors"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-ink" x-text="group.chapter"></span>
                                    <span class="text-ink-muted text-xs">&rsaquo;</span>
                                    <span class="text-xs font-semibold text-brand" x-text="group.subchapter"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] text-ink-muted" x-text="group.items.length + ' materi'"></span>
                                    <span :class="isGroupOpen(group.key) ? 'rotate-180' : ''" class="transition-transform duration-200 text-ink-muted">
                                        <x-icon name="chevron-down" size="16" />
                                    </span>
                                </div>
                            </button>

                            <!-- Daftar Baris Materi -->
                            <div x-show="isGroupOpen(group.key)" class="divide-y divide-line/60">
                                <template x-for="item in group.items" :key="item.material.id">
                                    <div
                                        class="p-3.5 flex flex-wrap items-center justify-between gap-3 hover:bg-canvas/50 transition-colors"
                                        :class="getRowMeta(item.material, item.index).isNext ? 'bg-brand-light/30 border-l-4 border-l-brand' : ''"
                                    >
                                        <!-- Info Materi -->
                                        <div class="flex items-center gap-3 min-w-[200px]">
                                            <span class="text-xs font-mono font-medium text-ink-muted w-6 text-center" x-text="item.index + 1"></span>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-semibold text-ink" x-text="item.material.title"></span>
                                                    <template x-if="item.material.weight !== 1.0">
                                                        <x-badge variant="brand" x-text="item.material.weight + 'x'"></x-badge>
                                                    </template>
                                                </div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <template x-if="getRowMeta(item.material, item.index).statusLabel">
                                                        <span
                                                            class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-[11px]"
                                                            :class="{
                                                                'bg-brand-soft text-brand-strong': getRowMeta(item.material, item.index).statusTone === 'brand',
                                                                'bg-success-soft text-emerald-700': getRowMeta(item.material, item.index).statusTone === 'success',
                                                                'bg-warning-soft text-amber-700': getRowMeta(item.material, item.index).statusTone === 'warning',
                                                                'bg-danger-soft text-danger': getRowMeta(item.material, item.index).statusTone === 'danger',
                                                                'bg-canvas text-muted': getRowMeta(item.material, item.index).statusTone === 'neutral'
                                                            }"
                                                            x-text="getRowMeta(item.material, item.index).statusLabel"
                                                        ></span>
                                                    </template>
                                                    <template x-if="getRowMeta(item.material, item.index).draft && getRowMeta(item.material, item.index).raw.trim() === ''">
                                                        <span class="text-[10px] text-ink-muted italic">Nilai akan dikosongkan saat disimpan</span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Editor Nilai Kesalahan -->
                                        <div class="flex items-center gap-3">
                                            <!-- Tombol 90 Cepat -->
                                            <button
                                                type="button"
                                                @click="setPerfect90(item.material, item.index)"
                                                :disabled="getRowMeta(item.material, item.index).isSaving"
                                                title="Lancar: 0 kesalahan (nilai 90), langsung simpan dan lanjut"
                                                class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100 font-bold text-xs tracking-wider shadow-2xs disabled:opacity-50"
                                            >
                                                90
                                            </button>

                                            <!-- Stepper Kesalahan -->
                                            <div class="flex items-center rounded-lg border border-line bg-surface overflow-hidden shadow-2xs">
                                                <button
                                                    type="button"
                                                    @click="step(item.material, -1)"
                                                    :disabled="getRowMeta(item.material, item.index).isSaving"
                                                    class="px-2 py-1 text-ink-muted hover:text-ink hover:bg-surface-alt border-r border-line disabled:opacity-40"
                                                    title="Kurangi kesalahan"
                                                >
                                                    -
                                                </button>
                                                <input
                                                    type="text"
                                                    inputmode="numeric"
                                                    :data-material-id="item.material.id"
                                                    :value="getRawMistakes(item.material.id)"
                                                    @input="onInputMistakes(item.material, $event.target.value)"
                                                    @keydown.enter.prevent="commit(item.material, item.index)"
                                                    @keydown.arrow-up.prevent="step(item.material, 1)"
                                                    @keydown.arrow-down.prevent="step(item.material, -1)"
                                                    @focus="$event.target.select()"
                                                    :disabled="getRowMeta(item.material, item.index).isSaving"
                                                    placeholder="0"
                                                    class="w-12 text-center text-sm font-semibold text-ink py-1 focus:outline-none focus:bg-brand-light/20"
                                                />
                                                <button
                                                    type="button"
                                                    @click="step(item.material, 1)"
                                                    :disabled="getRowMeta(item.material, item.index).isSaving"
                                                    class="px-2 py-1 text-ink-muted hover:text-ink hover:bg-surface-alt border-l border-line disabled:opacity-40"
                                                    title="Tambah kesalahan"
                                                >
                                                    +
                                                </button>
                                            </div>

                                            <!-- Tombol Batalkan / Kembalikan jika ada draft -->
                                            <template x-if="getRowMeta(item.material, item.index).draft">
                                                <button
                                                    type="button"
                                                    @click="discard(item.material)"
                                                    class="p-1.5 text-ink-muted hover:text-danger rounded hover:bg-danger-light"
                                                    title="Batalkan perubahan lokal"
                                                >
                                                    <x-icon name="refresh-cw" size="14" />
                                                </button>
                                            </template>

                                            <!-- Hasil Nilai -->
                                            <div class="w-12 text-right">
                                                <span class="text-base font-extrabold text-ink" x-text="'= ' + getRowMeta(item.material, item.index).scoreDisplay"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </main>
    </div>

    <script>
        try {
            localStorage.setItem('pib_assessment_mode', 'setoran');
            document.cookie = 'pib_assessment_mode=setoran; path=/; max-age=31536000; SameSite=Lax';
        } catch {}
    </script>
</x-app-layout>
