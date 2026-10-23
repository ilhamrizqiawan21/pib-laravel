<div>
    <x-slot:title>Impor Siswa</x-slot:title>

    <x-page-header
        eyebrow="DATA INDUK"
        title="Impor Siswa Massal"
        description="Unggah berkas Excel (.xlsx) atau CSV untuk menambahkan data siswa secara cepat."
    >
        <x-slot:actions>
            <x-button variant="secondary" size="sm" href="/siswa/template">
                <x-icon name="download" size="16" />
                <span>Unduh Template CSV</span>
            </x-button>
            <x-button variant="ghost" size="sm" href="/siswa">
                <x-icon name="chevron-left" size="16" />
                <span>Kembali ke Data Siswa</span>
            </x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($statusMessage)
        <div class="mb-6 max-w-3xl">
            <x-alert variant="success">
                {{ $statusMessage }}
            </x-alert>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-6 max-w-3xl">
            <x-alert variant="danger">
                {{ $errorMessage }}
            </x-alert>
        </div>
    @endif

    <div class="space-y-6 max-w-3xl">
        <!-- Unggah Berkas Card -->
        <x-card title="Unggah Berkas Siswa">
            <form wire:submit="import" class="space-y-5">
                <div class="space-y-2">
                    <label for="file" class="block text-xs font-medium text-ink">
                        Pilih Berkas Excel (.xlsx) atau CSV
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="file"
                        id="file"
                        wire:model="file"
                        accept=".csv, .xlsx, .xls"
                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-soft file:text-brand-strong hover:file:bg-brand-soft/80 cursor-pointer border border-line rounded-lg p-2 bg-surface"
                    />

                    @error('file')
                        <p class="text-xs text-danger font-medium flex items-center gap-1 mt-1">
                            <x-icon name="alert-circle" size="14" />
                            <span>{{ $message }}</span>
                        </p>
                    @enderror

                    <p class="text-xs text-muted">
                        Format yang didukung: <strong>.csv, .xlsx</strong>. Maksimal <strong>1.000 baris data</strong> dan ukuran berkas <strong>2 MB</strong>.
                    </p>
                </div>

                <div class="pt-2 flex items-center gap-3">
                    <x-button type="submit" variant="primary" wire:loading.attr="disabled">
                        <x-icon name="upload" size="16" />
                        <span>Mulai Impor Siswa</span>
                    </x-button>

                    <div wire:loading wire:target="import" class="text-xs text-muted flex items-center gap-2">
                        <x-icon name="loader-2" class="animate-spin text-brand-strong" size="16" />
                        <span>Memproses berkas...</span>
                    </div>
                </div>
            </form>
        </x-card>

        <!-- Hasil Impor & Laporan Baris Gagal -->
        @if (! empty($failedRows))
            <x-card title="Laporan Baris Gagal" description="Baris berikut tidak dapat diimpor karena tidak memenuhi aturan data.">
                <div class="overflow-x-auto border border-red-200 rounded-xl bg-danger-soft/20">
                    <table class="w-full text-left text-xs text-ink">
                        <thead class="bg-danger-soft text-danger font-semibold border-b border-red-200">
                            <tr>
                                <th class="px-3 py-2.5 w-16">Baris</th>
                                <th class="px-3 py-2.5">Nama</th>
                                <th class="px-3 py-2.5">Kelas</th>
                                <th class="px-3 py-2.5">Penyebab Gagal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-red-100">
                            @foreach ($failedRows as $fail)
                                <tr>
                                    <td class="px-3 py-2 font-mono font-medium text-danger">
                                        #{{ $fail['row'] }}
                                    </td>
                                    <td class="px-3 py-2 font-medium">
                                        {{ $fail['data']['name'] ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-muted">
                                        {{ $fail['data']['class'] ?? '-' }}
                                    </td>
                                    <td class="px-3 py-2 text-danger font-medium">
                                        {{ $fail['error'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @endif
    </div>
</div>

