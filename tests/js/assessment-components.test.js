import assert from 'node:assert/strict';
import { test } from 'node:test';

import { setoranComponent } from '../../resources/js/setoran.js';
import { materiAssessmentComponent } from '../../resources/js/materi-assessment.js';

for (const [name, create] of [
    ['Setoran', setoranComponent],
    ['Per materi', materiAssessmentComponent],
]) {
    test(`${name} mengabaikan respons kelas lama setelah berpindah kelas`, async () => {
        let releaseOld;
        globalThis.window = {
            DraftStore: {
                draftKey: (studentId, assessmentId) => `${studentId}:${assessmentId}`,
                apiRequest: async (url) => {
                    if (url.includes('/1/')) {
                        await new Promise((resolve) => { releaseOld = resolve; });
                    }
                    const classId = url.includes('/1/') ? 1 : 2;
                    return { ok: true, json: async () => ({ classId, className: `Kelas ${classId}`, materials: [], students: [], scores: [] }) };
                },
            },
        };
        globalThis.localStorage = { setItem: () => {} };

        const component = create();
        component.classId = '1';
        const oldRequest = component.loadClassProgress();
        component.classId = '2';
        await component.loadClassProgress();
        releaseOld();
        await oldRequest;

        assert.equal(component.data.classId, 2);
    });
}

test('Setoran meminta konfirmasi sebelum mengosongkan nilai tersimpan', async () => {
    let submitted = false;
    const events = [];
    globalThis.window = {
        DraftStore: {
            draftKey: () => '1:2',
            parseMistakes: () => ({ valid: true, mistakes: null }),
            submitScore: async () => { submitted = true; },
            persistDraft: () => {},
        },
    };

    const component = setoranComponent();
    component.studentId = 1;
    component.scores = { '1:2': { score: 85 } };
    component.drafts = { '1:2': { key: '1:2', studentId: 1, assessmentId: 2, raw: '' } };
    component.$dispatch = (name) => events.push(name);
    component.focusNextMaterial = () => {};
    component.refreshDrafts = () => {};

    await component.commit({ id: 2, title: 'Niat' }, 0);

    assert.equal(submitted, false);
    assert.deepEqual(events, ['open-modal']);
});
