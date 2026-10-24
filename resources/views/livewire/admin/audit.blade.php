<div>
    <x-slot:title>Riwayat Audit</x-slot:title>

    <!-- Header Halaman -->
    <x-page-header
        eyebrow="ADMINISTRASI SISTEM"
        title="Riwayat Audit"
        description="Catatan append-only yang merekam seluruh mutasi data, penilaian, dan operasional sistem."
    />

    <!-- Filter Riwayat Audit -->
    <x-card class="mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <!-- Pencarian -->
            <div class="sm:col-span-2 lg:col-span-2">
                <label for="audit-search" class="block text-xs font-semibold text-ink mb-1.5">Cari Ringkasan / ID / Pengguna</label>
                <input
                    type="text"
                    id="audit-search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ketik kata kunci pencarian..."
                    class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
            </div>

            <!-- Filter Entitas -->
            <div>
                <label for="audit-entity" class="block text-xs font-semibold text-ink mb-1.5">Entitas</label>
                <select
                    id="audit-entity"
                    wire:model.live="entity"
                    class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Entitas</option>
                    @foreach($availableEntities as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Aksi -->
            <div>
                <label for="audit-action" class="block text-xs font-semibold text-ink mb-1.5">Aksi</label>
                <select
                    id="audit-action"
                    wire:model.live="action"
                    class="w-full text-sm rounded-xl border border-line bg-surface px-3 py-2 text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    <option value="">Semua Aksi</option>
                    @foreach($availableActions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($search !== '' || $entity !== '' || $action !== '')
            <div class="mt-4 pt-3 border-t border-line flex items-center justify-between text-xs">
                <span class="text-muted">Filter aktif diterapkan</span>
                <x-button type="button" variant="ghost" size="xs" wire:click="resetFilters" class="text-brand-strong hover:text-brand">
                    <x-icon name="rotate-ccw" size="13" />
                    <span>Reset Filter</span>
                </x-button>
            </div>
        @endif
    </x-card>

    <!-- Tabel Riwayat Audit -->
    <x-card>
        <div class="overflow-x-auto border border-line rounded-xl">
            <table class="w-full text-left text-sm text-ink">
                <thead>
                    <tr class="border-b border-line bg-canvas/80 text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="px-4 py-3" style="width: 140px;">Waktu</th>
                        <th class="px-4 py-3" style="width: 130px;">Pengguna</th>
                        <th class="px-4 py-3" style="width: 120px;">Aksi</th>
                        <th class="px-4 py-3" style="width: 100px;">Entitas</th>
                        <th class="px-4 py-3">Ringkasan Aktivitas</th>
                        <th class="px-4 py-3 text-right" style="width: 80px;">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($logs as $log)
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="audit-timestamp px-4 py-3 text-xs text-muted whitespace-nowrap">
                                <div class="font-medium text-ink">{{ $log->created_at?->translatedFormat('d M Y') }}</div>
                                <div class="font-mono text-[11px] text-muted">{{ $log->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($log->user)
                                    <div class="font-semibold text-ink">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-muted">{{ $log->user->role->value }}</div>
                                @else
                                    <span class="italic text-muted">Sistem</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $actionClasses = match($log->action) {
                                        'CREATE', 'UPSERT' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'UPDATE', 'STATUS_CHANGE', 'ROLE_CHANGE', 'RESET_PASSWORD' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'DELETE', 'CLEAR', 'FAILED', 'LOGIN_FAILED' => 'bg-red-50 text-red-700 border-red-200',
                                        'SNAPSHOT', 'RESTORE' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        default => 'bg-canvas text-ink border-line',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold border {{ $actionClasses }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted font-mono">
                                <div class="text-ink font-semibold">{{ $log->entity }}</div>
                                @if($log->entity_id)
                                    <div class="text-[10px] text-muted">#{{ $log->entity_id }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-ink">
                                {{ $log->summary }}
                            </td>
                            <td class="px-4 py-3 text-xs text-muted text-right">
                                @if($log->meta)
                                    <details class="cursor-pointer text-left">
                                        <summary class="text-brand-strong hover:text-brand font-semibold text-xs">Meta</summary>
                                        <div class="mt-1 p-2 bg-canvas border border-line rounded-lg text-[10px] font-mono text-ink max-w-xs overflow-x-auto whitespace-pre">
                                            {{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                        </div>
                                    </details>
                                @else
                                    <span class="text-muted/40">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-muted text-sm">
                                Tidak ada catatan riwayat audit yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-line mt-4">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
</div>
