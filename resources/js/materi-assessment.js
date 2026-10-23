/**
 * PIB Penilaian - Komponen Alpine Layar Penilaian per Materi
 * Memungkinkan guru menilai seluruh siswa dalam satu kelas untuk satu materi terpilih.
 */

export function materiAssessmentComponent({ classes = [], initialClassId = null, initialAssessmentId = null } = {}) {
    return {
        classes: classes || [],
        classId: initialClassId ? String(initialClassId) : '',
        assessmentId: initialAssessmentId ? Number(initialAssessmentId) : null,
        query: '',
        filter: 'all', // 'all' | 'unassessed' | 'drafts'

        data: null, // { classId, className, materials: [], students: [], scores: [] }
        scores: {}, // key: "studentId:assessmentId" => score object
        drafts: {}, // key => draft object
        savingKeys: [],

        loading: false,
        errorMessage: '',
        statusMessage: '',

        init() {
            // Pulihkan preferensi dari localStorage bila tidak disediakan
            if (!this.classId) {
                try {
                    const last = JSON.parse(localStorage.getItem('pib-last-assessment-material') || '{}');
                    if (last.classId && this.classes.some((c) => String(c.id) === String(last.classId))) {
                        this.classId = String(last.classId);
                        if (last.assessmentId) {
                            this.assessmentId = Number(last.assessmentId);
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

            window.addEventListener('pib-draft-change', () => this.refreshDrafts());
            window.addEventListener('storage', () => this.refreshDrafts());

            window.addEventListener('pib-score-saved', (event) => {
                const { draft, score, mistakes, version, assessed_at } = event.detail;
                if (draft) {
                    this.scores[draft.key] = {
                        student_id: draft.studentId,
                        assessment_id: draft.assessmentId,
                        score,
                        mistakes,
                        version,
                        assessed_at: assessed_at || new Date().toISOString(),
                    };
                }
                this.refreshDrafts();
            });

            if (this.classId) {
                this.loadClassProgress();
            }
        },

        async loadClassProgress() {
            if (!this.classId) {
                this.data = null;
                this.scores = {};
                return;
            }

            this.loading = true;
            this.errorMessage = '';

            try {
                const response = await window.DraftStore.apiRequest(`/penilaian/kelas/${this.classId}/progress`);
                if (!response.ok) {
                    throw new Error('Gagal memuat data kelas dari server.');
                }

                const result = await response.json();
                this.data = result;

                const scoreMap = {};
                for (const s of result.scores) {
                    const key = window.DraftStore.draftKey(s.student_id, s.assessment_id);
                    scoreMap[key] = s;
                }
                this.scores = scoreMap;

                // Pastikan assessmentId sah untuk kurikulum kelas ini
                if (this.assessmentId && !result.materials.some((m) => m.id === this.assessmentId)) {
                    this.assessmentId = null;
                }

                if (!this.assessmentId && result.materials.length > 0) {
                    this.assessmentId = result.materials[0].id;
                }

                this.saveLastState();
            } catch (err) {
                this.errorMessage = err.message || 'Terjadi kesalahan saat memuat data.';
            } finally {
                this.loading = false;
            }
        },

        changeClass(newClassId) {
            this.classId = newClassId;
            this.assessmentId = null;
            this.query = '';
            this.saveLastState();
            this.loadClassProgress();
        },

        changeAssessment(newAssessmentId) {
            this.assessmentId = newAssessmentId ? Number(newAssessmentId) : null;
            this.saveLastState();
            this.$nextTick(() => {
                // Fokus ke input pertama siswa
                const firstInput = document.querySelector('input[data-student-row="0"]');
                if (firstInput) {
                    firstInput.focus();
                    firstInput.select();
                }
            });
        },

        saveLastState() {
            try {
                localStorage.setItem(
                    'pib-last-assessment-material',
                    JSON.stringify({ classId: this.classId, assessmentId: this.assessmentId })
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

        get selectedMaterial() {
            if (!this.data || !this.assessmentId) return null;
            return this.data.materials.find((m) => m.id === this.assessmentId) || null;
        },

        // Statistik kemajuan materi terpilih
        get materialStats() {
            if (!this.data || !this.assessmentId || !this.data.students) {
                return { assessedCount: 0, totalCount: 0, percentage: 0 };
            }

            const totalCount = this.data.students.length;
            let assessedCount = 0;

            this.data.students.forEach((s) => {
                const key = window.DraftStore.draftKey(s.id, this.assessmentId);
                if (this.scores[key] && this.scores[key].score !== null) {
                    assessedCount++;
                }
            });

            const percentage = totalCount > 0 ? Math.round((assessedCount / totalCount) * 100) : 0;
            return { assessedCount, totalCount, percentage };
        },

        // Siswa yang difilter dan dicari
        get filteredStudents() {
            if (!this.data || !this.data.students || !this.assessmentId) return [];

            const needle = this.query.trim().toLowerCase();

            return this.data.students.filter((s) => {
                const text = `${s.name} ${s.nis || ''}`.toLowerCase();
                if (!text.includes(needle)) {
                    return false;
                }

                const key = window.DraftStore.draftKey(s.id, this.assessmentId);
                const isAssessed = this.scores[key] && this.scores[key].score !== null;
                const hasDraft = !!this.drafts[key];

                if (this.filter === 'unassessed') {
                    return !isAssessed;
                }
                if (this.filter === 'drafts') {
                    return hasDraft;
                }

                return true;
            });
        },

        getRawMistakes(studentId) {
            if (!this.assessmentId) return '';
            const key = window.DraftStore.draftKey(studentId, this.assessmentId);
            if (this.drafts[key]) {
                return this.drafts[key].raw;
            }
            if (this.scores[key] && this.scores[key].mistakes !== null) {
                return String(this.scores[key].mistakes);
            }
            return '';
        },

        onInputMistakes(student, raw) {
            if (!this.assessmentId) return;
            const key = window.DraftStore.draftKey(student.id, this.assessmentId);
            const currentScore = this.scores[key];

            const draft = {
                key,
                studentId: student.id,
                assessmentId: this.assessmentId,
                raw: String(raw),
                id: window.DraftStore.createDraftId(),
                deviceId: window.DraftStore.getDeviceId(),
                baseVersion: currentScore ? currentScore.version : null,
                status: 'dirty',
            };

            window.DraftStore.persistDraft(draft);
            this.refreshDrafts();
        },

        step(student, delta) {
            const currentRaw = this.getRawMistakes(student.id);
            const next = window.DraftStore.stepMistakes(currentRaw, delta);
            if (next !== null) {
                this.onInputMistakes(student, next);
            }
        },

        focusNextStudent(currentIndex) {
            const nextIndex = currentIndex + 1;
            const nextInput = document.querySelector(`input[data-student-row="${nextIndex}"]`);
            if (nextInput) {
                nextInput.focus();
                nextInput.select();
                nextInput.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        },

        async setPerfect90(student, index) {
            if (!this.assessmentId) return;
            const key = window.DraftStore.draftKey(student.id, this.assessmentId);
            const currentScore = this.scores[key];

            const draft = {
                key,
                studentId: student.id,
                assessmentId: this.assessmentId,
                raw: '0',
                id: window.DraftStore.createDraftId(),
                deviceId: window.DraftStore.getDeviceId(),
                baseVersion: currentScore ? currentScore.version : null,
                status: 'pending',
            };

            window.DraftStore.persistDraft(draft);
            this.refreshDrafts();
            this.focusNextStudent(index);

            this.savingKeys.push(key);
            try {
                await window.DraftStore.submitScore(draft);
            } finally {
                this.savingKeys = this.savingKeys.filter((k) => k !== key);
                this.refreshDrafts();
            }
        },

        async commit(student, index) {
            if (!this.assessmentId) return;
            const key = window.DraftStore.draftKey(student.id, this.assessmentId);
            const draft = this.drafts[key];

            if (!draft) {
                this.focusNextStudent(index);
                return;
            }

            const parsed = window.DraftStore.parseMistakes(draft.raw);
            if (!parsed.valid) {
                this.statusMessage = `${student.name}: ${parsed.error}`;
                return;
            }

            this.focusNextStudent(index);

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

        discard(student) {
            if (!this.assessmentId) return;
            const key = window.DraftStore.draftKey(student.id, this.assessmentId);
            const isConflict = this.drafts[key] && this.drafts[key].status === 'conflict';

            window.DraftStore.deleteDraft(key);
            this.refreshDrafts();

            if (isConflict) {
                this.loadClassProgress();
            }
        },

        getRowMeta(student) {
            if (!this.assessmentId) return {};
            const key = window.DraftStore.draftKey(student.id, this.assessmentId);
            const draft = this.drafts[key];
            const server = this.scores[key];
            const isSaving = this.savingKeys.includes(key);

            const raw = this.getRawMistakes(student.id);
            const parsed = window.DraftStore.parseMistakes(raw);

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
            }

            return {
                raw,
                parsed,
                scoreDisplay: parsed.valid ? (parsed.score !== null ? parsed.score : '—') : '—',
                draft,
                isSaving,
                statusTone,
                statusLabel,
            };
        },
    };
}

