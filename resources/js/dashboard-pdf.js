import { jsPDF } from 'jspdf';
import { autoTable } from 'jspdf-autotable';
import { formatNumber, formatPercent } from './dashboard-format.js';

const dimensions = [
    ['modalities', 'Modalidade', '#246b35'],
    ['categories', 'Categoria', '#246589'],
    ['sex', 'Sexo', '#207568'],
    ['ages', 'Faixa etária', '#61752c'],
    ['education', 'Escolaridade', '#8a6b18'],
];
const mapColors = ['#e2e8e3', '#c1ddc5', '#7cb589', '#40874f', '#205a2e'];

export function reportTables(current) {
    const validStates = new Set(current.map.map(state => state.uf));
    return [
        { title: 'Resumo por categoria', head: ['Categoria · Modalidade', 'Inscrições', 'Percentual'], rows: current.categories.map(row => [row.label, formatNumber(row.total), formatPercent(row.percentage)]), total: true },
        { title: 'Totais por região', head: ['Região', 'Inscrições', 'Percentual'], rows: current.regions.map(row => [row.label, formatNumber(row.total), formatPercent(row.percentage)]) },
        { title: 'Distribuição por UF', head: ['Estado', 'Região', 'Inscrições', 'Percentual'], rows: [
            ...current.map.map(state => [`${state.name} (${state.uf})`, state.region, formatNumber(state.total), formatPercent(state.percentage)]),
            ...current.states.filter(row => !validStates.has(row.label)).map(row => [row.label, 'Não informado', formatNumber(row.total), formatPercent(row.percentage)]),
        ] },
        ...dimensions.filter(([key]) => key !== 'categories').map(([key, title]) => ({ title, head: ['Grupo', 'Inscrições', 'Percentual'], rows: current[key].map(row => [row.label, formatNumber(row.total), formatPercent(row.percentage)]) })),
    ];
}

