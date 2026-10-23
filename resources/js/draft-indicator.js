/**
 * PIB Penilaian - Komponen Alpine Draft Indicator Global
 * Menampilkan lencana jumlah nilai yang belum tersinkronkan ke server di topbar.
 */

export function draftIndicatorComponent() {
    return {
        count: 0,

        init() {
            this.update();

            window.addEventListener('pib-draft-change', () => this.update());
            window.addEventListener('storage', () => this.update());
            window.addEventListener('pib-score-saved', () => this.update());

            // Siklus sinkronisasi berkala di latar belakang jika dokumen aktif
            setInterval(() => {
                if (document.visibilityState === 'visible' && window.DraftStore) {
                    window.DraftStore.syncPendingScores().catch(() => {});
                }
            }, 30000);
        },

        update() {
            if (!window.DraftStore) return;
            try {
                const drafts = window.DraftStore.readDrafts();
                this.count = drafts.filter(
                    (x) => x.status === 'pending' || x.status === 'dirty'
                ).length;
            } catch {
                this.count = 0;
            }
        },
    };
}

