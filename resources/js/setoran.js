/**
 * PIB Penilaian - Komponen Alpine Layar Setoran (Penilaian per Siswa)
 * Berorientasi keyboard (keyboard-first), responsif, dan tahan jaringan offline.
 */

export function setoranComponent({ classes = [], initialClassId = null, initialStudentId = null } = {}) {
    return {
        classes: classes || [],
        classId: initialClassId ? String(initialClassId) : '',
        studentId: initialStudentId ? Number(initialStudentId) : null,
        query: '',
        searchIndex: 0,
        mobileStudentPickerOpen: false,

        data: null, // { classId, className, materials: [], students: [], scores: [] }
        scores: {}, // key: "studentId:assessmentId" => score object
        drafts: {}, // key => draft object
        savingKeys: [],
        openGroups: new Set(),

        loading: false,
        loadRequestId: 0,
        errorMessage: '',
        statusMessage: '',
        details: { raw: '', initials: '', note: '', assessedAt: '' },
        detailsError: '',
        savingDetails: false,
        pendingClear: null,

        init() {
            // Pulihkan kelas terakhir dari localStorage jika belum ditentukan
            if (!this.classId) {
                try {
                    const last = JSON.parse(localStorage.getItem('pib-last-setoran') || '{}');
                    if (last.classId && this.classes.some((c) => String(c.id) === String(last.classId))) {
                        this.classId = String(last.classId);
                        if (last.studentId) {
                            this.studentId = Number(last.studentId);
                        }
                    } else if (this.classes.length === 1) {
                        this.classId = String(this.classes[0].id);
                    }
                } catch {
                    if (this.classes.length === 1) {
                        this.classId = String(this.classes[0].id);
                    }
                }
            }

            this.refreshDrafts();

            // Dengarkan perubahan penyimpanan draft lokal
            window.addEventListener('pib-draft-change', () => this.refreshDrafts());
            window.addEventListener('storage', () => this.refreshDrafts());

            // Dengarkan konfirmasi penyimpanan sukses dari background sync
            window.addEventListener('pib-score-saved', (event) => {
                const { draft, score, mistakes, version, assessed_at, initials, note } = event.detail;
                if (draft) {
                    this.scores[draft.key] = {
                        student_id: draft.studentId,
                        assessment_id: draft.assessmentId,
                        score,
                        mistakes,
                        version,
                        assessed_at: assessed_at || new Date().toISOString(),
                        initials,
                        note,
                    };
                }
                this.refreshDrafts();
            });

            // Shortcut keyboard '/' untuk fokus ke pencarian siswa
            document.addEventListener('keydown', (e) => {
                if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                    const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
                    if (activeTag !== 'input' && activeTag !== 'textarea' && activeTag !== 'select') {
                        e.preventDefault();
                        this.mobileStudentPickerOpen = true;
                        this.$nextTick(() => {
                            const searchInput = document.getElementById('search-siswa-input');
                            searchInput?.focus();
                            searchInput?.select();
                        });
                    }
                }
            });

            if (this.classId) {
                this.loadClassProgress();
            }
        },

        async loadClassProgress() {
            const requestId = ++this.loadRequestId;
            if (!this.classId) {
                this.data = null;
                this.scores = {};
                this.loading = false;
                return;
            }

            const classId = this.classId;

            this.loading = true;
            this.errorMessage = '';

            try {
                const response = await window.DraftStore.apiRequest(`/penilaian/kelas/${classId}/progress`);
                if (!response.ok) {
                    throw new Error('Gagal memuat data kelas dari server.');
                }

                const result = await response.json();
                if (requestId !== this.loadRequestId) return;
                this.data = result;

                // Bangun map scores
                const scoreMap = {};
                for (const s of result.scores) {
                    const key = window.DraftStore.draftKey(s.student_id, s.assessment_id);
                    scoreMap[key] = s;
                }
                this.scores = scoreMap;

                // Pastikan siswa terpilih sah dalam kelas ini
                if (this.studentId && !result.students.some((s) => s.id === this.studentId)) {
                    this.studentId = null;
                }

                if (!this.studentId && result.students.length > 0) {
                    this.selectStudent(result.students[0].id);
                } else if (this.studentId) {
                    this.selectStudent(this.studentId);
                }

                this.saveLastState();
            } catch (err) {
                if (requestId === this.loadRequestId) {
                    this.errorMessage = err.message || 'Terjadi kesalahan saat memuat data.';
                }
            } finally {
                if (requestId === this.loadRequestId) this.loading = false;
            }
        },

        changeClass(newClassId) {
            this.classId = newClassId;
            this.studentId = null;
            this.query = '';
            this.mobileStudentPickerOpen = false;
            this.saveLastState();
            this.loadClassProgress();
        },

        selectStudent(id) {
            this.studentId = id;
            this.mobileStudentPickerOpen = false;
            this.saveLastState();
            this.autoExpandAndFocus();
        },

        moveSearch(direction) {
            const results = [...this.filteredStudents.today, ...this.filteredStudents.others];
            if (!results.length) return;
            this.searchIndex = (this.searchIndex + direction + results.length) % results.length;
        },

        selectSearchResult() {
            const results = [...this.filteredStudents.today, ...this.filteredStudents.others];
            const student = results[this.searchIndex];
            if (student) this.selectStudent(student.id);
        },

        isSearchHighlight(id) {
            const results = [...this.filteredStudents.today, ...this.filteredStudents.others];
            return results[this.searchIndex]?.id === id;
        },

        saveLastState() {
            try {
                localStorage.setItem(
                    'pib-last-setoran',
                    JSON.stringify({ classId: this.classId, studentId: this.studentId })
                );
            } catch {
                // Abaikan jika storage penuh
            }
        },

        refreshDrafts() {
            if (!window.DraftStore) return;
            const stored = window.DraftStore.readDrafts();
            const map = {};
            for (const d of stored) {
                map[d.key] = d;
            }
            this.drafts = map;
        },

        // Mengelompokkan materi per Subbab
        get groups() {
            if (!this.data || !this.data.materials) return [];
            const result = [];
            this.data.materials.forEach((material, index) => {
                const key = String(material.subchapter_id);
                const last = result[result.length - 1];
                if (last && last.key === key) {
                    last.items.push({ material, index });
                } else {
                    result.push({
                        key,
                        chapter: material.chapter,
                        subchapter: material.subchapter,
                        items: [{ material, index }],
                    });
                }
            });
            return result;
        },

        // Perhitungan posisi & materi terlewat untuk siswa saat ini
        get position() {
            if (!this.studentId || !this.data || !this.data.materials) {
                return { assessed: 0, total: 0, lastIndex: -1, nextIndex: -1, gaps: [] };
            }

            const materialIds = this.data.materials.map((m) => m.id);
            let lastIndex = -1;
            let assessed = 0;

            materialIds.forEach((id, index) => {
                const key = window.DraftStore.draftKey(this.studentId, id);
                if (this.scores[key] && this.scores[key].score !== null) {
                    assessed++;
                    lastIndex = index;
                }
            });

            const gaps = [];
            for (let i = 0; i < Math.max(0, lastIndex); i++) {
                const id = materialIds[i];
                const key = window.DraftStore.draftKey(this.studentId, id);
                if (!this.scores[key] || this.scores[key].score === null) {
                    gaps.push(i);
                }
            }

            let ahead = -1;
            for (let i = lastIndex + 1; i < materialIds.length; i++) {
                const id = materialIds[i];
                const key = window.DraftStore.draftKey(this.studentId, id);
                if (!this.scores[key] || this.scores[key].score === null) {
                    ahead = i;
                    break;
                }
            }

            return {
                assessed,
                total: materialIds.length,
                lastIndex,
                nextIndex: ahead >= 0 ? ahead : (gaps[0] ?? -1),
                gaps,
            };
        },

        // Daftar siswa terfilter untuk panel kiri
        get filteredStudents() {
            if (!this.data || !this.data.students) return { today: [], others: [] };

            const needle = this.query.trim().toLowerCase();
            const visible = this.data.students.filter((s) => {
                const text = `${s.name} ${s.nis || ''}`.toLowerCase();
                return text.includes(needle);
            });

            const today = [];
            const others = [];

            visible.forEach((s) => {
                // Cek apakah ada nilai yang dinilai pada hari ini
                const hasToday = Object.values(this.scores).some((sc) => {
                    if (sc.student_id !== s.id || sc.score === null || !sc.assessed_at) return false;
                    const d = new Date(sc.assessed_at);
                    const now = new Date();
                    return (
                        d.getFullYear() === now.getFullYear() &&
                        d.getMonth() === now.getMonth() &&
                        d.getDate() === now.getDate()
                    );
                });

                if (hasToday) {
                    today.push(s);
                } else {
                    others.push(s);
                }
            });

            return { today, others };
        },

        get selectedStudent() {
            if (!this.data || !this.studentId) return null;
            return this.data.students.find((s) => s.id === this.studentId) || null;
        },

        // Status siswa memiliki draft pending lokal
        hasPendingDraft(studentId) {
            return Object.values(this.drafts).some((d) => d.studentId === studentId);
        },

        // Label progres siswa di panel kiri (misal "12/24")
        studentProgressLabel(studentId) {
            if (!this.data || !this.data.materials || this.data.materials.length === 0) return '—';
            let assessed = 0;
            for (const m of this.data.materials) {
                const key = window.DraftStore.draftKey(studentId, m.id);
                if (this.scores[key] && this.scores[key].score !== null) {
                    assessed++;
                }
            }
            return `${assessed}/${this.data.materials.length}`;
        },

        toggleGroup(key) {
            if (this.openGroups.has(key)) {
                this.openGroups.delete(key);
            } else {
                this.openGroups.add(key);
            }
        },

        isGroupOpen(key) {
            return this.openGroups.has(key);
        },

        autoExpandAndFocus() {
            if (!this.data || !this.data.materials) return;
            const pos = this.position;
            const targetIndex = pos.nextIndex >= 0 ? pos.nextIndex : 0;
            const targetMaterial = this.data.materials[targetIndex];

            if (targetMaterial) {
                this.openGroups.add(String(targetMaterial.subchapter_id));
                // Juga buka grup yang memiliki materi terlewat (gaps)
                pos.gaps.forEach((idx) => {
                    const m = this.data.materials[idx];
                    if (m) this.openGroups.add(String(m.subchapter_id));
                });

                this.$nextTick(() => {
                    const input = document.querySelector(`input[data-material-id="${targetMaterial.id}"]`);
                    if (input) {
                        input.focus();
                        input.select();
                        input.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                });
            }
        },

        focusNextMaterial(currentIndex) {
            const nextIndex = currentIndex + 1;
            if (this.data && this.data.materials && nextIndex < this.data.materials.length) {
                const nextMaterial = this.data.materials[nextIndex];
                this.openGroups.add(String(nextMaterial.subchapter_id));
                this.$nextTick(() => {
                    const input = document.querySelector(`input[data-material-id="${nextMaterial.id}"]`);
                    if (input) {
                        input.focus();
                        input.select();
                        input.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                });
            } else {
                // Selesai seluruh materi, fokus kembali ke pencarian siswa
                const searchInput = document.getElementById('search-siswa-input');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        },

        // Ambil nilai mentah kesalahan untuk baris materi (dari draft bila ada, atau dari server)
        getRawMistakes(materialId) {
            if (!this.studentId) return '';
            const key = window.DraftStore.draftKey(this.studentId, materialId);
            if (this.drafts[key]) {
                return this.drafts[key].raw;
            }
            if (this.scores[key] && this.scores[key].mistakes !== null) {
                return String(this.scores[key].mistakes);
            }
            return '';
        },

        openDetails(material) {
            if (!this.studentId) return;
            const key = window.DraftStore.draftKey(this.studentId, material.id);
            const draft = this.drafts[key];
            const score = this.scores[key];
            this.details = {
                key,
                studentId: this.studentId,
                assessmentId: material.id,
                title: material.title,
                raw: this.getRawMistakes(material.id),
                initials: draft?.initials ?? score?.initials ?? '',
                note: draft?.note ?? score?.note ?? '',
                assessedAt: draft?.assessedAt ?? (score?.assessed_at
                    ? new Date(score.assessed_at).toLocaleDateString('sv-SE')
                    : new Date().toLocaleDateString('sv-SE')),
                baseVersion: score?.version ?? null,
            };
            this.detailsError = '';
            this.$dispatch('open-modal', 'score-details-setoran');
        },

        async saveDetails() {
            if (!this.details || this.savingDetails) return;
            const parsed = window.DraftStore.parseMistakes(this.details.raw);
            if (!parsed.valid || parsed.mistakes === null) {
                this.detailsError = parsed.error || 'Isi jumlah kesalahan sebelum menyimpan detail.';
                return;
            }

            const draft = {
                ...this.details,
                id: window.DraftStore.createDraftId(),
                deviceId: window.DraftStore.getDeviceId(),
                status: 'pending',
            };
            window.DraftStore.persistDraft(draft);
            this.refreshDrafts();
            this.savingDetails = true;
            try {
                const result = await window.DraftStore.submitScore(draft);
                if (result.success || result.status === 0) {
                    this.$dispatch('close-modal', 'score-details-setoran');
                } else {
                    this.detailsError = result.error || 'Nilai berubah di server. Muat ulang sebelum menyimpan.';
                }
            } finally {
                this.savingDetails = false;
                this.refreshDrafts();
            }
        },

        // Update nilai draft saat guru mengetik
        onInputMistakes(material, raw) {
            if (!this.studentId) return;
            const key = window.DraftStore.draftKey(this.studentId, material.id);
            const currentScore = this.scores[key];

            const draft = {
                key,
                studentId: this.studentId,
                assessmentId: material.id,
                raw: String(raw),
                id: window.DraftStore.createDraftId(),
                deviceId: window.DraftStore.getDeviceId(),
                baseVersion: currentScore ? currentScore.version : null,
                status: 'dirty',
            };

            window.DraftStore.persistDraft(draft);
            this.refreshDrafts();
        },

        // Stepper tambah/kurang nilai
        step(material, delta) {
            const currentRaw = this.getRawMistakes(material.id);
            const next = window.DraftStore.stepMistakes(currentRaw, delta);
            if (next !== null) {
                this.onInputMistakes(material, next);
            }
        },

        // Tombol 90: Nilai sempurna (0 kesalahan), langsung simpan dan pindah materi berikutnya
        async setPerfect90(material, index) {
            if (!this.studentId) return;
            const key = window.DraftStore.draftKey(this.studentId, material.id);
            const currentScore = this.scores[key];

            const draft = {
                key,
                studentId: this.studentId,
                assessmentId: material.id,
                raw: '0',
                id: window.DraftStore.createDraftId(),
                deviceId: window.DraftStore.getDeviceId(),
                baseVersion: currentScore ? currentScore.version : null,
                status: 'pending',
            };

            window.DraftStore.persistDraft(draft);
            this.refreshDrafts();
            this.focusNextMaterial(index);

            this.savingKeys.push(key);
            try {
                await window.DraftStore.submitScore(draft);
            } finally {
                this.savingKeys = this.savingKeys.filter((k) => k !== key);
                this.refreshDrafts();
            }
        },

        // Kirim nilai saat tekan Enter
        async commit(material, index) {
            if (!this.studentId) return;
            const key = window.DraftStore.draftKey(this.studentId, material.id);
            const draft = this.drafts[key];

            if (!draft) {
                // Tidak ada perubahan yang diketik, langsung lanjut ke materi berikutnya
                this.focusNextMaterial(index);
                return;
            }

            const parsed = window.DraftStore.parseMistakes(draft.raw);
            if (!parsed.valid) {
                this.statusMessage = `${material.title}: ${parsed.error}`;
                return;
            }

            if (parsed.mistakes === null && this.scores[key]?.score !== null) {
                this.pendingClear = { draft, index };
                this.$dispatch('open-modal', 'confirm-clear-setoran');
                return;
            }

            await this.submitDraft(draft, index);
        },

        async confirmClear() {
            if (!this.pendingClear) return;
            const { draft, index } = this.pendingClear;
            this.pendingClear = null;
            this.$dispatch('close-modal', 'confirm-clear-setoran');
            await this.submitDraft(draft, index);
        },

        cancelClear() {
            if (!this.pendingClear) return;
            window.DraftStore.deleteDraft(this.pendingClear.draft.key, this.pendingClear.draft.id);
            this.pendingClear = null;
            this.refreshDrafts();
        },

        async submitDraft(draft, index) {
            const key = draft.key;
            this.focusNextMaterial(index);

            const queued = { ...draft, status: 'pending' };
            window.DraftStore.persistDraft(queued);
            this.refreshDrafts();

            this.savingKeys.push(key);
            try {
                await window.DraftStore.submitScore(queued);
            } finally {
                this.savingKeys = this.savingKeys.filter((k) => k !== key);
                this.refreshDrafts();
            }
        },

        // Batalkan perubahan lokal / muat ulang jika konflik
        discard(material) {
            if (!this.studentId) return;
            const key = window.DraftStore.draftKey(this.studentId, material.id);
            const isConflict = this.drafts[key] && this.drafts[key].status === 'conflict';

            window.DraftStore.deleteDraft(key);
            this.refreshDrafts();

            if (isConflict) {
                this.loadClassProgress();
            }
        },

        // Periksa status baris materi
        getRowMeta(material, index) {
            if (!this.studentId) return {};
            const key = window.DraftStore.draftKey(this.studentId, material.id);
            const draft = this.drafts[key];
            const server = this.scores[key];
            const isSaving = this.savingKeys.includes(key);

            const raw = this.getRawMistakes(material.id);
            const parsed = window.DraftStore.parseMistakes(raw);

            const pos = this.position;
            const isNext = index === pos.nextIndex;
            const isGap = pos.gaps.includes(index);

            let statusTone = 'neutral';
            let statusLabel = '';

            if (isSaving) {
                statusTone = 'brand';
                statusLabel = 'Menyimpan...';
            } else if (draft) {
                if (draft.status === 'conflict') {
                    statusTone = 'danger';
                    statusLabel = 'Konflik server';
                } else if (draft.status === 'failed') {
                    statusTone = 'danger';
                    statusLabel = 'Gagal';
                } else {
                    statusTone = 'warning';
                    statusLabel = 'Menunggu koneksi';
                }
            } else if (server && server.score !== null) {
                statusTone = 'success';
                statusLabel = 'Tersimpan';
            } else if (isNext) {
                statusTone = 'brand';
                statusLabel = 'Berikutnya';
            } else if (isGap) {
                statusTone = 'warning';
                statusLabel = 'Terlewat';
            }

            return {
                raw,
                parsed,
                scoreDisplay: parsed.valid ? (parsed.score !== null ? parsed.score : '—') : '—',
                draft,
                isSaving,
                isNext,
                isGap,
                statusTone,
                statusLabel,
            };
        },
    };
}

