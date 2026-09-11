import { chartRegistry, chartData } from './analytics-charts.js';

const names = {edition:'Edição',edition_id:'Edição',edition_ids:'Edições',edition_year:'Ano da edição',category:'Categoria',category_id:'Categoria',state:'UF',city:'Município',region:'Região',education:'Escolaridade',age_group:'Faixa etária',status:'Situação',indication_status:'Indicação',day:'Dia',week:'Semana',month:'Mês',days_to_deadline:'Dias até o encerramento',ai_status:'Situação IA',criterion:'Critério',evaluator:'Avaliador',quality_field:'Campo',value:'Valor',records:'Registros',date_from:'Início',date_to:'Fim',last_days:'Últimos dias da edição'};
const root = document.getElementById('analytics-chat');
const make = (tag, text, parent) => {
    const el = document.createElement(tag);
    if (text !== undefined) el.textContent = text;
    parent?.append(el);
    return el;
};
if (root) {
    let context = JSON.parse(document.getElementById('analytics-context').textContent);
    let busy = false;
    const charts = [];
    const history = root.querySelector('#analytics-history');
    const status = root.querySelector('#analytics-status');
    const input = root.querySelector('#analytics-question');
    const request = async (url, method, data) => {
        const response = await fetch(url, {method, headers: {'Content-Type':'application/json', Accept:'application/json', 'X-CSRF-TOKEN': root.dataset.token}, body: data ? JSON.stringify(data) : undefined});
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Não foi possível concluir a análise.');
        return payload;
    };
    const filters = () => {
        const target = root.querySelector('#analytics-filters'); target.replaceChildren();
        make('span', 'Filtros ativos: ', target);
        for (const [key, value] of Object.entries(context?.filters || {})) {
            const button = make('button', `${names[key] || key}: ${value} ×`, target);
            button.className = 'kt-btn kt-btn-outline';
            button.setAttribute('aria-label', `Remover filtro ${names[key] || key}`);
            button.onclick = () => { if (busy) return; const plan = structuredClone(context); delete plan.filters[key]; send({plan}, `Remover filtro ${names[key] || key}`); };
        }
    };
    filters();
    const render = async data => {
        const card = make('section', undefined, history); card.className = 'kt-card p-4 my-4';
        make('p', data.answer, card);
        if (!data.rows) return;
        for (const kpi of data.kpis) make('p', `${kpi.label}: ${kpi.value === null ? 'Sem dados' : new Intl.NumberFormat('pt-BR').format(kpi.value)}`, card);
        make('p', `Filtros: ${Object.entries(data.filters).map(([key,value]) => `${names[key] || key}: ${value}`).join('; ') || 'Todos'}. Período: ${data.period.from || 'Sem dados'} até ${data.period.to || 'Sem dados'} (${data.period.timezone}).`, card);
        data.warnings.forEach(w => make('p', w, card));
        const detail = make('details', undefined, card); make('summary', 'Como este resultado foi calculado?', detail);
        make('p', `${data.calculation} Fonte: ${data.source}. Registros agregados: ${data.records_aggregated}. Consulta: ${data.queried_at}`, detail);
        const config = chartRegistry[data.chart.type];
        if (config && data.rows.length) {
            await import('../comp_themes/apexcharts/apexcharts.min.js');
            const ApexCharts = window.ApexCharts;
            const chartEl = make('div', undefined, card); chartEl.setAttribute('role','img'); chartEl.setAttribute('aria-label', `Gráfico ${data.metric}; dados completos na tabela seguinte.`);
            const { labels, series } = chartData(data);
            const chart = new ApexCharts(chartEl, {
                chart:{type:config.type, height:350, stacked:!!config.stacked && /_count$/.test(data.metric), animations:{enabled:false}, toolbar:{show:false}},
                series:config.type === 'donut' ? data.rows.map(r => Number(r.value)) : series,
                labels, xaxis:{categories:labels, labels:{trim:true, maxHeight:80}},
                plotOptions:{bar:{horizontal:!!config.horizontal}}, colors:['#2563eb','#b45309','#047857','#7c3aed','#be123c'],
                dataLabels:{enabled:false}, tooltip:{enabled:false}, noData:{text:'Sem dados'},
            });
            charts.push({chart,card}); chart.render().catch(() => { chartEl.textContent = 'Use a tabela abaixo para consultar os dados.'; });
        }
        make('button','Exportar CSV (linhas exibidas)',card).onclick = () => {
            const cell = value => {
                let text = String(value ?? '');
                if (/^(?:\s*[=+@-]|[\t\r])/.test(text)) text = "'" + text;
                return '"' + text.replaceAll('"','""') + '"';
            };
            const csv = [data.columns, ...data.rows.map(r => data.columns.map(c => r[c]))].map(r => r.map(cell).join(';')).join('\r\n');
            const url = URL.createObjectURL(new Blob(['\ufeff', csv], {type:'text/csv;charset=utf-8'}));
            const link = make('a'); link.href=url; link.download='analise-agregada.csv'; link.click(); setTimeout(()=>URL.revokeObjectURL(url),1000);
        };
        let rows = [...data.rows], page = 0, direction = 1;
        const wrapper = make('div', undefined, card); wrapper.style.overflowX = 'auto';
        const table = make('table', undefined, wrapper); table.className = 'kt-table';
        make('caption', `${data.total_groups} grupos no resultado; ${rows.length} disponíveis nesta resposta.`, table);
        const head = make('tr', undefined, make('thead', undefined, table));
        const body = make('tbody', undefined, table);
        const pageLabel = make('p', '', card);
        const draw = () => {body.replaceChildren(); for (const row of rows.slice(page*20, page*20+20)) {const tr = make('tr', undefined, body); data.columns.forEach(c => make('td', row[c] ?? 'Sem dados', tr));} pageLabel.textContent = `Página ${page+1} de ${Math.max(1,Math.ceil(rows.length/20))}`;};
        data.columns.forEach(c => {const th = make('th', undefined, head); th.scope = 'col'; const b = make('button', names[c] || c, th); b.onclick = () => {direction *= -1; rows.sort((a,b) => typeof a[c] === 'number' ? direction*(a[c]-b[c]) : direction*String(a[c] ?? '').localeCompare(String(b[c] ?? ''), 'pt-BR', {numeric:true})); page=0; draw();};});
        make('button','Anterior',card).onclick = () => {page=Math.max(0,page-1);draw();};
        make('button','Próxima',card).onclick = () => {page=Math.min(Math.max(0,Math.ceil(rows.length/20)-1),page+1);draw();}; draw();

    };
    const send = async (payload, label) => {
        if (busy) return; busy=true; status.textContent='Analisando…'; root.setAttribute('aria-busy','true');
        make('p', label, history);
        try { const result = await request(root.dataset.query,'POST',payload); if (result.context) context=result.context; filters(); await render(result); input.value=''; }
        catch (e) { status.textContent=e.message; }
        finally {
            while (history.children.length > 20) history.firstChild.remove();
            for (let i=charts.length-1;i>=0;i--) if (!history.contains(charts[i].card)) { charts[i].chart.destroy(); charts.splice(i,1); }
            busy=false;root.setAttribute('aria-busy','false');if(status.textContent==='Analisando…')status.textContent='';}
    };
    root.querySelector('#analytics-form').onsubmit = e => {e.preventDefault();send({question:input.value},input.value);};
    root.querySelectorAll('#analytics-examples button').forEach(b => b.onclick = () => send({question:b.textContent},b.textContent));
    root.querySelector('#analytics-clear').onclick = async () => {
        if (busy) return;
        try {await request(root.dataset.clear,'DELETE'); context=null; charts.splice(0).forEach(({chart}) => chart.destroy());history.replaceChildren();filters();status.textContent='';}
        catch(e) {status.textContent=e.message;}
    };
}
