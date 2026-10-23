<div>
    <x-slot:title>Rekapitulasi Nilai</x-slot:title>

    <x-page-header
        eyebrow="REKAPITULASI"
        title="Rekapitulasi Nilai"
        description="Pantau kemajuan, nilai rata-rata berbobot, dan status kelengkapan penilaian praktik ibadah siswa."
    >
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-lg border border-line bg-surface p-1 text-xs font-medium">
                    <button
                        type="button"
                        wire:click="$set('active_tab', 'summary')"
                        class="rounded-md px-3 py-1.5 transition-colors {{ $active_tab === 'summary' ? 'bg-brand-light font-bold text-brand shadow-xs' : 'text-ink-muted hover:text-ink' }}"
                    >
                        Ringkasan Siswa
                    </button>
                    <button
                        type="button"
                        wire:click="$set('active_tab', 'matrix')"
                        class="rounded-md px-3 py-1.5 transition-colors {{ $active_tab === 'matrix' ? 'bg-brand-light font-bold text-brand shadow-xs' : 'text-ink-muted hover:text-ink' }}"
                    >
                        Matriks Kelas
                    </button>
                </span>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Bar Filter Terpadu (Query URL) -->
    <div class="mb-6 rounded-xl border border-line bg-surface p-4 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Filter Tahun Ajaran -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-ink-muted mb-1">Tahun Ajaran</label>
                <select
                    wire:model.live="academic_year_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-xs text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">
                            {{ $year->label() }} {{ $year->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Kelas -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-ink-muted mb-1">Kelas</label>
                <select
                    wire:model.live="class_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-xs text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Kelas</option>
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Siswa -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-ink-muted mb-1">Cari Siswa</label>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="student"
                    placeholder="Nama siswa..."
                    class="w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-xs text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                />
            </div>

            <!-- Tombol Reset -->
            <div class="flex items-end">
                <x-button variant="outline" size="sm" wire:click="resetFilters" class="w-full justify-center">
                    <x-icon name="refresh-cw" size="14" />
                    <span>Reset Filter</span>
                </x-button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div wire:loading.delay class="w-full mb-6">
        <x-loading-state message="Menghitung rekapitulasi data nilai..." />
    </div>

    <div wire:loading.remove>
        <!-- TAB 1: RINGKASAN SISWA -->
        @if ($active_tab === 'summary')
            <!-- Ringkasan Kelas (Bila ada data) -->
            @if (!empty($recap['classes']))
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @php
                        $totStudents = array_sum(array_column($recap['classes'], 'students_count'));
                        $totAssessed = array_sum(array_column($recap['classes'], 'assessed'));
                        $totExpected = array_sum(array_column($recap['classes'], 'expected'));
                        $avgValues = array_filter(array_column($recap['classes'], 'average'), fn($v) => $v !== null);
                        $overallAvg = count($avgValues) > 0 ? round(array_sum($avgValues) / count($avgValues), 1) : null;
                        $overallPercent = $totExpected > 0 ? ($totAssessed >= $totExpected ? 100 : min(99, (int) round($totAssessed / $totExpected * 100))) : 0;
                    @endphp
                    <x-card>
                        <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Total Siswa</div>
                        <div class="text-2xl font-black text-ink mt-1">{{ $totStudents }}</div>
                        <p class="text-[11px] text-ink-muted mt-0.5">Siswa aktif dalam cakupan</p>
                    </x-card>
                    <x-card>
                        <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Rata-rata Nilai</div>
                        <div class="text-2xl font-black text-brand mt-1">{{ $overallAvg !== null ? $overallAvg : '—' }}</div>
                        <p class="text-[11px] text-ink-muted mt-0.5">Rata-rata berbobot keseluruhan</p>
                    </x-card>
                    <x-card>
                        <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Nilai Terisi</div>
                        <div class="text-2xl font-black text-ink mt-1">{{ $totAssessed }} <span class="text-sm font-normal text-ink-muted">/ {{ $totExpected }}</span></div>
                        <p class="text-[11px] text-ink-muted mt-0.5">Materi penilaian terisi</p>
                    </x-card>
                    <x-card>
                        <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Kelengkapan</div>
                        <div class="text-2xl font-black text-emerald-600 mt-1">{{ $overallPercent }}%</div>
                        <p class="text-[11px] text-ink-muted mt-0.5">Progres penilaian keseluruhan</p>
                    </x-card>
                </div>
            @endif

            @if (empty($recap['students']))
                <x-card>
                    <x-empty-state
                        title="Tidak ada data siswa"
                        description="Tidak ditemukan data siswa yang cocok dengan filter yang dipilih."
                    >
                        <x-slot:action>
                            <x-button variant="primary" size="sm" wire:click="resetFilters">
                                <span>Reset Filter</span>
                            </x-button>
                        </x-slot:action>
                    </x-empty-state>
                </x-card>
            @else
                <div class="bg-surface rounded-xl border border-line shadow-xs overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-line bg-surface-alt text-xs font-semibold uppercase tracking-wider text-ink-muted">
                                    <th scope="col" class="py-3 px-4 w-12 text-center">No</th>
                                    <th scope="col" class="py-3 px-4">Nama Siswa</th>
                                    <th scope="col" class="py-3 px-4 w-28">NIS</th>
                                    <th scope="col" class="py-3 px-4 w-28">Kelas</th>
                                    <th scope="col" class="py-3 px-4 w-28 text-center">Terisi</th>
                                    <th scope="col" class="py-3 px-4 w-24 text-right">Total</th>
                                    <th scope="col" class="py-3 px-4 w-28 text-right">Rata-rata</th>
                                    <th scope="col" class="py-3 px-4 w-40">Progres</th>
                                    <th scope="col" class="py-3 px-4 w-36">Status</th>
                                    <th scope="col" class="py-3 px-4 w-24 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line/60">
                                @foreach ($recap['students'] as $idx => $st)
                                    <tr class="hover:bg-canvas/50 transition-colors {{ $selected_student_id === $st['id'] ? 'bg-brand-light/20' : '' }}">
                                        <td class="py-3 px-4 text-center text-xs font-mono text-ink-muted">{{ $idx + 1 }}</td>
                                        <td class="py-3 px-4 font-semibold text-ink">
                                            <button
                                                type="button"
                                                wire:click="selectStudent({{ $st['id'] }})"
                                                class="hover:text-brand hover:underline text-left cursor-pointer"
                                            >
                                                {{ $st['name'] }}
                                            </button>
                                        </td>
                                        <td class="py-3 px-4 text-xs font-mono text-ink-muted">{{ $st['nis'] ?: '—' }}</td>
                                        <td class="py-3 px-4 text-xs font-medium text-ink">{{ $st['class_name'] }}</td>
                                        <td class="py-3 px-4 text-xs text-center font-mono font-medium text-ink">
                                            {{ $st['assessed'] }} / {{ $st['expected'] }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-right font-mono font-bold text-ink">
                                            {{ $st['total'] ?: '—' }}
                                        </td>
                                        <td class="py-3 px-4 text-xs text-right font-mono font-bold text-brand">
                                            {{ $st['average'] !== null ? number_format($st['average'], 1) : '—' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-full bg-line rounded-full h-2 overflow-hidden">
                                                    <div
                                                        class="bg-brand h-2 rounded-full transition-all"
                                                        style="width: {{ $st['percent'] }}%"
                                                    ></div>
                                                </div>
                                                <span class="text-xs font-mono font-semibold text-ink-muted w-9 text-right">{{ $st['percent'] }}%</span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <x-status-badge :status="$st['status']" />
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <x-button
                                                variant="ghost"
                                                size="sm"
                                                wire:click="selectStudent({{ $st['id'] }})"
                                                title="Lihat rincian materi"
                                            >
                                                <span>Detail</span>
                                            </x-button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Modal / Panel Detail Siswa -->
            @if ($selected_student_id && $selectedStudentModel)
                <div class="mt-8 rounded-xl border border-line bg-surface p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-line pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-ink">
                                    Detail Penilaian: {{ $selectedStudentModel->name }}
                                </h3>
                                <x-badge variant="brand">{{ $selectedStudentModel->schoolClass->name }}</x-badge>
                            </div>
                            <p class="text-xs text-ink-muted mt-0.5">
                                NIS: {{ $selectedStudentModel->nis ?: 'Tanpa NIS' }} &bull; Periode: {{ $selectedStudentModel->schoolClass->academicYear?->label() }}
                            </p>
                        </div>
                        <x-button variant="outline" size="sm" wire:click="closeStudentDetail">
                            <x-icon name="x" size="16" />
                            <span>Tutup Detail</span>
                        </x-button>
                    </div>

                    @if (empty($studentDetails))
                        <x-empty-state
                            title="Belum ada detail penilaian"
                            description="Siswa ini belum memiliki materi penilaian yang tersedia."
                        />
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-line bg-surface-alt font-semibold uppercase text-ink-muted">
                                        <th scope="col" class="py-2.5 px-3">Bab & Subbab</th>
                                        <th scope="col" class="py-2.5 px-3">Materi Penilaian</th>
                                        <th scope="col" class="py-2.5 px-3 text-center">Bobot</th>
                                        <th scope="col" class="py-2.5 px-3 text-center">Kesalahan</th>
                                        <th scope="col" class="py-2.5 px-3 text-right">Nilai</th>
                                        <th scope="col" class="py-2.5 px-3">Status</th>
                                        <th scope="col" class="py-2.5 px-3">Tanggal Dinilai</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/60">
                                    @foreach ($studentDetails as $det)
                                        <tr class="hover:bg-canvas/50">
                                            <td class="py-2.5 px-3 text-ink-muted">
                                                {{ $det['chapter'] }} &rsaquo; {{ $det['subchapter'] }}
                                            </td>
                                            <td class="py-2.5 px-3 font-semibold text-ink">
                                                {{ $det['assessment'] }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-mono">
                                                {{ $det['weight'] ? $det['weight'] . 'x' : '1x' }}
                                            </td>
                                            <td class="py-2.5 px-3 text-center font-mono">
                                                {{ $det['mistakes'] !== null ? $det['mistakes'] : '—' }}
                                            </td>
                                            <td class="py-2.5 px-3 text-right font-mono font-bold text-ink">
                                                {{ $det['score'] !== null ? $det['score'] : '—' }}
                                            </td>
                                            <td class="py-2.5 px-3">
                                                @if ($det['score'] !== null)
                                                    <span class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-[10px] bg-success-soft text-emerald-700">
                                                        Dinilai
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-[10px] bg-canvas text-muted">
                                                        Belum dinilai
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="py-2.5 px-3 text-ink-muted">
                                                {{ $det['assessed_at'] ? \Carbon\Carbon::parse($det['assessed_at'])->translatedFormat('d M Y H:i') : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @endif

        <!-- TAB 2: MATRIKS KELAS -->
        @elseif ($active_tab === 'matrix')
            @if (! $class_id)
                <x-card>
                    <x-empty-state
                        title="Pilih kelas terlebih dahulu"
                        description="Pilih salah satu kelas pada filter di atas untuk menampilkan matriks perbandingan nilai seluruh siswa."
                    />
                </x-card>
            @elseif (! $matrix || empty($matrix['materials']))
                <x-card>
                    <x-empty-state
                        title="Belum ada kurikulum untuk kelas ini"
                        description="Kelas terpilih belum memiliki materi kurikulum aktif untuk ditampilkan pada matriks."
                    />
                </x-card>
            @else
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-ink">Matriks Penilaian Kelas</h3>
                            <p class="text-xs text-ink-muted">
                                {{ count($matrix['students']) }} Siswa &bull; {{ count($matrix['materials']) }} Materi Penilaian
                            </p>
                        </div>
                        <!-- Legenda Warna -->
                        <div class="flex flex-wrap items-center gap-3 text-xs text-ink-muted">
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span>
                                <span>80 – 90</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded bg-teal-100 border border-teal-300"></span>
                                <span>70 – 79</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span>
                                <span>&lt; 70</span>
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded bg-slate-100 border border-slate-300"></span>
                                <span>Belum Dinilai</span>
                            </span>
                        </div>
                    </div>

                    <div class="bg-surface rounded-xl border border-line shadow-xs overflow-hidden">
                        <div class="overflow-x-auto max-h-[calc(100vh-280px)]">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead class="sticky top-0 bg-surface-alt z-20">
                                    <tr class="border-b border-line text-ink-muted">
                                        <th scope="col" class="py-2.5 px-3 w-10 text-center bg-surface-alt sticky left-0 z-30 border-r border-line">No</th>
                                        <th scope="col" class="py-2.5 px-3 w-48 bg-surface-alt sticky left-10 z-30 border-r border-line">Nama Siswa</th>
                                        @foreach ($matrix['materials'] as $mat)
                                            <th scope="col" class="py-2.5 px-3 text-center border-r border-line/60 whitespace-nowrap" title="{{ $mat['chapter'] }} › {{ $mat['subchapter'] }} › {{ $mat['title'] }}">
                                                <div class="font-bold text-ink truncate max-w-[120px]">{{ $mat['title'] }}</div>
                                                <div class="text-[10px] text-ink-muted">{{ $mat['weight'] !== 1.0 ? $mat['weight'] . 'x' : '' }}</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line/60">
                                    @foreach ($matrix['students'] as $sIndex => $s)
                                        <tr class="hover:bg-canvas/40 transition-colors">
                                            <td class="py-2 px-3 text-center font-mono text-ink-muted sticky left-0 bg-surface z-10 border-r border-line">
                                                {{ $sIndex + 1 }}
                                            </td>
                                            <td class="py-2 px-3 font-semibold text-ink sticky left-10 bg-surface z-10 border-r border-line truncate max-w-[180px]">
                                                {{ $s['name'] }}
                                            </td>
                                            @foreach ($matrix['materials'] as $mat)
                                                @php
                                                    $cellKey = "{$s['id']}:{$mat['id']}";
                                                    $cell = $matrix['cells'][$cellKey] ?? null;
                                                    $sc = $cell['score'] ?? null;
                                                    $bgClass = match (true) {
                                                        $sc === null => 'bg-slate-50 text-ink-muted',
                                                        $sc >= 80 => 'bg-emerald-50 text-emerald-800 font-bold',
                                                        $sc >= 70 => 'bg-teal-50 text-teal-800 font-bold',
                                                        default => 'bg-amber-50 text-amber-800 font-bold',
                                                    };
                                                @endphp
                                                <td class="py-2 px-3 text-center font-mono border-r border-line/60 {{ $bgClass }}">
                                                    {{ $sc !== null ? $sc : '—' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>

