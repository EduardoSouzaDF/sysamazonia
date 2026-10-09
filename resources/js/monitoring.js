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
    const elements = [...root.querySelectorAll('[data-monitoring-pie]')];
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
        const chart = new ApexCharts(element, {
            chart: { type: 'pie', height: 300, fontFamily: 'inherit', foreColor: getComputedStyle(root).color, toolbar: { show: false }, animations: { enabled: false } },
            series: JSON.parse(element.dataset.series),
            labels: ['Recomendada', 'Meritória', 'Não recomendada'],
            colors: ['#166534', '#ca8a04', '#b91c1c'],
            legend: { show: false },
            stroke: { width: 2, colors: [getComputedStyle(root).getPropertyValue('--background').trim() || '#ffffff'] },
            tooltip: { theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light', y: { formatter: value => `${value.toLocaleString('pt-BR')} inscrições` } },
            dataLabels: { enabled: true, formatter: value => `${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%` },
        });
        await chart.render();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMonitoring);
} else {
    initializeMonitoring();
}
