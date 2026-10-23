<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wider text-pib-600 mb-1">Administrasi Sistem</div>
            <h1 class="text-2xl font-bold text-gray-900">Riwayat Audit</h1>
            <p class="text-sm text-gray-500 mt-0.5">Catatan append-only yang merekam seluruh mutasi data, penilaian, dan operasional sistem.</p>
        </div>
    </div>

    <!-- Filter Riwayat Audit -->
    <x-card>
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-4 items-end">
            <!-- Pencarian -->
            <div class="sm:col-span-2 lg:col-span-2">
                <label for="audit-search" class="block text-xs font-medium text-gray-700 mb-1">Cari Ringkasan / ID / Pengguna</label>
                <input type="text" id="audit-search" wire:model.live.debounce.300ms="search" placeholder="Ketik kata kunci pencarian..." class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
            </div>

            <!-- Filter Entitas -->
            <div>
                <label for="audit-entity" class="block text-xs font-medium text-gray-700 mb-1">Entitas</label>
                <select id="audit-entity" wire:model.live="entity" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Entitas</option>
                    @foreach($availableEntities as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ $key }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Aksi -->
            <div>
                <label for="audit-action" class="block text-xs font-medium text-gray-700 mb-1">Aksi</label>
                <select id="audit-action" wire:model.live="action" class="w-full text-sm rounded-lg border-gray-300 focus:border-pib-500 focus:ring-pib-500">
                    <option value="">Semua Aksi</option>
                    @foreach($availableActions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($search !== '' || $entity !== '' || $action !== '')
            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
                <span class="text-gray-500">Filter aktif diterapkan</span>
                <button type="button" wire:click="resetFilters" class="text-pib-600 hover:text-pib-800 font-medium underline">Reset Filter</button>
            </div>
        @endif
    </x-card>

    <!-- Tabel Riwayat Audit -->
    <x-card>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3" style="width: 140px;">Waktu</th>
                        <th class="px-4 py-3" style="width: 130px;">Pengguna</th>
                        <th class="px-4 py-3" style="width: 120px;">Aksi</th>
                        <th class="px-4 py-3" style="width: 100px;">Entitas</th>
                        <th class="px-4 py-3">Ringkasan Aktivitas</th>
                        <th class="px-4 py-3" style="width: 80px;">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-normal">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                <div>{{ $log->created_at?->translatedFormat('d M Y') }}</div>
                                <div class="font-mono text-[11px] text-gray-400">{{ $log->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                @if($log->user)
                                    <div class="font-medium text-gray-900">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-gray-500">{{ $log->user->role->value }}</div>
                                @else
                                    <span class="italic text-gray-400">Sistem</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $actionClasses = match($log->action) {
                                        'CREATE', 'UPSERT' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'UPDATE', 'STATUS_CHANGE', 'ROLE_CHANGE', 'RESET_PASSWORD' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'DELETE', 'CLEAR', 'FAILED', 'LOGIN_FAILED' => 'bg-red-50 text-red-700 border-red-200',
                                        'SNAPSHOT', 'RESTORE' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        default => 'bg-gray-50 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-semibold border {{ $actionClasses }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 font-mono">
                                <div>{{ $log->entity }}</div>
                                @if($log->entity_id)
                                    <div class="text-[10px] text-gray-400">#{{ $log->entity_id }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-800">
                                {{ $log->summary }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 text-right">
                                @if($log->meta)
                                    <details class="cursor-pointer text-left">
                                        <summary class="text-pib-600 hover:text-pib-800 font-medium">Meta</summary>
                                        <div class="mt-1 p-2 bg-gray-50 border border-gray-200 rounded text-[10px] font-mono text-gray-700 max-w-xs overflow-x-auto whitespace-pre">
                                            {{ json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 text-sm">
                                Tidak ada catatan riwayat audit yang sesuai.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </x-card>
</div>

