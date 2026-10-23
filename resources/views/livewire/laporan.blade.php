<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-pib-600 mb-1">Unduhan & Arsip</div>
            <h1 class="text-2xl font-bold text-gray-900">Laporan & Backup</h1>
            <p class="text-sm text-gray-500 mt-0.5">Unduh berkas rekap nilai, cetak rapor siswa, dan kelola salinan data.</p>
        </div>
        <div>
            <a href="{{ route('rekap', $this->queryStringParams) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-pib-700 bg-pib-50 hover:bg-pib-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Tinjau di Rekap
            </a>
        </div>
    </div>

    <!-- Filter Cakupan Laporan -->
    <x-card>
        <x-slot:header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Cakupan Laporan</h2>
                    <p class="text-xs text-gray-500">Pilih parameter untuk membatasi data yang akan diunduh.</p>
                </div>
            </div>
        </x-slot:header>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Tahun Ajaran -->
            <div>
                <label for="filter-year" class="block text-xs font-medium text-gray-700 mb-1">Tahun Ajaran / Periode</label>
                <select id="filter-year" wire:model.live="academicYearId" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Periode</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}">{{ $y->name }} · {{ $y->semester->value }}{{ $y->is_active ? ' (Aktif)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label for="filter-class" class="block text-xs font-medium text-gray-700 mb-1">Kelas</label>
                <select id="filter-class" wire:model.live="classId" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Nama Siswa -->
            <div>
                <label for="filter-student" class="block text-xs font-medium text-gray-700 mb-1">Cari Nama Siswa</label>
                <input type="text" id="filter-student" wire:model.live.debounce.300ms="student" placeholder="Ketik nama siswa..." class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
            </div>

            <!-- Bab -->
            <div>
                <label for="filter-chapter" class="block text-xs font-medium text-gray-700 mb-1">Bab</label>
                <select id="filter-chapter" wire:model.live="chapter" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Bab</option>
                    @foreach($chapters as $ch)
                        <option value="{{ $ch }}">{{ $ch }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subbab -->
            <div>
                <label for="filter-subchapter" class="block text-xs font-medium text-gray-700 mb-1">Subbab</label>
                <select id="filter-subchapter" wire:model.live="subchapter" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Subbab</option>
                    @foreach($subchapters as $sub)
                        <option value="{{ $sub }}">{{ $sub }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Materi -->
            <div>
                <label for="filter-assessment" class="block text-xs font-medium text-gray-700 mb-1">Materi</label>
                <select id="filter-assessment" wire:model.live="assessmentId" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Materi</option>
                    @foreach($assessments as $a)
                        <option value="{{ $a->id }}">{{ $a->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    <!-- Kartu Unduhan -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Unduh Excel -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col justify-between hover:border-pib-300 transition-all">
            <div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Laporan Excel</h3>
                <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                    Buku kerja lengkap berisi 5 lembar: Ringkasan, Laporan per Materi, Rekap Siswa, Rekap Kelas, dan Detail Nilai mentah.
                </p>
            </div>
            <div>
                <a href="{{ route('laporan.excel', $this->queryStringParams) }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-pib-600 hover:bg-pib-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Excel (.xlsx)
                </a>
            </div>
        </div>

        <!-- Unduh PDF Kelas -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col justify-between hover:border-pib-300 transition-all">
            <div>
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-700 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Laporan PDF Kelas</h3>
                <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                    Dokumen siap cetak format A4 portrait dengan identitas sekolah, rekap per materi dan kelas, serta rata-rata berbobot.
                </p>
            </div>
            <div>
                <a href="{{ route('laporan.pdf', $this->queryStringParams) }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh PDF Kelas
                </a>
            </div>
        </div>

        <!-- Unduh Rapor Per Siswa -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col justify-between hover:border-pib-300 transition-all">
            <div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-1">Rapor Per Siswa</h3>
                <p class="text-xs text-gray-500 mb-3 leading-relaxed">
                    Lembar rapor individual mencakup seluruh materi, nilai, rata-rata berbobot, dan blok paraf guru penilai.
                </p>
                <div class="mb-4">
                    <label for="select-student" class="block text-xs font-medium text-gray-700 mb-1">Pilih Siswa</label>
                    <select id="select-student" wire:model.live="selectedStudentId" class="w-full text-xs rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                        <option value="">-- Pilih salah satu siswa --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->schoolClass?->name ?? 'Tanpa kelas' }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                @if($selectedStudentId)
                    <a href="{{ route('laporan.pdf.student', ['student' => $selectedStudentId]) }}" target="_blank" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Unduh Rapor Siswa
                    </a>
                @else
                    <button type="button" disabled class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-100 text-gray-400 text-sm font-semibold rounded-lg cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Pilih siswa terlebih dahulu
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Cadangan Data Sistem (Bagian 2 - Cadangan Data) -->
    <x-card>
        <x-slot:header>
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-pib-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <h2 class="text-base font-semibold text-gray-900">Cadangan Data (Backup)</h2>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" wire:click="backupNow" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-pib-600 hover:bg-pib-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        <span wire:loading.remove wire:target="backupNow">
                            <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            Cadangkan Sekarang
                        </span>
                        <span wire:loading wire:target="backupNow">Menyiapkan…</span>
                    </button>
                    <a href="{{ route('backup.download') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-500 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Unduh Backup (.json)
                    </a>
                </div>
            </div>
        </x-slot:header>

        @if(session('backup_status'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg text-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('backup_status') }}</span>
            </div>
        @endif

        <div class="space-y-4">
            <p class="text-sm text-gray-600">
                Pencadangan mencakup seluruh data master sekolah, kelas, siswa, materi kurikulum, nilai, dan log audit.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <div class="text-xs text-gray-500 font-medium">Backup Otomatis Terakhir</div>
                    <div class="text-sm font-semibold text-gray-900 mt-0.5">
                        {{ $backupStatus['last_backup_time'] ? \Illuminate\Support\Carbon::parse($backupStatus['last_backup_time'])->translatedFormat('d M Y, H:i') : 'Belum ada' }}
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <div class="text-xs text-gray-500 font-medium">Jadwal & Retensi</div>
                    <div class="text-sm font-semibold text-gray-900 mt-0.5">
                        Tiap {{ $backupStatus['interval_hours'] }} jam · simpan {{ $backupStatus['keep'] }} file
                    </div>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <div class="text-xs text-gray-500 font-medium">Berkas Tersimpan</div>
                    <div class="text-sm font-semibold text-gray-900 mt-0.5">
                        {{ $backupStatus['count'] }} berkas di server
                    </div>
                </div>
            </div>
        </div>
    </x-card>
</div>

