import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/julgar.js', import.meta.url), 'utf8');
function loadJudge(judgeId, storage) {
    const fields = [];
    const cards = ['registration:1', 'registration:2'].map(key => ({
        dataset: { key }, classList: { toggle() {} },
        setAttribute(name, value) { this[name] = value; },
        querySelector() { return null; },
        addEventListener(name, callback) { this[name] = callback; },
    }));
    const fieldsBox = { set innerHTML(value) { fields.length = 0; }, appendChild(input) { fields.push(input); } };
    const form = { addEventListener(name, callback) { this[name] = callback; } };
    const root = {
        dataset: { judgeId, categoryId: '10', effectiveQuota: '1', selectedIds: '[]' },
        querySelectorAll() { return cards; },
        querySelector(selector) { return ({ '[data-inscription-fields]': fieldsBox, '[data-julgar-form]': form })[selector] ?? null; },
    };
    vm.runInNewContext(script, {
        document: { readyState: 'complete', querySelector() { return root; }, createElement() { return {}; } },
        window: { localStorage: { getItem(key) { return storage.get(key) ?? null; }, setItem(key, value) { storage.set(key, value); }, removeItem(key) { storage.delete(key); } } },
    });
    return { cards, fields, form };
}
const select = card => card.click({ target: { closest() { return true; } } });

test('two judges in the same browser submit their own selections', () => {
    const storage = new Map([['julgar-preselects-10', '["registration:1"]']]);
    const first = loadJudge('100', storage);
    assert.equal(first.fields.length, 0);
    assert.equal(storage.has('julgar-preselects-10'), false);
    select(first.cards[0]);
    assert.equal(first.fields[1].value, '1');
    const second = loadJudge('200', storage);
    assert.equal(second.fields.length, 0);
    select(second.cards[1]);
    assert.equal(second.fields[1].value, '2');
    assert.equal(storage.get('julgar-preselects-100-10'), '["registration:1"]');
    assert.equal(storage.get('julgar-preselects-200-10'), '["registration:2"]');
    const restored = loadJudge('100', storage);
    assert.equal(restored.fields[1].value, '1');
});
