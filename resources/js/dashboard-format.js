export const formatNumber = value => Number(value).toLocaleString('pt-BR');
export const formatPercent = value => `${Number(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
export const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
export const shortenLabel = (label, maximum = 32) => String(label).length > maximum ? `${String(label).slice(0, maximum - 1)}…` : String(label);
export const tooltipMarkup = row => `<div class="dashboard-tooltip"><strong>${escapeHtml(row.label)}</strong><span>${formatNumber(row.total)} inscrições · ${formatPercent(row.percentage)}</span></div>`;
