const form = document.getElementById('ai-settings-form');
if (form) {
    const provider = form.elements.provider;
    const updateProviderFields = () => {
        form.querySelectorAll('[data-ai-endpoint]').forEach(element => {
            element.hidden = !['local', 'openai-compatible'].includes(provider.value);
            element.querySelector('input').disabled = element.hidden;
        });
        form.querySelectorAll('[data-ai-local]').forEach(element => {
            element.hidden = provider.value !== 'local';
            element.querySelector('select').disabled = element.hidden;
        });
    };
    provider.addEventListener('change', updateProviderFields);
    updateProviderFields();
    form.addEventListener('invalid', event => {
        const section = event.target.closest('details');
        if (section) section.open = true;
    }, true);
    let changed = false;
    form.addEventListener('input', () => { changed = true; });
    form.addEventListener('change', () => { changed = true; });
    form.addEventListener('submit', () => { changed = false; });
    window.addEventListener('beforeunload', event => {
        if (changed) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}
