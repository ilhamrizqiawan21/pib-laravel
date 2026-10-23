<div>
    <x-slot:title>Beranda</x-slot:title>

    <x-page-header
        eyebrow="RINGKASAN KERJA"
        title="Selamat datang, {{ $teacherName }}"
        description="Ringkasan kemajuan penilaian praktik ibadah siswa dan pekerjaan yang dapat Anda lanjutkan hari ini."
    >
        <x-slot:actions>
            <div class="w-full sm:w-64">
                <select
                    aria-label="Pilih periode ringkasan"
                    wire:model.live="selected_academic_year_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-xs text-ink font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Periode</option>
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">
                            {{ $year->label() }} {{ $year->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </x-slot:actions>
    </x-page-header>

    <!-- Checklist Penyiapan Awal (Bila Belum Lengkap) -->
    @if (! $setup['isComplete'])
        <div class="mb-8 rounded-xl border border-brand/30 bg-brand-light/30 p-5 shadow-xs">
            <div class="mb-4">
                <h3 class="text-sm font-bold text-brand-strong uppercase tracking-wider">Langkah Penyiapan Awal</h3>
                <p class="text-xs text-ink-muted mt-0.5">Selesaikan urutan persiapan berikut agar proses penilaian berjalan lancar.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($setup['steps'] as $idx => $step)
                    <div class="rounded-lg border p-3.5 flex flex-col justify-between transition-colors {{ $step['done'] ? 'bg-surface/80 border-line text-ink' : 'bg-surface border-brand shadow-xs' }}">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <span class="text-xs font-bold text-ink">
                                    {{ $idx + 1 }}. {{ $step['title'] }}
                                </span>
                                @if ($step['done'])
                                    <span class="inline-flex items-center text-xs text-emerald-600 font-semibold" title="Selesai">
                                        <x-icon name="check-circle" size="16" />
                                    </span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-brand animate-pulse" title="Perlu dikerjakan"></span>
                                @endif
                            </div>
                            <p class="text-[11px] text-ink-muted leading-relaxed">{{ $step['detail'] }}</p>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-line/60 flex justify-end">
                            @if ($step['done'])
                                <a href="{{ $step['href'] }}" class="text-[11px] font-semibold text-ink-muted hover:text-ink">
                                    Lihat &rarr;
                                </a>
                            @else
                                <a href="{{ $step['href'] }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-brand-strong hover:text-brand-strong">
                                    <span>{{ $step['action'] }}</span>
                                    <span>&rarr;</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Kartu KPI Statistik Ringkas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-8">
        <!-- Siswa Aktif -->
        <x-card class="hover:border-line-hover transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Siswa Aktif</div>
                    <div class="text-3xl font-black text-ink mt-1.5">{{ $summary['total_students'] }}</div>
                    <div class="mt-2">
                        <a href="{{ route('siswa') }}" class="text-xs font-semibold text-brand-strong hover:underline inline-flex items-center gap-1">
                            <span>Kelola Siswa</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-brand-light text-brand-strong flex items-center justify-center">
                    <x-icon name="users" size="24" />
                </div>
            </div>
        </x-card>

        <!-- Kelas Aktif -->
        <x-card class="hover:border-line-hover transition-colors">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Kelas Aktif</div>
                    <div class="text-3xl font-black text-ink mt-1.5">{{ $summary['total_classes'] }}</div>
                    <div class="mt-2">
                        <a href="{{ route('kelas') }}" class="text-xs font-semibold text-brand-strong hover:underline inline-flex items-center gap-1">
                            <span>Lihat Kelas</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-brand-light text-brand-strong flex items-center justify-center">
                    <x-icon name="graduation-cap" size="24" />
                </div>
            </div>
        </x-card>

        <!-- Progres Penilaian -->
        <x-card class="hover:border-line-hover transition-colors">
            <div class="flex items-center justify-between">
                <div class="w-full">
                    <div class="flex items-center justify-between">
                        <div class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Progres Penilaian</div>
                        <span class="text-xs font-bold text-brand-strong">{{ $summary['overall_percent'] }}%</span>
                    </div>
                    <div class="text-2xl font-black text-ink mt-1">
                        {{ $summary['total_assessed'] }} <span class="text-xs font-normal text-ink-muted">/ {{ $summary['total_expected'] }} nilai</span>
                    </div>
                    <div class="w-full bg-line rounded-full h-2 overflow-hidden mt-3">
                        <div class="bg-brand h-2 rounded-full transition-all" style="width: {{ $summary['overall_percent'] }}%"></div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>

    <!-- Grid Utama: Prioritas Kelas & Aktivitas Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8">
        <!-- Prioritas Kelas (8 kolom desktop) -->
        <div class="lg:col-span-8 bg-surface rounded-xl border border-line p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4 border-b border-line pb-4">
                    <div>
                        <h3 class="text-base font-bold text-ink">Prioritas Kelas</h3>
                        <p class="text-xs text-ink-muted mt-0.5">Kelas dengan progres penilaian terendah yang membutuhkan perhatian.</p>
                    </div>
                    <a href="{{ route('rekap') }}" class="text-xs font-semibold text-brand-strong hover:underline">
                        Lihat Rekap Lengkap &rarr;
                    </a>
                </div>

                @if (empty($priorityClasses))
                    <x-empty-state
                        title="Semua penilaian selesai"
                        description="Seluruh kelas aktif pada periode ini telah selesai dinilai 100% atau belum ada kelas."
                    />
                @else
                    <div class="space-y-4">
                        @foreach ($priorityClasses as $cls)
                            <div class="p-4 rounded-xl border border-line bg-surface-alt/40 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 hover:border-line-hover transition-colors">
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-sm font-bold text-ink">{{ $cls['name'] }}</h4>
                                        <span class="text-xs text-ink-muted">&bull; {{ $cls['period'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-ink-muted">
                                        <span>{{ $cls['students_count'] }} Siswa</span>
                                        <span>&bull;</span>
                                        <span>{{ $cls['assessed_count'] }} / {{ $cls['expected_count'] }} Nilai</span>
                                    </div>
                                    <div class="w-full max-w-md bg-line rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-brand h-1.5 rounded-full" style="width: {{ $cls['percent'] }}%"></div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <span class="text-sm font-bold text-brand-strong">{{ $cls['percent'] }}%</span>
                                    <a
                                        href="{{ route('penilaian.setoran', ['classId' => $cls['id']]) }}"
                                        class="inline-flex items-center gap-1 rounded-lg bg-brand-strong px-3 py-1.5 text-xs font-semibold text-white shadow-2xs hover:bg-brand-strong transition-colors"
                                    >
                                        <span>Lanjutkan</span>
                                        <span>&rarr;</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Aktivitas Terbaru (4 kolom desktop) -->
        <div class="lg:col-span-4 bg-surface rounded-xl border border-line p-6 shadow-xs flex flex-col">
            <div class="mb-4 border-b border-line pb-4">
                <h3 class="text-base font-bold text-ink">Aktivitas Terbaru</h3>
                <p class="text-xs text-ink-muted mt-0.5">Catatan riwayat perubahan data sistem.</p>
            </div>

            @if ($recentActivities->isEmpty())
                <x-empty-state
                    title="Belum ada aktivitas"
                    description="Penyimpanan nilai dan perubahan data akan tercatat di sini."
                />
            @else
                <ul class="space-y-3.5 divide-y divide-line/40">
                    @foreach ($recentActivities as $act)
                        <li class="pt-3 first:pt-0">
                            <div class="text-xs font-semibold text-ink leading-tight">{{ $act->summary }}</div>
                            <div class="text-[10px] text-ink-muted mt-1">
                                {{ $act->created_at?->diffForHumans() }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>

