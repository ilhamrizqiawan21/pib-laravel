/**
 * PIB Penilaian - Client Offline Draft Store & Sync Engine
 * Tanpa dependensi pihak ketiga, toleran terhadap jaringan putus / offline di LAN.
 */

export const DRAFT_PREFIX = 'pib-score-edit:';
export const DRAFT_EVENT = 'pib-draft-change';
export const SCORE_SAVED_EVENT = 'pib-score-saved';
export const DEVICE_ID_KEY = 'pib-device-id';

/**
 * Buat ID unik 32 karakter hexadecimal menggunakan crypto.getRandomValues
 * Kompatibel dengan origin HTTP di jaringan lokal (LAN).
 */
export function createDraftId() {
    if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
        const bytes = crypto.getRandomValues(new Uint8Array(16));
        return Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
    }
    return 'd-' + Date.now().toString(16) + '-' + Math.random().toString(16).slice(2, 10);
}

/**
 * Dapatkan atau buat identifier perangkat lokal unik.
 */
export function getDeviceId() {
    try {
        let deviceId = localStorage.getItem(DEVICE_ID_KEY);
        if (!deviceId) {
            deviceId = 'dev-' + createDraftId().slice(0, 16);
            localStorage.setItem(DEVICE_ID_KEY, deviceId);
        }
        return deviceId;
    } catch {
        return 'browser-client';
    }
}

/**
 * Format key draft penyimpanan.
 */
export function draftKey(studentId, assessmentId) {
    return `${studentId}:${assessmentId}`;
}

/**
 * Validasi dan hitung nilai dari input string kesalahan.
 * '' => valid null (belum dinilai).
 * 0..90 => valid integer (score = 90 - mistakes).
 */
export function parseMistakes(raw) {
    const trimmed = String(raw ?? '').trim();
    if (trimmed === '') {
        return { valid: true, mistakes: null, score: null };
    }

    if (!/^\d+$/.test(trimmed)) {
        return { valid: false, error: 'Isi bilangan bulat 0–90.' };
    }

    const value = Number(trimmed);
    if (!Number.isInteger(value) || value < 0 || value > 90) {
        return { valid: false, error: 'Isi bilangan bulat 0–90.' };
    }

    return { valid: true, mistakes: value, score: 90 - value };
}

/**
 * Langkah naik/turun kesalahan (+1, -1, dsb) dalam batas 0..90.
 */
export function stepMistakes(raw, delta) {
    const parsed = parseMistakes(raw);
    if (!parsed.valid) {
        return null;
    }
    const current = parsed.mistakes ?? 0;
    return String(Math.max(0, Math.min(90, current + delta)));
}

/**
 * Baca seluruh draft antrean yang ada di localStorage.
 */
export function readDrafts() {
    const drafts = [];
    try {
        for (let i = 0; i < localStorage.length; i++) {
            const key = localStorage.key(i);
            if (!key || !key.startsWith(DRAFT_PREFIX)) {
                continue;
            }
            try {
                const item = JSON.parse(localStorage.getItem(key) ?? 'null');
                if (
                    item &&
                    Number.isInteger(item.studentId) &&
                    Number.isInteger(item.assessmentId) &&
                    typeof item.raw === 'string' &&
                    typeof item.id === 'string' &&
                    ['dirty', 'pending', 'conflict', 'failed'].includes(item.status)
                ) {
                    drafts.push(item);
                }
            } catch {
                // Biarkan data rusak tetap ada tanpa crash
            }
        }
    } catch {
        return [];
    }
    return drafts;
}

/**
 * Ambil satu draft spesifik jika ada.
 */
export function getDraft(studentId, assessmentId) {
    try {
        const key = DRAFT_PREFIX + draftKey(studentId, assessmentId);
        const item = JSON.parse(localStorage.getItem(key) ?? 'null');
        return item || null;
    } catch {
        return null;
    }
}

/**
 * Simpan draft ke localStorage dan beri notifikasi event ke aplikasi.
 */
export function persistDraft(draft) {
    try {
        localStorage.setItem(DRAFT_PREFIX + draft.key, JSON.stringify(draft));
        window.dispatchEvent(new CustomEvent(DRAFT_EVENT, { detail: draft }));
    } catch (e) {
        console.error('Gagal menyimpan draft nilai ke localStorage:', e);
    }
}

/**
 * Hapus draft dari antrean localStorage setelah berhasil disinkronkan.
 */
export function deleteDraft(key, id) {
    try {
        const fullKey = DRAFT_PREFIX + key;
        const record = localStorage.getItem(fullKey);
        if (record && id) {
            try {
                const parsed = JSON.parse(record);
                if (parsed.id !== id) {
                    return; // Draft telah ditimpa dengan operasi baru yang lebih baru
                }
            } catch {
                // Abaikan jika rusak
            }
        }
        localStorage.removeItem(fullKey);
        window.dispatchEvent(new CustomEvent(DRAFT_EVENT, { detail: { key, deleted: true } }));
    } catch (e) {
        console.error('Gagal menghapus draft dari localStorage:', e);
    }
}

