// No executable content from the plan is ever used as a renderer.
export const chartRegistry = Object.freeze({
    bar: {type:'bar'}, horizontal_bar: {type:'bar',horizontal:true},
    stacked_bar: {type:'bar',stacked:true}, line: {type:'line'}, area: {type:'area'},
    donut: {type:'donut'}, kpi:null, table:null, map_brazil:{type:'bar',horizontal:true},
});
export function chartData(data) {
    const dims = data.context.dimensions;
    const safeLabel = value => String(value ?? 'Desconhecido').replace(/[<>&"']/g, '');
    const labels = [...new Set(data.rows.map(r => safeLabel(r[dims[0]])))];
    const keys = dims.length > 1 ? [...new Set(data.rows.map(r => safeLabel(r[dims[1]])))] : [data.metric];
    const series = keys.map(key => ({name:key, data:labels.map(label => {
        const row = data.rows.find(r => safeLabel(r[dims[0]]) === label && (dims.length === 1 || safeLabel(r[dims[1]]) === key));
        return row?.value == null ? null : Number(row.value);
    })}));
    return {labels,series};
}
