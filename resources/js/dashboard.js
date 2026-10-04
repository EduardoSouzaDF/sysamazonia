import { formatNumber, formatPercent, shortenLabel, tooltipMarkup } from './dashboard-format';

async function initializeDashboard() {
    const root = document.getElementById('statistics-dashboard');
    const data = window.dashboardStatistics;
    if (!root || !data) return;
    const tabs = [...root.querySelectorAll('[data-kt-tab-toggle]')];
    const panels = tabs.map(tab => root.querySelector(tab.dataset.ktTabToggle));
    root.querySelector('[data-kt-tabs]').setAttribute('role', 'tablist');
    root.querySelector('[data-kt-tabs]').setAttribute('aria-label', 'Escopo das estatísticas');
    const syncTabs = () => tabs.forEach((tab, index) => {
        const selected = !panels[index].classList.contains('hidden');
        tab.setAttribute('role', 'tab');
        tab.setAttribute('aria-controls', panels[index].id);
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
        panels[index].setAttribute('role', 'tabpanel');
        panels[index].setAttribute('aria-labelledby', tab.id);
    });
    const activateTab = index => {
        tabs.forEach((tab, i) => {
            tab.classList.toggle('active', i === index);
            panels[i].classList.toggle('hidden', i !== index);
        });
        syncTabs();
    };
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateTab(index));
        tab.addEventListener('keydown', event => {
            let next;
            if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') next = (index + tabs.length - 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            if (next === undefined) return;
            event.preventDefault();
            activateTab(next);
            tabs[next].focus();
        });
    });
    syncTabs();
    const selectState = uf => {
        const state = data.current?.map.find(row => row.uf === uf);
        if (!state) return;
        root.querySelectorAll('[data-uf]').forEach(path => path.setAttribute('aria-pressed', String(path.dataset.uf === uf)));
        root.querySelectorAll('[data-state-row]').forEach(row => row.classList.toggle('selected', row.dataset.stateRow === uf));
        document.getElementById('selected-state-title').textContent = `${state.name} (${state.uf})`;
        document.getElementById('selected-state-details').textContent = `${state.region} · ${formatNumber(state.total)} inscrições · ${formatPercent(state.percentage)} do total da edição.`;
    };
    root.querySelectorAll('[data-uf], [data-select-uf]').forEach(element => {
        element.addEventListener('click', () => selectState(element.dataset.uf || element.dataset.selectUf));
        if (element.hasAttribute('data-uf')) {
            element.addEventListener('keydown', event => {
                if (['Enter', ' '].includes(event.key)) {
                    event.preventDefault();
                    selectState(element.dataset.uf);
                }
            });
        }
    });
    let ApexCharts;
    try {
        ({ default: ApexCharts } = await import('../comp_themes/apexcharts/apexcharts.min.js'));
    } catch {
        // Server-rendered tables and the map remain available if the chart bundle fails.
        return;
    }
    const charts = new Map();
    const renderVisible = () => {
        syncTabs();
        root.querySelectorAll('[data-dashboard-chart]').forEach(element => {
            if (!element.getClientRects().length) return;
            const width = Math.floor(element.parentElement.clientWidth);
            if (width < 1) return;
            const existing = charts.get(element);
            if (existing) {
                if (existing.width !== width) {
                    existing.width = width;
                    existing.chart.updateOptions({ chart: { width } }, false, false);
                }
                return;
            }
            const rows = data[element.dataset.dashboardScope][element.dataset.dashboardChart];
            const horizontal = element.dataset.chartHorizontal === 'true';
            const maxLabel = width < 450 ? 20 : 34;
            const chart = new ApexCharts(element, {
                chart: { type: 'bar', width, height: horizontal ? Math.max(260, rows.length * 38 + 50) : 340, toolbar: { show: false }, animations: { enabled: false }, fontFamily: 'inherit', foreColor: getComputedStyle(root).color },
                series: [{ name: 'Inscrições', data: rows.map(row => row.total) }],
                colors: [getComputedStyle(element).getPropertyValue('--dashboard-accent').trim()],
                plotOptions: { bar: { horizontal, borderRadius: 3, barHeight: '62%', columnWidth: '55%' } },
                dataLabels: { enabled: false },
                xaxis: { categories: rows.map(row => row.label), labels: { rotate: horizontal ? 0 : -35, trim: true, formatter: value => horizontal ? formatNumber(value) : shortenLabel(value, maxLabel) }, decimalsInFloat: 0 },
                yaxis: horizontal ? { labels: { maxWidth: width < 450 ? 140 : 260, formatter: value => shortenLabel(value, maxLabel) } } : { min: 0, decimalsInFloat: 0, labels: { formatter: formatNumber } },
                tooltip: { custom: ({ dataPointIndex }) => rows[dataPointIndex] ? tooltipMarkup(rows[dataPointIndex]) : '' },
                grid: { borderColor: getComputedStyle(root).getPropertyValue('--border').trim() || '#e2e8f0' },
                noData: { text: 'Nenhuma inscrição disponível' },
            });
            charts.set(element, { chart, width });
            chart.render();
        });
    };
    let frame;
    const scheduleRender = () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(renderVisible); };
    const observer = new MutationObserver(scheduleRender);
    panels.forEach(panel => observer.observe(panel, { attributes: true, attributeFilter: ['class'] }));
    const resize = new ResizeObserver(scheduleRender);
    root.querySelectorAll('.dashboard-chart-scroll').forEach(element => resize.observe(element));
    renderVisible();
}

if (document.readyState === 'complete') initializeDashboard();
else window.addEventListener('load', initializeDashboard, { once: true });
