import assert from 'node:assert/strict';
import { test } from 'node:test';

import { persistDraft, submitScore } from '../../resources/js/draft-store.js';

test('menyimpan nilai tanpa metadata tidak mengirim paraf dan catatan kosong', async () => {
    const items = new Map();
    globalThis.localStorage = {
        getItem: (key) => items.get(key) ?? null,
        setItem: (key, value) => items.set(key, value),
        removeItem: (key) => items.delete(key),
        key: (index) => [...items.keys()][index] ?? null,
        get length() { return items.size; },
    };
    globalThis.window = { dispatchEvent: () => {} };
    globalThis.CustomEvent = class {
        constructor(type, options) { this.type = type; this.detail = options?.detail; }
    };
    globalThis.document = { querySelector: () => null, cookie: '' };

    let payload;
    globalThis.fetch = async (_url, options) => {
        payload = JSON.parse(options.body);
        return {
            ok: true,
            status: 200,
            json: async () => ({ status: 'SYNCED', score: 85, mistakes: 5, version: 2, assessed_at: '2026-10-24T00:00:00Z' }),
        };
    };

    await submitScore({ key: '1:2', studentId: 1, assessmentId: 2, raw: '5', id: 'test-op-1234', deviceId: 'test-device', baseVersion: 1 });

    assert.equal(payload.mistakes, 5);
    assert.equal(Object.hasOwn(payload, 'initials'), false);
    assert.equal(Object.hasOwn(payload, 'note'), false);
});

test('perubahan kedua pada nilai yang sedang dikirim tetap terkirim dengan versi terbaru', async () => {
    const items = new Map();
    globalThis.localStorage = {
        getItem: (key) => items.get(key) ?? null,
        setItem: (key, value) => items.set(key, value),
        removeItem: (key) => items.delete(key),
        key: (index) => [...items.keys()][index] ?? null,
        get length() { return items.size; },
    };
    globalThis.window = { dispatchEvent: () => {} };
    globalThis.document = { querySelector: () => null, cookie: '' };

    const payloads = [];
    let releaseFirst;
    globalThis.fetch = async (_url, options) => {
        const payload = JSON.parse(options.body);
        payloads.push(payload);
        if (payloads.length === 1) {
            await new Promise((resolve) => { releaseFirst = resolve; });
        }
        return {
            ok: true,
            status: 200,
            json: async () => ({ status: 'SYNCED', score: 90 - payload.mistakes, mistakes: payload.mistakes, version: payloads.length + 1 }),
        };
    };

    const first = { key: '3:4', studentId: 3, assessmentId: 4, raw: '5', id: 'first-op-1234', deviceId: 'test-device', baseVersion: 1, status: 'pending' };
    const second = { ...first, raw: '2', id: 'second-op-1234' };
    persistDraft(first);
    const firstRequest = submitScore(first);
    persistDraft(second);
    const secondRequest = submitScore(second);
    releaseFirst();
    await Promise.all([firstRequest, secondRequest]);

    assert.equal(payloads.length, 2);
    assert.equal(payloads[1].mistakes, 2);
    assert.equal(payloads[1].base_version, 2);
});

test('draft tidak valid tidak menghalangi kiriman valid berikutnya', async () => {
    const items = new Map();
    globalThis.localStorage = {
        getItem: (key) => items.get(key) ?? null,
        setItem: (key, value) => items.set(key, value),
        removeItem: (key) => items.delete(key),
        key: (index) => [...items.keys()][index] ?? null,
        get length() { return items.size; },
    };
    globalThis.window = { dispatchEvent: () => {} };
    globalThis.document = { querySelector: () => null, cookie: '' };
    let sent = 0;
    globalThis.fetch = async () => {
        sent++;
        return { ok: true, status: 200, json: async () => ({ status: 'SYNCED', score: 88, mistakes: 2, version: 1 }) };
    };

    const invalid = { key: '5:6', studentId: 5, assessmentId: 6, raw: 'abc', id: 'invalid-op-1234', deviceId: 'test-device', baseVersion: null, status: 'dirty' };
    persistDraft(invalid);
    await submitScore(invalid);
    const valid = { ...invalid, raw: '2', id: 'valid-op-1234' };
    persistDraft(valid);
    await submitScore(valid);

    assert.equal(sent, 1);
});
