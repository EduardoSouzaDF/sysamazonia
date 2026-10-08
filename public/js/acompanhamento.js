/**
 * Acompanhamento do julgamento (spec 0004): modais de confirmação, atualização
 * automática da matriz e dos votos, e seleção de agraciados com limite, busca e
 * espelho do destaque "Mais votadas".
 */
document.addEventListener('DOMContentLoaded', () => {
    // Delegação: os botões das regiões atualizadas são recriados a cada ciclo.
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-dialog-open]');

        if (opener) {
            document.getElementById(opener.dataset.dialogOpen)?.showModal();
            return;
        }

        event.target.closest('[data-dialog-close]')?.closest('dialog')?.close();
    });

    setupLiveRefresh();

    setupAwardees(document.querySelector('[data-awardees]'));
});

/**
 * Escolha dos agraciados: a ordem de seleção define a posição (1º lugar,
 * 2º lugar…), reordenável pelas setas; o envio segue a ordem da classificação.
 */
function setupAwardees(form) {
    if (!form) {
        return;
    }

    const limit = Number(form.dataset.limit);
    const picks = [...form.querySelectorAll('[data-pick]')];
    const mirrors = [...form.querySelectorAll('[data-mirror]')];
    const slots = [...form.querySelectorAll('[data-slot]')];
    const inputs = form.querySelector('[data-ranking-inputs]');
    const counter = form.querySelector('[data-awardees-count]');
    const submit = form.querySelector('[data-awardees-submit]');
    const pickById = (id) => picks.find((pick) => pick.value === id);
    let ranking = [];

    const button = (label, title, action) => {
        const element = document.createElement('button');
        element.type = 'button';
        element.className = 'kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost';
        element.textContent = label;
        element.title = title;
        element.setAttribute('aria-label', title);
        element.addEventListener('click', action);
        return element;
    };

    const move = (index, offset) => {
        const target = index + offset;
        [ranking[index], ranking[target]] = [ranking[target], ranking[index]];
        render();
    };

    const remove = (id) => {
        ranking = ranking.filter((item) => item !== id);
        render();
    };

    const render = () => {
        const isFull = ranking.length >= limit;

        slots.forEach((slot, index) => {
            const content = slot.querySelector('[data-slot-content]');
            const id = ranking[index];
            slot.querySelectorAll('[data-slot-actions]').forEach((actions) => actions.remove());

            if (!id) {
                content.textContent = 'Selecione uma inscrição abaixo';
                content.className = 'grow text-sm text-muted-foreground';
                slot.classList.add('border-dashed');
                return;
            }

            const pick = pickById(id);
            content.textContent = `${pick.dataset.code} — ${pick.dataset.label}`;
            content.className = 'grow text-sm text-mono';
            slot.classList.remove('border-dashed');

            const actions = document.createElement('span');
            actions.dataset.slotActions = '';
            actions.className = 'flex gap-1';
            if (index > 0) {
                actions.append(button('↑', 'Subir uma posição', () => move(index, -1)));
            }
            if (index < ranking.length - 1) {
                actions.append(button('↓', 'Descer uma posição', () => move(index, 1)));
            }
            actions.append(button('✕', 'Remover da classificação', () => remove(id)));
            slot.append(actions);
        });

        inputs.replaceChildren(...ranking.map((id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'inscriptions[]';
            input.value = id;
            return input;
        }));

        picks.forEach((pick) => {
            pick.checked = ranking.includes(pick.value);
            pick.disabled = isFull && !pick.checked;
        });

        mirrors.forEach((mirror) => {
            const pick = pickById(mirror.dataset.mirror);
            mirror.checked = pick.checked;
            mirror.disabled = pick.disabled;
        });

        counter.textContent = ranking.length;
        submit.disabled = ranking.length === 0;
    };

    const toggle = (id, selected) => {
        ranking = selected ? [...ranking.filter((item) => item !== id), id] : ranking.filter((item) => item !== id);
        render();
    };

    picks.forEach((pick) => pick.addEventListener('change', () => toggle(pick.value, pick.checked)));
    mirrors.forEach((mirror) => mirror.addEventListener('change', () => toggle(mirror.dataset.mirror, mirror.checked)));

    const search = form.querySelector('[data-search]');
    const empty = form.querySelector('[data-search-empty]');

    search.addEventListener('input', () => {
        const term = search.value.trim().toLowerCase();
        let visible = 0;

        form.querySelectorAll('[data-option]').forEach((row) => {
            const matches = row.dataset.searchText.includes(term);
            row.classList.toggle('hidden', !matches);
            visible += matches ? 1 : 0;
        });

        empty.classList.toggle('hidden', visible > 0);
    });

    render();
}

/**
 * Busca a própria página a cada `data-refresh-interval` ms e troca só as regiões
 * `[data-live]`. Pausa com a aba oculta ou com um modal aberto, para não
 * fechar uma confirmação em andamento; para de vez se a sessão expirar.
 */
function setupLiveRefresh() {
    const container = document.querySelector('[data-refresh-interval]');

    if (!container) {
        return;
    }

    const interval = Number(container.dataset.refreshInterval);
    const updatedAt = container.querySelector('[data-live-updated-at]');
    let timer = null;

    const refresh = async () => {
        if (document.hidden || document.querySelector('dialog[open]')) {
            return;
        }

        try {
            const response = await fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            if (!response.ok || response.redirected) {
                clearInterval(timer);
                return;
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');

            container.querySelectorAll('[data-live]').forEach((region) => {
                const fresh = page.querySelector(`[data-live="${region.dataset.live}"]`);

                if (!fresh) {
                    return;
                }

                const scrollLeft = region.querySelector('.kt-scrollable-x-auto')?.scrollLeft ?? 0;
                region.replaceWith(fresh);

                const scroller = fresh.querySelector('.kt-scrollable-x-auto');
                if (scroller) {
                    scroller.scrollLeft = scrollLeft;
                }
            });

            updatedAt.textContent = new Date().toLocaleTimeString('pt-BR');
        } catch (error) {
            // Falha de rede: tenta de novo no próximo ciclo.
        }
    };

    timer = setInterval(refresh, interval);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refresh();
        }
    });
}
