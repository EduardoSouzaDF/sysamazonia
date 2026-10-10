import test from 'node:test';
import assert from 'node:assert/strict';
import { guides, contextGuides, pendingGuides, automaticGuide, taskSteps } from '../../resources/js/commission-guide-content.js';

test('guides follow the current screen and each permission for users with multiple profiles', () => {
    assert.deepEqual(contextGuides('admin.registration.index', ['evaluator', 'indicator']), ['evaluator-list', 'indicator-list']);
    assert.deepEqual(contextGuides('admin.registration.show', ['indicator'], { detail: true }), ['indicator-detail']);
    assert.deepEqual(contextGuides('admin.registration.show', ['indicator']), []);
    assert.deepEqual(contextGuides('panel.julgar.index', ['judge'], { drawer: true, panel: true }), ['judge-detail']);
    assert.deepEqual(contextGuides('panel.julgar.index', ['judge'], { done: true, votes: true }), ['judge-done', 'judge-votes']);
    assert.deepEqual(contextGuides('panel.julgar.index', ['evaluator'], { panel: true }), []);
    assert.deepEqual(contextGuides('panel.julgar.index', ['judge'], { empty: true }), ['judge-empty']);
    assert.deepEqual(contextGuides('home', ['judge']), []);
});

test('completed guides are omitted while paused and first-visit guides remain available', () => {
    assert.deepEqual(pendingGuides(['welcome', 'judge-panel', 'judge-detail'], {
        welcome: { status: 'completed' }, 'judge-panel': { status: 'paused', step: 2 },
    }), ['judge-panel', 'judge-detail']);
});

test('indicator guide explains the existing submission form and judge distinguishes confirmations', () => {
    assert.equal(guides['indicator-detail'].steps.some(step => /IN-06|IN-07/.test(step.id)), true);
    assert.match(guides['judge-detail'].steps.find(step => step.id === 'JU-08').text, /ainda precisa confirmar as escolhas no painel/);
    assert.match(guides['judge-panel'].steps.find(step => step.id === 'JU-11').text, /grava seu julgamento/);
});

test('automatic guidance stops independently for each profile and honors the chosen activity', () => {
    const ids = ['evaluator-list', 'indicator-list'];
    assert.equal(automaticGuide(ids, ['indicator', 'evaluator']), 'indicator-list');
    assert.equal(automaticGuide(ids, ['evaluator']), 'evaluator-list');
    assert.equal(automaticGuide(ids, []), undefined);
    assert.equal(automaticGuide(['judge-panel'], ['judge']), 'judge-panel');
    assert.equal(taskSteps['evaluator-detail'], 'AV-09');
    assert.equal(taskSteps['indicator-detail'], 'IN-07');
    assert.equal(taskSteps['judge-panel'], 'JU-11');
});
