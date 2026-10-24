<div>
    <x-slot:title>Beranda</x-slot:title>

    <x-page-header eyebrow="RINGKASAN KERJA" title="Selamat datang, {{ $teacherName }}" description="Berikut ringkasan penilaian praktik ibadah periode ini.">
        <x-slot:actions>
            <select aria-label="Pilih periode ringkasan" wire:model.live="selected_academic_year_id" class="min-h-11 w-full rounded-xl border border-line bg-surface px-3 text-xs font-semibold text-ink sm:w-52">
                <option value="">Semua Periode</option>
                @foreach ($academicYears as $year)
                    <option value="{{ $year->id }}">{{ $year->label() }} {{ $year->is_active ? '(Aktif)' : '' }}</option>
                @endforeach
            </select>
            <a href="{{ route('penilaian') }}" class="hidden min-h-11 items-center gap-2 rounded-xl bg-brand-strong px-4 text-xs font-bold text-white hover:bg-brand sm:inline-flex"><x-icon name="plus" size="16" /> Penilaian baru</a>
        </x-slot:actions>
    </x-page-header>

    <section class="relative mb-5 flex flex-col gap-5 overflow-hidden rounded-[18px] bg-gradient-to-r from-[#0e766d] to-[#129b8d] px-6 py-6 text-white shadow-[0_12px_30px_rgba(15,118,110,.17)] sm:flex-row sm:items-center sm:justify-between" aria-labelledby="continue-heading">
        <div class="pointer-events-none absolute -top-36 right-[12%] h-64 w-64 rounded-full border border-white/15 shadow-[0_0_0_28px_rgba(255,255,255,.05),0_0_0_58px_rgba(255,255,255,.03)]"></div>
        <div class="relative">
            <p class="text-[11px] font-bold uppercase tracking-wider text-teal-100">LANJUTKAN PENILAIAN</p>
            @if (! empty($priorityClasses))
                <h2 id="continue-heading" class="mt-2 text-xl font-bold tracking-tight">{{ $priorityClasses[0]['name'] }}</h2>
                <p class="mt-1 text-xs text-teal-50">{{ $priorityClasses[0]['assessed_count'] }} dari {{ $priorityClasses[0]['expected_count'] }} nilai terisi · {{ $priorityClasses[0]['period'] }}</p>
            @else
                <h2 id="continue-heading" class="mt-2 text-xl font-bold tracking-tight">Mulai penilaian siswa</h2>
                <p class="mt-1 text-xs text-teal-50">Pilih kelas dan siswa untuk mengisi nilai praktik ibadah.</p>
            @endif
        </div>
        <a href="{{ ! empty($priorityClasses) ? route('penilaian.setoran', ['classId' => $priorityClasses[0]['id']]) : route('penilaian') }}" class="relative inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 text-xs font-bold text-brand-strong shadow-sm hover:bg-teal-50">Lanjutkan penilaian <span aria-hidden="true">&rarr;</span></a>
    </section>

    <section class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Statistik periode">
        <x-card class="!p-4 sm:!p-5">
            <div class="flex items-start justify-between gap-2"><span class="text-[11px] font-bold uppercase tracking-wide text-muted">Kelas Aktif</span><span class="rounded-[10px] bg-brand-soft p-2 text-brand-strong"><x-icon name="graduation-cap" size="18" /></span></div>
            <div class="mt-2 text-[26px] font-extrabold tracking-tight text-ink">{{ $summary['total_classes'] }}</div>
            <a href="{{ route('kelas') }}" class="mt-1 inline-block text-[11px] font-semibold text-brand-strong hover:underline">Lihat kelas &rarr;</a>
        </x-card>
        <x-card class="!p-4 sm:!p-5">
            <div class="flex items-start justify-between gap-2"><span class="text-[11px] font-bold uppercase tracking-wide text-muted">Siswa Aktif</span><span class="rounded-[10px] bg-brand-soft p-2 text-brand-strong"><x-icon name="users" size="18" /></span></div>
            <div class="mt-2 text-[26px] font-extrabold tracking-tight text-ink">{{ $summary['total_students'] }}</div>
            <a href="{{ route('siswa') }}" class="mt-1 inline-block text-[11px] font-semibold text-brand-strong hover:underline">Kelola siswa &rarr;</a>
        </x-card>
        <x-card class="!p-4 sm:!p-5">
            <div class="flex items-start justify-between gap-2"><span class="text-[11px] font-bold uppercase tracking-wide text-muted">Nilai Terisi</span><span class="rounded-[10px] bg-brand-soft p-2 text-brand-strong"><x-icon name="clipboard-check" size="18" /></span></div>
            <div class="mt-2 text-[26px] font-extrabold tracking-tight text-ink">{{ $summary['total_assessed'] }}</div>
            <div class="mt-1 text-[11px] text-muted">Dari {{ $summary['total_expected'] }} penilaian</div>
        </x-card>
        <x-card class="!p-4 sm:!p-5">
            <div class="flex items-start justify-between gap-2"><span class="text-[11px] font-bold uppercase tracking-wide text-muted">Progres Periode</span><span class="rounded-[10px] bg-brand-soft p-2 text-brand-strong"><x-icon name="bar-chart-3" size="18" /></span></div>
            <div class="mt-2 text-[26px] font-extrabold tracking-tight text-ink">{{ $summary['overall_percent'] }}<span class="ml-0.5 text-sm">%</span></div>
            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-canvas" role="progressbar" aria-label="Progres penilaian periode" aria-valuenow="{{ $summary['overall_percent'] }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-brand" style="width: {{ $summary['overall_percent'] }}%"></div></div>
        </x-card>
    </section>

    <div class="mb-6 grid gap-4 lg:grid-cols-[minmax(0,1.6fr)_minmax(290px,.9fr)]">
        <section class="rounded-[15px] border border-line bg-surface p-5 shadow-sm" aria-labelledby="priority-heading">
            <div class="mb-3 flex flex-wrap items-start justify-between gap-2"><div><h2 id="priority-heading" class="text-sm font-bold text-ink">Prioritas Kelas</h2><p class="mt-1 text-[11px] text-muted">Kelas yang masih memiliki penilaian untuk diselesaikan.</p></div><a href="{{ route('rekap') }}" class="text-[11px] font-bold text-brand-strong hover:underline">Lihat rekap &rarr;</a></div>
            @forelse ($priorityClasses as $cls)
                <div class="flex items-center justify-between gap-4 border-t border-line py-3 first:border-0">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2"><a href="{{ route('penilaian.setoran', ['classId' => $cls['id']]) }}" class="text-xs font-bold text-ink hover:text-brand-strong hover:underline">{{ $cls['name'] }}</a>@if ($loop->first && $cls['percent'] < 50)<span class="rounded-md bg-warning-soft px-2 py-0.5 text-[10px] font-semibold text-warning">Perlu perhatian</span>@endif</div>
                        <p class="mt-1 text-[11px] text-muted">{{ $cls['students_count'] }} siswa · {{ $cls['assessed_count'] }} dari {{ $cls['expected_count'] }} nilai</p>
                        <div class="mt-2 h-1.5 max-w-md overflow-hidden rounded-full bg-canvas" role="progressbar" aria-label="Progres {{ $cls['name'] }}" aria-valuenow="{{ $cls['percent'] }}" aria-valuemin="0" aria-valuemax="100"><div class="h-full rounded-full bg-brand" style="width: {{ $cls['percent'] }}%"></div></div>
                    </div><span class="text-xs font-extrabold text-brand-strong">{{ $cls['percent'] }}%</span>
                </div>
            @empty
                <x-empty-state title="Belum ada kelas prioritas" description="Kelas dengan penilaian yang belum selesai akan muncul di sini." />
            @endforelse
        </section>
        <section class="rounded-[15px] border border-line bg-surface p-5 shadow-sm" aria-labelledby="activity-heading">
            <div class="mb-3 flex items-start justify-between gap-2"><div><h2 id="activity-heading" class="text-sm font-bold text-ink">Aktivitas Terbaru</h2><p class="mt-1 text-[11px] text-muted">Catatan pekerjaan terakhir.</p></div>@can('admin')<a href="{{ route('admin.audit') }}" class="text-[11px] font-bold text-brand-strong hover:underline">Semua &rarr;</a>@endcan</div>
            @forelse ($recentActivities as $act)
                <div class="flex gap-3 border-t border-line py-3 first:border-0"><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand-strong"><x-icon name="check" size="14" /></span><div class="min-w-0"><p class="text-[11px] font-semibold text-ink">{{ $act->summary }}</p><time class="mt-1 block text-[10px] text-muted" datetime="{{ $act->created_at?->toIso8601String() }}">{{ $act->created_at?->diffForHumans() }}</time></div></div>
            @empty
                <x-empty-state title="Belum ada aktivitas" description="Penyimpanan nilai dan perubahan data akan tercatat di sini." />
            @endforelse
        </section>
    </div>

    @if (! $setup['isComplete'])
        <section class="rounded-[15px] border border-line bg-surface p-5 shadow-sm" aria-labelledby="setup-heading">
            <h2 id="setup-heading" class="text-sm font-bold text-ink">Langkah Penyiapan Awal</h2><p class="mt-1 text-xs text-muted">Selesaikan langkah berikut agar penilaian dapat berjalan.</p>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($setup['steps'] as $idx => $step)
                    <div class="flex flex-col justify-between rounded-xl border p-4 {{ $step['done'] ? 'border-line bg-canvas/40' : 'border-brand/30 bg-brand-soft/20' }}">
                        <div><div class="flex items-start justify-between gap-2"><h3 class="text-xs font-bold text-ink">{{ $idx + 1 }}. {{ $step['title'] }}</h3><span class="text-[10px] font-bold {{ $step['done'] ? 'text-success' : 'text-warning' }}">{{ $step['done'] ? 'Selesai' : 'Perlu dikerjakan' }}</span></div><p class="mt-2 text-[11px] leading-relaxed text-muted">{{ $step['detail'] }}</p></div>
                        <a href="{{ $step['href'] }}" class="mt-3 text-[11px] font-bold text-brand-strong hover:underline">{{ $step['done'] ? 'Lihat' : $step['action'] }} &rarr;</a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