/**
 * Dapatkan token CSRF dari cookie atau meta tag.
 */
export function getCsrfToken() {
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag && metaTag.getAttribute('content')) {
        return metaTag.getAttribute('content');
    }

    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (match && match[1]) {
        return decodeURIComponent(match[1]);
    }

    return '';
}

/**
 * Helper request JSON dengan CSRF token dan retry 1x untuk method GET.
 */
export async function apiRequest(url, options = {}, isRetry = false) {
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': getCsrfToken(),
        ...(options.headers || {}),
    };

    try {
        const response = await fetch(url, {
            ...options,
            headers,
        });

        return response;
    } catch (error) {
        // Retry 1x untuk permintaan GET bila terjadi kegagalan jaringan
        if (!isRetry && (!options.method || options.method.toUpperCase() === 'GET')) {
            await new Promise((resolve) => setTimeout(resolve, 300));
            return apiRequest(url, options, true);
        }
        throw error;
    }
}

// Map pencegah request ganda untuk draft yang sama secara simultan
const inFlightRequests = new Map();

/**
 * Kirim draft nilai ke server via POST /penilaian/sync secara idempoten.
 */
export function submitScore(draft) {
    const parsed = parseMistakes(draft.raw);
    if (!parsed.valid) {
        persistDraft({ ...draft, status: 'failed', error: parsed.error });
        return Promise.resolve({ success: false, status: 422, error: parsed.error });
    }

    const current = inFlightRequests.get(draft.key);
    if (current) {
        if (current.id === draft.id) {
            return current.promise;
        }

        return current.promise.then((result) => {
            const latest = getDraft(draft.studentId, draft.assessmentId);
            if (!latest) return result;
            if (result.status === 409) {
                persistDraft({ ...latest, status: 'conflict', conflict: result.conflict });
                return result;
            }

            const next = result.success
                ? { ...latest, baseVersion: result.data.version }
                : latest;
            if (result.success) persistDraft(next);
            return submitScore(next);
        });
    }

    const request = (async () => {
        try {
            const payload = {
                id: draft.id,
                device_id: draft.deviceId || getDeviceId(),
                student_id: draft.studentId,
                assessment_id: draft.assessmentId,
                mistakes: parsed.mistakes,
                base_version: typeof draft.baseVersion === 'number' ? draft.baseVersion : null,
                ...(draft.initials !== undefined ? { initials: draft.initials } : {}),
                ...(draft.note !== undefined ? { note: draft.note } : {}),
                ...(draft.assessedAt !== undefined ? { assessed_at: draft.assessedAt } : {}),
            };

            const response = await apiRequest('/penilaian/sync', {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (response.ok && data.status === 'SYNCED') {
                deleteDraft(draft.key, draft.id);
                window.dispatchEvent(
                    new CustomEvent(SCORE_SAVED_EVENT, {
                        detail: {
                            draft,
                            score: data.score,
                            mistakes: data.mistakes,
                            version: data.version,
                            assessed_at: data.assessed_at,
                            initials: data.initials,
                            note: data.note,
                        },
                    })
                );
                return { success: true, status: 200, data };
            }

            if (response.status === 409) {
                const latest = readDrafts().find((x) => x.key === draft.key);
                if (latest?.id === draft.id) {
                    persistDraft({
                        ...draft,
                        status: 'conflict',
                        error: 'Data di server telah diperbarui oleh pengguna lain.',
                        conflict: data.current,
                    });
                }
                return { success: false, status: 409, conflict: data.current };
            }

            // Error 422 atau lainnya
            const latest = readDrafts().find((x) => x.key === draft.key);
            if (latest?.id === draft.id) {
                persistDraft({
                    ...draft,
                    status: 'failed',
                    error: data.message || data.error || 'Penyimpanan gagal.',
                });
            }
            return { success: false, status: response.status, error: data.message || data.error };
        } catch {
            // Kegagalan jaringan / offline
            const latest = readDrafts().find((x) => x.key === draft.key);
            if (latest?.id === draft.id) {
                persistDraft({
                    ...draft,
                    status: 'pending',
                    error: 'Menunggu koneksi untuk dikirim.',
                });
            }
            return { success: false, status: 0, error: 'Menunggu koneksi untuk dikirim.' };
        } finally {
            if (inFlightRequests.get(draft.key)?.id === draft.id) {
                inFlightRequests.delete(draft.key);
            }
        }
    })();

    inFlightRequests.set(draft.key, { id: draft.id, promise: request });
    return request;
}

/**
 * Sinkronkan semua draft pending/dirty yang tertunda saat koneksi kembali online.
 */
export async function syncPendingScores() {
    if (typeof navigator !== 'undefined' && !navigator.onLine) {
        return;
    }

    const pendingDrafts = readDrafts().filter(
        (x) => x.status === 'pending' || x.status === 'dirty'
    );

    for (const draft of pendingDrafts) {
        await submitScore(draft);
    }
}

// Listener otomatis untuk event online browser
if (typeof window !== 'undefined') {
    window.addEventListener('online', () => {
        syncPendingScores();
    });
}

