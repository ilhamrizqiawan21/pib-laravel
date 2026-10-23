<div>
    <x-slot:title>Kurikulum & Materi</x-slot:title>

    <x-page-header
        eyebrow="KURIKULUM"
        title="Kurikulum & Materi"
        description="Kelola template kurikulum, bab, subbab, materi penilaian, dan bobot penilaian."
    >
        <x-slot:actions>
            <div class="flex flex-wrap items-center gap-2">
                @if ($selected_template_id)
                    <x-button variant="outline" size="sm" wire:click="openCopyModal">
                        <x-icon name="copy" size="16" />
                        <span>Salin Struktur</span>
                    </x-button>
                @endif
                <x-button variant="outline" size="sm" x-data @click="$dispatch('open-modal', 'modal-tambah-template')">
                    <x-icon name="plus" size="16" />
                    <span>Template Baru</span>
                </x-button>
                @if ($selected_template_id)
                    <x-button variant="primary" size="sm" x-data @click="$dispatch('open-modal', 'modal-form-bab')">
                        <x-icon name="plus" size="16" />
                        <span>Tambah Bab</span>
                    </x-button>
                @endif
            </div>
        </x-slot:actions>
    </x-page-header>

    @if ($statusMessage)
        <div class="mb-6">
            <x-alert variant="success">
                {{ $statusMessage }}
            </x-alert>
        </div>
    @endif

    @if ($errorMessage)
        <div class="mb-6">
            <x-alert variant="danger">
                {{ $errorMessage }}
            </x-alert>
        </div>
    @endif

    <!-- Selector Bar -->
    <div class="mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 bg-surface p-4 rounded-xl border border-line">
        <div class="flex flex-wrap items-center gap-4">
            <div class="w-full md:w-64">
                <label for="materi-tahun" class="block text-xs font-medium text-ink-muted mb-1">Tahun Ajaran</label>
                <select
                    id="materi-tahun"
                    wire:model.live="selected_academic_year_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                >
                    @foreach ($academicYears as $year)
                        <option value="{{ $year->id }}">
                            {{ $year->label() }} {{ $year->is_active ? '(Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-72">
                <label for="materi-template" class="block text-xs font-medium text-ink-muted mb-1">Template Kurikulum</label>
                <select
                    id="materi-template"
                    wire:model.live="selected_template_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    @if ($templates->isEmpty()) disabled @endif
                >
                    @if ($templates->isEmpty())
                        <option value="">Belum ada template</option>
                    @else
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div wire:loading.delay class="w-full mb-6">
        <x-loading-state message="Memuat kurikulum..." />
    </div>

    <!-- Tree View / Empty State -->
    <div wire:loading.remove>
        @if (! $template)
            <x-card>
                <x-empty-state
                    title="Tidak ada template kurikulum"
                    description="Belum ada template kurikulum untuk tahun ajaran ini. Silakan buat template baru atau salin dari tahun ajaran lain."
                >
                    <x-slot:action>
                        <x-button variant="primary" size="sm" x-data @click="$dispatch('open-modal', 'modal-tambah-template')">
                            <x-icon name="plus" size="16" />
                            <span>Buat Template</span>
                        </x-button>
                    </x-slot:action>
                </x-empty-state>
            </x-card>
        @elseif ($template->chapters->isEmpty())
            <x-card>
                <div class="p-6">
                    <div class="mb-4 flex items-center justify-between border-b border-line pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-ink">{{ $template->name }}</h2>
                            <p class="text-xs text-ink-muted">Tahun Ajaran: {{ $template->academicYear?->label() }}</p>
                        </div>
                    </div>
                    <x-empty-state
                        title="Belum ada bab materi"
                        description="Template ini belum memiliki bab materi. Tambahkan bab pertama untuk mulai menyusun kurikulum."
                    >
                        <x-slot:action>
                            <x-button variant="primary" size="sm" x-data @click="$dispatch('open-modal', 'modal-form-bab')">
                                <x-icon name="plus" size="16" />
                                <span>Tambah Bab Pertama</span>
                            </x-button>
                        </x-slot:action>
                    </x-empty-state>
                </div>
            </x-card>
        @else
            <!-- Hierarchy Display -->
            <div class="space-y-4">
                <div class="flex items-center justify-between px-1">
                    <div>
                        <h2 class="text-lg font-bold text-ink">{{ $template->name }}</h2>
                        <p class="text-xs text-ink-muted">Tahun Ajaran: {{ $template->academicYear?->label() }} &bull; {{ $template->chapters->count() }} Bab</p>
                    </div>
                </div>

                @foreach ($template->chapters as $cIndex => $chapter)
                    <div class="bg-surface rounded-xl border border-line shadow-sm overflow-hidden" wire:key="chapter-{{ $chapter->id }}">
                        <!-- Chapter Header -->
                        <div class="bg-surface-alt px-5 py-3 border-b border-line flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-brand-light text-xs font-bold text-brand-strong">
                                    {{ $chapter->display_order }}
                                </span>
                                <h3 class="text-base font-semibold text-ink">{{ $chapter->title }}</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="moveItem('chapter', {{ $chapter->id }}, 'up')"
                                    title="Pindah ke atas"
                                    class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface"
                                    @if ($cIndex === 0) disabled class="opacity-30 cursor-not-allowed p-1 text-ink-muted" @endif
                                >
                                    <x-icon name="chevron-up" size="16" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="moveItem('chapter', {{ $chapter->id }}, 'down')"
                                    title="Pindah ke bawah"
                                    class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface"
                                    @if ($cIndex === $template->chapters->count() - 1) disabled class="opacity-30 cursor-not-allowed p-1 text-ink-muted" @endif
                                >
                                    <x-icon name="chevron-down" size="16" />
                                </button>
                                <x-button
                                    variant="outline"
                                    size="sm"
                                    x-data
                                    @click="$wire.set('parent_chapter_id', {{ $chapter->id }}); $dispatch('open-modal', 'modal-form-subbab')"
                                >
                                    <x-icon name="plus" size="14" />
                                    <span>Subbab</span>
                                </x-button>
                                <button
                                    type="button"
                                    wire:click="deleteChapter({{ $chapter->id }})"
                                    wire:confirm="Hapus bab ini beserta seluruh subbab dan materinya?"
                                    class="p-1.5 rounded text-danger hover:bg-danger-light"
                                    title="Hapus Bab"
                                >
                                    <x-icon name="trash-2" size="16" />
                                </button>
                            </div>
                        </div>

                        <!-- Subchapters -->
                        <div class="p-4 space-y-4">
                            @if ($chapter->subchapters->isEmpty())
                                <p class="text-xs text-ink-muted italic py-2 px-3">Belum ada subbab di dalam bab ini.</p>
                            @else
                                @foreach ($chapter->subchapters as $sIndex => $subchapter)
                                    <div class="rounded-lg border border-line bg-surface p-4" wire:key="subchapter-{{ $subchapter->id }}">
                                        <!-- Subchapter Header -->
                                        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-line mb-3">
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-mono font-medium text-ink-muted">{{ $chapter->display_order }}.{{ $subchapter->display_order }}</span>
                                                <h4 class="text-sm font-semibold text-ink">{{ $subchapter->title }}</h4>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <button
                                                    type="button"
                                                    wire:click="moveItem('subchapter', {{ $subchapter->id }}, 'up')"
                                                    title="Pindah ke atas"
                                                    class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface-alt"
                                                    @if ($sIndex === 0) disabled class="opacity-30 cursor-not-allowed p-1 text-ink-muted" @endif
                                                >
                                                    <x-icon name="chevron-up" size="14" />
                                                </button>
                                                <button
                                                    type="button"
                                                    wire:click="moveItem('subchapter', {{ $subchapter->id }}, 'down')"
                                                    title="Pindah ke bawah"
                                                    class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface-alt"
                                                    @if ($sIndex === $chapter->subchapters->count() - 1) disabled class="opacity-30 cursor-not-allowed p-1 text-ink-muted" @endif
                                                >
                                                    <x-icon name="chevron-down" size="14" />
                                                </button>
                                                <x-button
                                                    variant="ghost"
                                                    size="sm"
                                                    x-data
                                                    @click="$wire.set('parent_subchapter_id', {{ $subchapter->id }}); $dispatch('open-modal', 'modal-form-materi')"
                                                >
                                                    <x-icon name="plus" size="14" />
                                                    <span>Materi</span>
                                                </x-button>
                                                <button
                                                    type="button"
                                                    wire:click="deleteSubchapter({{ $subchapter->id }})"
                                                    wire:confirm="Hapus subbab ini beserta seluruh materinya?"
                                                    class="p-1 rounded text-danger hover:bg-danger-light"
                                                    title="Hapus Subbab"
                                                >
                                                    <x-icon name="trash-2" size="14" />
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Assessments list -->
                                        @if ($subchapter->assessments->isEmpty())
                                            <p class="text-xs text-ink-muted italic py-1">Belum ada materi penilaian.</p>
                                        @else
                                            <ul class="divide-y divide-line/60">
                                                @foreach ($subchapter->assessments as $aIndex => $assessment)
                                                    <li class="py-2 flex flex-wrap items-center justify-between gap-3" wire:key="assessment-{{ $assessment->id }}">
                                                        <div class="flex items-center gap-3">
                                                            <span class="text-xs font-mono text-ink-muted">
                                                                {{ $chapter->display_order }}.{{ $subchapter->display_order }}.{{ $assessment->display_order }}
                                                            </span>
                                                            <span class="text-sm text-ink {{ ! $assessment->is_active ? 'line-through text-ink-muted' : '' }}">
                                                                {{ $assessment->title }}
                                                            </span>
                                                            @if ((float) $assessment->weight !== 1.0)
                                                                <x-badge variant="brand">
                                                                    {{ (float) $assessment->weight }}x
                                                                </x-badge>
                                                            @endif
                                                            @if (! $assessment->is_active)
                                                                <x-badge variant="neutral">Non-aktif</x-badge>
                                                            @endif
                                                        </div>

                                                        <div class="flex items-center gap-1.5">
                                                            <button
                                                                type="button"
                                                                wire:click="moveItem('assessment', {{ $assessment->id }}, 'up')"
                                                                title="Pindah ke atas"
                                                                class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface-alt"
                                                                @if ($aIndex === 0) disabled class="opacity-30 cursor-not-allowed p-1 text-ink-muted" @endif
                                                            >
                                                                <x-icon name="chevron-up" size="14" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="moveItem('assessment', {{ $assessment->id }}, 'down')"
                                                                title="Pindah ke bawah"
                                                                class="p-1 rounded text-ink-muted hover:text-ink hover:bg-surface-alt"
                                                                @if ($aIndex === $subchapter->assessments->count() - 1) disabled class="opacity-30 cursor-not-allowed p-1 text-ink-muted" @endif
                                                            >
                                                                <x-icon name="chevron-down" size="14" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="toggleAssessment({{ $assessment->id }})"
                                                                title="{{ $assessment->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                                                class="px-2 py-0.5 text-xs rounded border {{ $assessment->is_active ? 'border-line text-ink-muted hover:text-ink' : 'border-success text-success bg-success-light' }}"
                                                            >
                                                                {{ $assessment->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="deleteAssessment({{ $assessment->id }})"
                                                                wire:confirm="Hapus materi penilaian ini?"
                                                                class="p-1 rounded text-danger hover:bg-danger-light"
                                                                title="Hapus Materi"
                                                            >
                                                                <x-icon name="trash-2" size="14" />
                                                            </button>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Modal Tambah Template -->
    <x-modal name="modal-tambah-template" title="Tambah Template Kurikulum">
        <form wire:submit.prevent="createTemplate" class="space-y-4">
            <x-field label="Nama Template" name="new_template_name" required>
                <x-input
                    wire:model="new_template_name"
                    placeholder="Misal: Kurikulum Praktik Ibadah Utama"
                    maxlength="100"
                    required
                />
            </x-field>

            <div class="flex justify-end gap-2 pt-4 border-t border-line">
                <x-button type="button" variant="outline" x-data @click="$dispatch('close-modal', 'modal-tambah-template')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    Simpan Template
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Form Bab -->
    <x-modal name="modal-form-bab" title="Tambah Bab Baru">
        <form wire:submit.prevent="createChapter" class="space-y-4">
            <x-field label="Judul Bab" name="new_chapter_title" required>
                <x-input
                    wire:model="new_chapter_title"
                    placeholder="Misal: Bab 1 - Praktik Shalat Fardhu"
                    maxlength="150"
                    required
                />
            </x-field>

            <div class="flex justify-end gap-2 pt-4 border-t border-line">
                <x-button type="button" variant="outline" x-data @click="$dispatch('close-modal', 'modal-form-bab')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    Tambah Bab
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Form Subbab -->
    <x-modal name="modal-form-subbab" title="Tambah Subbab Baru">
        <form wire:submit.prevent="createSubchapter" class="space-y-4">
            <x-field label="Judul Subbab" name="new_subchapter_title" required>
                <x-input
                    wire:model="new_subchapter_title"
                    placeholder="Misal: Bacaan Ruku & Sujud"
                    maxlength="150"
                    required
                />
            </x-field>

            <div class="flex justify-end gap-2 pt-4 border-t border-line">
                <x-button type="button" variant="outline" x-data @click="$dispatch('close-modal', 'modal-form-subbab')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    Tambah Subbab
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Form Materi -->
    <x-modal name="modal-form-materi" title="Tambah Materi Penilaian">
        <form wire:submit.prevent="createAssessment" class="space-y-4">
            <x-field label="Judul Materi" name="new_assessment_title" required>
                <x-input
                    wire:model="new_assessment_title"
                    placeholder="Misal: Doa Iftitah"
                    maxlength="150"
                    required
                />
            </x-field>

            <x-field label="Bobot Penilaian" name="new_assessment_weight" required help="Nilai standar adalah 1.0. Masukkan nilai 0.1 - 100.">
                <x-input
                    type="number"
                    step="0.1"
                    min="0.1"
                    max="100"
                    wire:model="new_assessment_weight"
                    required
                />
            </x-field>

            <x-field label="Deskripsi / Catatan" name="new_assessment_description">
                <textarea
                    wire:model="new_assessment_description"
                    rows="3"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    placeholder="Petunjuk penilaian bagi guru (opsional)"
                ></textarea>
            </x-field>

            <div class="flex justify-end gap-2 pt-4 border-t border-line">
                <x-button type="button" variant="outline" x-data @click="$dispatch('close-modal', 'modal-form-materi')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    Tambah Materi
                </x-button>
            </div>
        </form>
    </x-modal>

    <!-- Modal Salin Struktur -->
    <x-modal name="modal-salin-struktur" title="Salin Struktur Kurikulum">
        <form wire:submit.prevent="executeCopy" class="space-y-4">
            <p class="text-xs text-ink-muted">
                Salin seluruh struktur bab, subbab, dan materi kurikulum ke tahun ajaran lain. Seluruh data nilai tidak akan ikut tersalin.
            </p>

            @if ($copyPreview)
                <div class="rounded-lg bg-surface-alt p-3 border border-line text-xs space-y-1">
                    <div class="font-semibold text-ink">Ringkasan yang akan disalin:</div>
                    <ul class="list-disc list-inside text-ink-muted">
                        <li>{{ $copyPreview['chapters_count'] }} Bab</li>
                        <li>{{ $copyPreview['subchapters_count'] }} Subbab</li>
                        <li>{{ $copyPreview['assessments_count'] }} Materi Penilaian</li>
                    </ul>
                </div>
            @endif

            <x-field label="Tahun Ajaran Tujuan" name="copy_target_academic_year_id" required>
                <select
                    wire:model="copy_target_academic_year_id"
                    class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:border-brand"
                    required
                >
                    <option value="">Pilih Tahun Ajaran Tujuan</option>
                    @foreach ($academicYears as $year)
                        @if ($year->id !== (int) $selected_academic_year_id)
                            <option value="{{ $year->id }}">{{ $year->label() }}</option>
                        @endif
                    @endforeach
                </select>
            </x-field>

            <x-field label="Nama Template Baru" name="copy_new_name" help="Biarkan sama atau sesuaikan untuk tahun ajaran baru.">
                <x-input
                    wire:model="copy_new_name"
                    maxlength="100"
                    placeholder="Nama template baru"
                />
            </x-field>

            <div class="flex justify-end gap-2 pt-4 border-t border-line">
                <x-button type="button" variant="outline" x-data @click="$dispatch('close-modal', 'modal-salin-struktur')">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    Salin Kurikulum Sekarang
                </x-button>
            </div>
        </form>
    </x-modal>
</div>