export function buildDashboardPdf(statistics, mapImage = null) {
    if (!statistics.edition || !statistics.current) throw new Error('Nenhuma edição disponível.');
    const current = statistics.current;
    const doc = new jsPDF({ unit: 'mm', format: 'a4', orientation: 'landscape', compress: true });
    doc.setProperties({ title: `Dashboard — ${statistics.edition.title}`, subject: 'Estatísticas da edição atual', creator: 'Prêmio Amazônia' });
    const margin = 14;
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const width = pageWidth - margin * 2;
    const gap = 10;
    const columnWidth = (width - gap) / 2;
    const columnX = [margin, margin + columnWidth + gap];
    const bottom = pageHeight - 24;
    let chartPages = 0;
    const header = (section, title, newPage = true) => {
        if (newPage) doc.addPage();
        doc.setFont('helvetica', 'bold').setFontSize(14).setTextColor('#246b35');
        doc.text('Dashboard estatístico', margin, 18);
        doc.setFontSize(9).setTextColor('#172018');
        const editionLines = doc.splitTextToSize(statistics.edition.title, width);
        doc.text(editionLines, margin, 25);
        let y = 25 + editionLines.length * 5;
        doc.setFont('helvetica', 'normal').setFontSize(8).setTextColor('#475569');
        doc.text(`Dados atualizados em ${current.generatedAt}`, margin, y);
        y += 6;
        doc.text(`Total: ${formatNumber(current.total)} · Regulares: ${formatNumber(current.regular)} · Honoríficas: ${formatNumber(current.honorary)}`, margin, y);
        y += 6;
        if (statistics.fallback) {
            const note = doc.splitTextToSize('Nenhuma edição com inscrições ativas. Relatório da edição mais recente.', width);
            doc.text(note, margin, y);
            y += note.length * 4 + 2;
        }
        doc.setFont('helvetica', 'bold').setFontSize(10).setTextColor('#172018');
        const lines = doc.splitTextToSize(`${section} — ${title}`, width);
        doc.text(lines, margin, y + 3);
        return y + lines.length * 5 + 9;
    };

    const chartItems = [
        mapImage ? { title: 'Distribuição pelo Brasil', image: mapImage } : {
            title: 'Distribuição por UF', rows: current.map.map(state => ({ ...state, label: `${state.name} (${state.uf})` })), color: '#246589',
        },
        ...dimensions.map(([key, title, color]) => ({ title, rows: current[key], color })),
    ];
    const columnTitle = (title, x, y) => {
        doc.setFont('helvetica', 'bold').setFontSize(10).setTextColor('#172018');
        const lines = doc.splitTextToSize(title, columnWidth);
        doc.text(lines, x, y);
        return y + lines.length * 4 + 4;
    };
    const drawMap = (x, y) => {
        const size = Math.min(100, columnWidth - 8, bottom - y - 30);
        doc.addImage(mapImage, 'PNG', x + (columnWidth - size) / 2, y, size, size);
        y += size + 7;
        const maximum = Math.max(0, ...current.map.map(state => state.total));
        let legendX = x;
        doc.setFont('helvetica', 'normal').setFontSize(7);
        for (let level = 0; level <= 4; level++) {
            const lower = Math.floor((level - 1) * maximum / 4) + 1;
            const upper = Math.floor(level * maximum / 4);
            if (level > 0 && lower > upper) continue;
            doc.setFillColor(mapColors[level]).rect(legendX, y - 2, 4, 2, 'F');
            doc.setTextColor('#172018').text(level === 0 ? '0' : lower === upper ? formatNumber(upper) : `${formatNumber(lower)}–${formatNumber(upper)}`, legendX + 5, y);
            legendX += columnWidth / 5;
        }
        const note = 'UF de residência do responsável. Fonte cartográfica: IBGE, malha simplificada.';
        doc.setTextColor('#475569').text(doc.splitTextToSize(note, columnWidth), x, y + 7);
        if (current.unmapped) doc.text(doc.splitTextToSize(`${formatNumber(current.unmapped)} inscrição(ões) sem UF válida, incluídas nas tabelas e nos totais.`, columnWidth), x, y + 17);
    };

    // Paginate both columns together so long distributions retain every label.
    for (let pair = 0; pair < chartItems.length; pair += 2) {
        const items = chartItems.slice(pair, pair + 2);
        const indices = items.map(() => 0);
        const finished = items.map(() => false);
        let continuation = false;
        while (finished.some(done => !done)) {
            const top = header('Gráficos', continuation ? 'Distribuições (continuação)' : 'Distribuições da edição', chartPages > 0);
            chartPages++;
            items.forEach((item, column) => {
                if (finished[column]) return;
                const x = columnX[column];
                let y = columnTitle(`${item.title}${indices[column] ? ' (continuação)' : ''}`, x, top);
                if (item.image) {
                    drawMap(x, y);
                    finished[column] = true;
                    return;
                }
                const maximum = Math.max(1, ...item.rows.map(row => row.total));
                if (!item.rows.length) {
                    doc.setFont('helvetica', 'normal').setFontSize(8);
                    doc.text(doc.splitTextToSize('Nenhuma inscrição disponível para esta distribuição.', columnWidth), x, y);
                }
                while (indices[column] < item.rows.length) {
                    const row = item.rows[indices[column]];
                    doc.setFont('helvetica', 'normal').setFontSize(8);
                    const lines = doc.splitTextToSize(row.label, columnWidth * .45);
                    const height = Math.max(9, lines.length * 3.4 + 3);
                    if (y + height > bottom) break;
                    doc.setTextColor('#172018').text(lines, x, y + 3);
                    const barX = x + columnWidth * .48;
                    const barWidth = columnWidth * .3;
                    doc.setFillColor('#edf2ed').rect(barX, y, barWidth, 3.5, 'F');
                    if (row.total > 0) doc.setFillColor(item.color).rect(barX, y, barWidth * row.total / maximum, 3.5, 'F');
                    doc.text(formatNumber(row.total), x + columnWidth * .81, y + 3);
                    doc.setFontSize(7).setTextColor('#475569');
                    doc.text(formatPercent(row.percentage), x + columnWidth * .81, y + 6.5);
                    y += height;
                    indices[column]++;
                }
                finished[column] = indices[column] === item.rows.length;
            });
            continuation = true;
        }
    }

    const tables = reportTables(current);
    const tableHeaders = new Set();
    for (let pair = 0; pair < tables.length; pair += 2) {
        doc.setPage(doc.getNumberOfPages());
        const top = header('Tabelas', 'Dados da edição');
        const firstPage = doc.getCurrentPageInfo().pageNumber;
        tableHeaders.add(firstPage);
        tables.slice(pair, pair + 2).forEach((table, column) => {
            doc.setPage(firstPage);
            const x = columnX[column];
            autoTable(doc, {
                startY: top + 9,
                tableWidth: columnWidth,
                margin: { left: x, right: pageWidth - x - columnWidth, top: top + 9, bottom: 24 },
                head: [table.head],
                body: table.rows.length ? table.rows : [['Nenhum dado disponível.', ...table.head.slice(1).map(() => '')]],
                foot: table.total ? [['Total da edição', formatNumber(current.total), current.total ? '100,00%' : '0,00%']] : undefined,
                showFoot: 'lastPage',
                theme: 'striped',
                styles: { font: 'helvetica', fontSize: 8, cellPadding: 2, overflow: 'linebreak' },
                headStyles: { fillColor: '#246b35', textColor: '#ffffff' },
                footStyles: { fillColor: '#edf2ed', textColor: '#172018' },
                columnStyles: Object.fromEntries(table.head.map((_, index) => [index, { halign: index >= table.head.length - 2 ? 'right' : 'left' }])),
                rowPageBreak: 'avoid',
                willDrawPage: hook => {
                    const page = doc.getCurrentPageInfo().pageNumber;
                    if (!tableHeaders.has(page)) {
                        header('Tabelas', 'Dados da edição (continuação)', false);
                        tableHeaders.add(page);
                    }
                    columnTitle(`${table.title}${hook.pageNumber > 1 ? ' (continuação)' : ''}`, x, top);
                },
            });
        });
    }
    const pages = doc.getNumberOfPages();
    for (let page = 1; page <= pages; page++) {
        doc.setPage(page).setFont('helvetica', 'normal').setFontSize(8).setTextColor('#475569');
        doc.text('Cada registro representa uma inscrição. Idade calculada na data da inscrição.', margin, pageHeight - 14);
        doc.text(`${page} / ${pages}`, pageWidth - margin, pageHeight - 8, { align: 'right' });
    }
    return { doc, chartPages, tables };
}

