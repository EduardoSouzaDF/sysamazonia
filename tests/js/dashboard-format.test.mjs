import test from 'node:test';
import assert from 'node:assert/strict';
import { formatNumber, formatPercent, shortenLabel, tooltipMarkup } from '../../resources/js/dashboard-format.js';

test('uses Brazilian quantities and percentages, including zero', () => {
    assert.equal(formatNumber(12345), '12.345');
    assert.equal(formatPercent(33.3333), '33,33%');
    assert.equal(formatPercent(0), '0,00%');
});

test('chart tooltip escapes labels and retains counts and percentages', () => {
    const result = tooltipMarkup({ label: '<img src=x onerror="alert(1)">', total: 1234, percentage: 25 });
    assert.ok(!result.includes('<img'));
    assert.ok(result.includes('&lt;img'));
    assert.ok(result.includes('1.234 inscrições · 25,00%'));
});

test('long chart labels are shortened while tooltips retain their full content', () => {
    const label = 'Uma categoria com um título extenso para testar legibilidade';
    assert.equal(shortenLabel(label, 20).length, 20);
    assert.ok(tooltipMarkup({ label, total: 1, percentage: 100 }).includes(label));
});
