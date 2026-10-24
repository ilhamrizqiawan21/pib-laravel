<div>
    <x-slot:title>Laporan &amp; Backup</x-slot:title>

    <!-- Header Halaman -->
    <x-page-header
        eyebrow="UNDUHAN & ARSIP"
        title="Laporan & Backup"
        description="Unduh berkas rekap nilai, cetak rapor siswa, dan kelola salinan data."
    >
        <x-slot:actions>
            <x-button variant="secondary" size="sm" href="{{ route('rekap', $this->queryStringParams) }}" wire:navigate>
                <x-icon name="bar-chart-3" size="16" />
                <span>Tinjau di Rekap</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    <!-- Filter Cakupan Laporan -->
    <x-card title="Cakupan Laporan" description="Pilih parameter untuk membatasi data yang akan diunduh." class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Tahun Ajaran -->
            <div>
                <label for="filter-year" class="block text-xs font-semibold text-ink mb-1.5">Tahun Ajaran / Periode</label>
                <select id="filter-year" wire:model.live="academicYearId" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    <option value="">Semua Periode</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}">{{ $y->name }} · {{ $y->semester->value }}{{ $y->is_active ? ' (Aktif)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label for="filter-class" class="block text-xs font-semibold text-ink mb-1.5">Kelas</label>
                <select id="filter-class" wire:model.live="classId" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Nama Siswa -->
            <div>
                <label for="filter-student" class="block text-xs font-semibold text-ink mb-1.5">Cari Nama Siswa</label>
                <input type="text" id="filter-student" wire:model.live.debounce.300ms="student" placeholder="Ketik nama siswa..." class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
            </div>

            <!-- Bab -->
            <div>
                <label for="filter-chapter" class="block text-xs font-semibold text-ink mb-1.5">Bab</label>
                <select id="filter-chapter" wire:model.live="chapter" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    <option value="">Semua Bab</option>
                    @foreach($chapters as $ch)
                        <option value="{{ $ch }}">{{ $ch }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subbab -->
            <div>
                <label for="filter-subchapter" class="block text-xs font-semibold text-ink mb-1.5">Subbab</label>
                <select id="filter-subchapter" wire:model.live="subchapter" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    <option value="">Semua Subbab</option>
                    @foreach($subchapters as $sub)
                        <option value="{{ $sub }}">{{ $sub }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Materi -->
            <div>
                <label for="filter-assessment" class="block text-xs font-semibold text-ink mb-1.5">Materi</label>
                <select id="filter-assessment" wire:model.live="assessmentId" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                    <option value="">Semua Materi</option>
                    @foreach($assessments as $a)
                        <option value="{{ $a->id }}">{{ $a->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    <!-- Kartu Unduhan -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <!-- Unduh Excel -->
        <div class="bg-surface rounded-[14px] border border-line shadow-sm p-6 flex flex-col justify-between hover:border-brand/50 hover:shadow-md transition-all">
            <div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-4">
                    <x-icon name="file-spreadsheet" size="24" />
                </div>
                <h3 class="text-lg font-bold text-ink mb-1 tracking-tight">Laporan Excel</h3>
                <p class="text-xs text-muted mb-4 leading-relaxed">
                    Buku kerja lengkap berisi 5 lembar: Ringkasan, Laporan per Materi, Rekap Siswa, Rekap Kelas, dan Detail Nilai mentah.
                </p>
            </div>
            <div>
                <x-button variant="primary" class="w-full font-bold" href="{{ route('laporan.excel', $this->queryStringParams) }}" target="_blank">
                    <x-icon name="file-spreadsheet" size="17" />
                    <span>Unduh Excel (.xlsx)</span>
                </x-button>
            </div>
        </div>

        <!-- Unduh PDF Kelas -->
        <div class="bg-surface rounded-[14px] border border-line shadow-sm p-6 flex flex-col justify-between hover:border-brand/50 hover:shadow-md transition-all">
            <div>
                <div class="w-12 h-12 rounded-xl bg-red-50 text-danger flex items-center justify-center mb-4">
                    <x-icon name="file-pdf" size="24" />
                </div>
                <h3 class="text-lg font-bold text-ink mb-1 tracking-tight">Laporan PDF Kelas</h3>
                <p class="text-xs text-muted mb-4 leading-relaxed">
                    Dokumen siap cetak format A4 portrait dengan identitas sekolah, rekap per materi dan kelas, serta rata-rata berbobot.
                </p>
            </div>
            <div>
                <x-button variant="secondary" class="w-full font-bold" href="{{ route('laporan.pdf', $this->queryStringParams) }}" target="_blank">
                    <x-icon name="file-pdf" size="17" />
                    <span>Unduh PDF Kelas</span>
                </x-button>
            </div>
        </div>

        <!-- Unduh Rapor Per Siswa -->
        <div class="bg-surface rounded-[14px] border border-line shadow-sm p-6 flex flex-col justify-between hover:border-brand/50 hover:shadow-md transition-all">
            <div>
                <div class="w-12 h-12 rounded-xl bg-brand-soft text-brand-strong flex items-center justify-center mb-4">
                    <x-icon name="file-pdf" size="24" />
                </div>
                <h3 class="text-lg font-bold text-ink mb-1 tracking-tight">Rapor Per Siswa</h3>
                <p class="text-xs text-muted mb-3 leading-relaxed">
                    Lembar rapor individual mencakup seluruh materi, nilai, rata-rata berbobot, dan blok paraf guru penilai.
                </p>
                <div class="mb-4">
                    <label for="select-student" class="block text-xs font-semibold text-ink mb-1.5">Pilih Siswa</label>
                    <select id="select-student" wire:model.live="selectedStudentId" class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand">
                        <option value="">-- Pilih salah satu siswa --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->schoolClass?->name ?? 'Tanpa kelas' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                @if($selectedStudentId)
                    <x-button variant="primary" class="w-full font-bold" href="{{ route('laporan.pdf.student', ['student' => $selectedStudentId]) }}" target="_blank">
                        <x-icon name="file-pdf" size="17" />
                        <span>Unduh Rapor Siswa</span>
                    </x-button>
                @else
                    <x-button variant="secondary" disabled class="w-full opacity-60">
                        <x-icon name="file-pdf" size="17" />
                        <span>Pilih siswa terlebih dahulu</span>
                    </x-button>
                @endif
            </div>
        </div>
    </div>

    <!-- Cadangan Data Sistem (Bagian 2 - Cadangan Data) -->
    <x-card title="Cadangan Data (Backup)" description="Pencadangan mencakup seluruh data master sekolah, kelas, siswa, materi kurikulum, nilai, dan log audit.">
        <x-slot:actions>
            <div class="flex items-center gap-2">
                <x-button type="button" variant="primary" size="sm" wire:click="backupNow" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="backupNow" class="inline-flex items-center gap-1.5">
                        <x-icon name="download-cloud" size="15" />
                        <span>Cadangkan Sekarang</span>
                    </span>
                    <span wire:loading wire:target="backupNow">Menyiapkan…</span>
                </x-button>
                <x-button variant="secondary" size="sm" href="{{ route('backup.download') }}">
                    <x-icon name="download" size="15" />
                    <span>Unduh Backup (.json)</span>
                </x-button>
            </div>
        </x-slot:actions>

        @if(session('backup_status'))
            <div class="mb-4">
                <x-alert variant="success">
                    {{ session('backup_status') }}
                </x-alert>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
            <div class="bg-canvas border border-line rounded-xl p-3.5">
                <div class="text-xs text-muted font-medium">Backup Otomatis Terakhir</div>
                <div class="text-sm font-bold text-ink mt-0.5">
                    {{ $backupStatus['last_backup_time'] ? \Illuminate\Support\Carbon::parse($backupStatus['last_backup_time'])->translatedFormat('d M Y, H:i') : 'Belum ada' }}
                </div>
            </div>
            <div class="bg-canvas border border-line rounded-xl p-3.5">
                <div class="text-xs text-muted font-medium">Jadwal & Retensi</div>
                <div class="text-sm font-bold text-ink mt-0.5">
                    Tiap {{ $backupStatus['interval_hours'] }} jam · simpan {{ $backupStatus['keep'] }} file
                </div>
            </div>
            <div class="bg-canvas border border-line rounded-xl p-3.5">
                <div class="text-xs text-muted font-medium">Berkas Tersimpan</div>
                <div class="text-sm font-bold text-ink mt-0.5">
                    {{ $backupStatus['count'] }} berkas di server
                </div>
            </div>
        </div>
    </x-card>
</div>
