import test from 'node:test';
import assert from 'node:assert/strict';
import { buildDashboardPdf, reportTables } from '../../resources/js/dashboard-pdf.js';

const row = (label, total = 1, percentage = 50) => ({ label, total, percentage });
const fixture = () => ({
    edition: { id: 7, title: 'EDICAO TESTE' }, fallback: false,
    current: {
        total: 2, regular: 1, honorary: 1, unmapped: 1, generatedAt: '10/10/2026 12:00:00',
        categories: [row('Categoria teste', 2, 100)], modalities: [row('Modalidade teste', 2, 100)],
        sex: [row('Mulher'), row('Não informado')], ages: [row('18 a 30'), row('Não informado')], education: [row('Superior'), row('Não informado')],
        regions: [row('Norte'), row('Não informado / UF inválida')], states: [row('AM'), row('UF inválida')],
        map: [{ ...row('Amazonas'), uf: 'AM', name: 'Amazonas', region: 'Norte' }, { ...row('Distrito Federal', 0, 0), uf: 'DF', name: 'Distrito Federal', region: 'Centro-Oeste' }],
    },
});

test('report includes every table, zero-count states and invalid UF without duplicating valid states', () => {
    const tables = reportTables(fixture().current);
    assert.equal(tables.length, 7);
    const states = tables.find(table => table.title === 'Distribuição por UF');
    assert.deepEqual(states.rows.map(row => row[0]), ['Amazonas (AM)', 'Distrito Federal (DF)', 'UF inválida']);
    assert.deepEqual(states.rows[1].slice(2), ['0', '0,00%']);
    assert.equal(tables[0].total, true);
});

test('generated PDF places all charts before tables and uses the dashboard snapshot', () => {
    const { doc, chartPages } = buildDashboardPdf(fixture());
    const pages = doc.internal.pages.slice(1).map(page => page.join('\n'));
    assert.equal(chartPages, 3);
    assert.ok(pages.slice(0, chartPages).every(page => !page.includes('(Tabelas')));
    assert.ok(pages.slice(chartPages).every(page => page.includes('(Tabelas')));
    assert.ok(pages.every(page => page.includes('EDICAO TESTE')));
    assert.ok(pages[0].includes('10/10/2026 12:00:00'));
    const output = doc.output();
    assert.ok(output.startsWith('%PDF-'));
    assert.ok(output.trimEnd().endsWith('%%EOF'));
});

test('many categories paginate without omitting chart or table labels', () => {
    const statistics = fixture();
    statistics.current.categories = Array.from({ length: 90 }, (_, index) => row(`Categoria longa ${index} com informações para leitura completa`));
    const { doc, chartPages } = buildDashboardPdf(statistics);
    assert.ok(chartPages > 3);
    const chartText = doc.internal.pages.slice(1, chartPages + 1).flat().join('\n');
    const tableText = doc.internal.pages.slice(chartPages + 1).flat().join('\n');
    assert.ok(chartText.includes('Categoria longa 89'));
    assert.ok(tableText.includes('Categoria longa 89'));
});

test('an empty edition can be exported and a missing edition is rejected', () => {
    const statistics = fixture();
    Object.assign(statistics.current, { total: 0, regular: 0, honorary: 0, unmapped: 0 });
    for (const key of ['categories', 'modalities', 'sex', 'ages', 'education', 'regions', 'states']) statistics.current[key] = [];
    statistics.current.map.forEach(state => Object.assign(state, { total: 0, percentage: 0 }));
    assert.ok(buildDashboardPdf(statistics).doc.output().startsWith('%PDF-'));
    assert.throws(() => buildDashboardPdf({ edition: null }), /Nenhuma edição/);
});

test('the map image is included before the tables with the edition fallback note', () => {
    const statistics = fixture();
    statistics.fallback = true;
    const pixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
    const { doc, chartPages } = buildDashboardPdf(statistics, pixel);
    const firstPage = doc.internal.pages[1].join('\n');
    assert.equal(chartPages, 3);
    assert.ok(firstPage.includes('/I0 Do'));
    assert.ok(firstPage.includes('Brasil'));
    assert.ok(firstPage.includes('Nenhuma'));
    assert.ok(!firstPage.includes('(Tabelas'));
});

test('charts and tables share each page in two separate columns', () => {
    const { doc, chartPages } = buildDashboardPdf(fixture());
    const pages = doc.internal.pages.slice(1).map(page => page.join('\n'));
    assert.equal(doc.internal.pageSize.getWidth() > doc.internal.pageSize.getHeight(), true);
    assert.ok(pages[0].includes('Modalidade'));
    assert.ok(pages[1].includes('Categoria'));
    assert.ok(pages[1].includes('Sexo'));
    assert.ok(pages[2].includes('Escolaridade'));
    assert.ok(pages[chartPages].includes('Resumo por categoria'));
    assert.ok(pages[chartPages].includes('Totais por'));
    assert.equal(doc.getNumberOfPages(), 7);
});

test('long tables paginate both columns without dropping rows or repeating the page header', () => {
    const statistics = fixture();
    statistics.current.categories = Array.from({ length: 90 }, (_, index) => row(`Categoria ${index}`));
    statistics.current.regions = Array.from({ length: 90 }, (_, index) => row(`Regiao ${index}`));
    const { doc, chartPages } = buildDashboardPdf(statistics);
    const tablePages = doc.internal.pages.slice(chartPages + 1).map(page => page.join('\n'));
    assert.ok(tablePages.join('\n').includes('Categoria 89'));
    assert.ok(tablePages.join('\n').includes('Regiao 89'));
    assert.ok(tablePages.every(page => (page.match(/Dashboard estat/g) || []).length === 1));
});