async function mapToImage(svg) {
    if (!svg) return null;
    const clone = svg.cloneNode(true);
    clone.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
    clone.setAttribute('width', '1280');
    clone.setAttribute('height', '1280');
    clone.querySelectorAll('path').forEach(path => {
        const level = Number([...path.classList].find(name => name.startsWith('map-level-'))?.split('-').at(-1)) || 0;
        path.setAttribute('fill', mapColors[level]);
        path.setAttribute('stroke', '#ffffff');
        path.setAttribute('stroke-width', '1');
    });
    const url = URL.createObjectURL(new Blob([new XMLSerializer().serializeToString(clone)], { type: 'image/svg+xml;charset=utf-8' }));
    try {
        const image = new Image();
        await new Promise((resolve, reject) => { image.onload = resolve; image.onerror = reject; image.src = url; });
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = 1280;
        const context = canvas.getContext('2d');
        if (!context) throw new Error('Não foi possível exportar o mapa.');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, 1280, 1280);
        context.drawImage(image, 0, 0, 1280, 1280);
        return canvas.toDataURL('image/png');
    } finally { URL.revokeObjectURL(url); }
}

export async function downloadDashboardPdf(statistics, svg) {
    const mapImage = await mapToImage(svg);
    const { doc } = buildDashboardPdf(statistics, mapImage);
    await doc.save(`dashboard-edicao-${statistics.edition.id}.pdf`, { returnPromise: true });
}
