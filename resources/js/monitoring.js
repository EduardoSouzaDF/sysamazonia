async function initializeMonitoring() {
    const root = document.getElementById('monitoring');
    if (!root) return;
    const dialog = root.querySelector('#monitoring-project-dialog');
    const dialogContent = dialog.querySelector('[data-monitoring-dialog-content]');
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-monitoring-view]');
        if (!button) return;
        const template = document.getElementById(button.dataset.monitoringView);
        if (!template) return;
        dialogContent.replaceChildren(template.content.cloneNode(true));
        dialogContent.querySelector('[data-project-title]').id = 'monitoring-dialog-title';
        dialog.showModal();
    });
    dialog.querySelector('[data-monitoring-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    const elements = [...root.querySelectorAll('[data-monitoring-pie], [data-monitoring-line]')];
    if (!elements.length) return;
    let ApexCharts;
    try {
        const module = await import('../comp_themes/apexcharts/apexcharts.min.js');
        ApexCharts = module.default ?? window.ApexCharts;
        if (typeof ApexCharts !== 'function') return;
    } catch {
        return;
    }
    for (const element of elements) {
        const isLine = element.hasAttribute('data-monitoring-line');
        const chart = new ApexCharts(element, isLine ? {
            chart: { type: 'line', height: 360, fontFamily: 'inherit', foreColor: '#ffffff', toolbar: { show: false }, animations: { enabled: false } },
            series: JSON.parse(element.dataset.series),
            colors: ['#4ade80', '#facc15'],
            xaxis: { categories: JSON.parse(element.dataset.editions), title: { text: 'Edição dos Prêmios' } },
            yaxis: { min: 0, max: 50, title: { text: 'Nota média (0 a 50)' }, labels: { formatter: value => value.toLocaleString('pt-BR', { maximumFractionDigits: 1 }) } },
            stroke: { width: [3, 3], curve: 'straight', dashArray: [0, 8] },
            markers: { size: [5, 0] },
            legend: { position: 'bottom', labels: { colors: '#ffffff' } },
            grid: { borderColor: '#475569' },
            tooltip: { theme: 'dark', y: { formatter: value => value == null ? 'Sem avaliações' : `${value.toLocaleString('pt-BR')} / 50` } },
            dataLabels: { enabled: false },
        } : {
            chart: { type: 'pie', height: 300, fontFamily: 'inherit', foreColor: '#ffffff', toolbar: { show: false }, animations: { enabled: false } },
            series: JSON.parse(element.dataset.series),
            labels: ['Recomendada', 'Meritória', 'Não recomendada'],
            colors: ['#166534', '#ca8a04', '#b91c1c'],
            legend: { show: false },
            stroke: { width: 2, colors: [getComputedStyle(root).getPropertyValue('--background').trim() || '#ffffff'] },
            tooltip: { theme: 'dark', y: { formatter: value => `${value.toLocaleString('pt-BR')} inscrições` } },
            dataLabels: { enabled: true, style: { colors: ['#ffffff'] }, formatter: value => `${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%` },
        });
        await chart.render();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMonitoring);
} else {
    initializeMonitoring();
}
