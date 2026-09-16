async function renderAiDashboard() {
    const data = document.getElementById('ai-metrics-data');
    if (!data) return;
    const { default: ApexCharts } = await import('../comp_themes/apexcharts/apexcharts.min.js');
    const metrics = JSON.parse(data.textContent);
    const charts = [
        ['ai-status-chart', {
            chart: { type: 'bar', height: 260, toolbar: { show: false } },
            series: [{ name: 'Execuções', data: ['pending', 'processing', 'completed', 'failed'].map(s => Number(metrics.counts[s] || 0)) }],
            xaxis: { categories: ['Aguardando', 'Processando', 'Concluídas', 'Falhas'] },
            colors: ['#2563eb'],
        }],
        ['ai-daily-chart', {
            chart: { type: 'line', height: 260, toolbar: { show: false } },
            series: [{ name: 'Técnica', data: metrics.series.technical_evaluation }, { name: 'Estratégica', data: metrics.series.strategic_selection }],
            xaxis: { categories: metrics.labels, tickAmount: Math.min(7, metrics.labels.length) },
            colors: ['#2563eb', '#b45309'], stroke: { width: [3, 3], dashArray: [0, 5] },
        }],
    ];
    for (const [id, options] of charts) {
        const element = document.getElementById(id);
        if (element) new ApexCharts(element, { ...options, yaxis: { min: 0, decimalsInFloat: 0 }, noData: { text: 'Nenhum resultado no período' } }).render();
    }
}
window.addEventListener('load', renderAiDashboard, { once: true });
