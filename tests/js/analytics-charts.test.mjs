import test from 'node:test';
import assert from 'node:assert/strict';
import {chartData,chartRegistry} from '../../resources/js/analytics-charts.js';
const data = (rows,dimensions=['state']) => ({rows,context:{dimensions},metric:'registrations_count'});
test('empty chart has no invented values', () => assert.deepEqual(chartData(data([])).labels, []));
test('single zero stays zero and absent score stays null', () => {
    assert.deepEqual(chartData(data([{state:'AM',value:0}])).series[0].data,[0]);
    assert.deepEqual(chartData(data([{state:'AM',value:null}])).series[0].data,[null]);
});
test('crossing aligns groups without summing unrelated rows', () => {
    const result=chartData(data([{state:'AM',category:'A',value:2},{state:'PA',category:'B',value:3}],['state','category']));
    assert.deepEqual(result.series.map(s=>s.data),[[2,null],[null,3]]);
});
test('long labels and 100 categories remain available to the table', () => {
    const rows=Array.from({length:100},(_,i)=>({state:'Categoria longa '.repeat(10)+i,value:i}));
    assert.equal(chartData(data(rows)).labels.length,100);
    assert.deepEqual(chartData(data(rows)).labels, rows.map(r=>r.state));
});
test('labels cannot carry markup and map has a bar fallback', () => {
    assert.equal(chartData(data([{state:'<img src=x>',value:1}])).labels[0], 'img src=x');
    assert.equal(chartRegistry.map_brazil.type,'bar');
    assert.equal(chartRegistry.table,null);
    assert.equal(chartRegistry.script,undefined);
});
