import '../css/commission-guides.css';
import { guides, contextGuides, automaticGuide, taskSteps } from './commission-guide-content';

export function initializeCommissionGuides() {
    const host = document.querySelector('[data-commission-guides]');
    if (!host) return;
    const config = JSON.parse(host.querySelector('[data-guide-config]').textContent);
    const help = host.querySelector('[data-guide-help]');
    const progress = config.progress;
    let balloon = null;
    let highlighted = null;
    let active = null;
    let returnFocus = null;
    let saving = false;
    let frame = null;
    let requestQueue = Promise.resolve();
    const drawer = document.querySelector('[data-drawer]');
    let drawerOpen = false;
    let beforeDrawer = null;
    let automatic = false;
    let dismissed = false;

    function availableContexts() {
        const ids = contextGuides(config.page, config.profiles, {
            drawer: drawerOpen,
            panel: Boolean(document.querySelector('.judging-current-category')),
            done: Boolean(document.querySelector('.judging-completed')),
            empty: Boolean(document.querySelector('[data-guide-judge-empty]')),
            votes: Boolean(document.querySelector('[aria-labelledby="votos-title"]')),
            detail: Boolean(document.querySelector('[data-guide-registration-content]')),
        });
        return ids.filter(id => config.available.includes(id))
            .filter(id => id !== 'evaluator-detail' || document.querySelector('[data-guide-evaluation-form]'))
            .filter(id => id !== 'indicator-detail' || document.querySelector('[data-guide-indication-submit]'));
    }

    function findTarget(selector) {
        if (!selector) return null;
        return [...document.querySelectorAll(selector)].find(element => {
            const rect = element.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0 && getComputedStyle(element).visibility !== 'hidden';
        }) ?? null;
    }

    function usableSteps(id) {
        // Keep original indices so an optional section cannot shift persisted progress.
        const taskIndex = guides[id].steps.findIndex(step => step.id === taskSteps[id]);
        return guides[id].steps.map((step, index) => ({ ...step, index }))
            .filter(step => !automatic || taskIndex < 0 || step.index <= taskIndex || step.id.endsWith('-empty'))
            .filter(step => !step.optional || findTarget(step.target));
    }

    function removeHighlight() {
        highlighted?.classList.remove('commission-guide-highlight');
        highlighted = null;
    }

    function close(restore = true) {
        removeHighlight();
        balloon?.remove();
        balloon = null;
        active = null;
        if (restore && returnFocus?.isConnected) returnFocus.focus({ preventScroll: true });
    }

    function shell() {
        close(false);
        balloon = document.createElement('section');
        balloon.className = 'commission-guide-balloon';
        balloon.setAttribute('role', 'dialog');
        balloon.setAttribute('aria-modal', 'false');
        balloon.setAttribute('aria-labelledby', 'commission-guide-title');
        balloon.tabIndex = -1;
        // Keep the balloon inside Shoelace's focus boundary while its drawer is open.
        const parent = drawerOpen ? drawer.querySelector('[data-drawer-body]') : document.body;
        parent.appendChild(balloon);
        const dismiss = document.createElement('button');
        dismiss.type = 'button';
        dismiss.className = 'guide-close';
        dismiss.textContent = '×';
        dismiss.setAttribute('aria-label', 'Fechar orientação');
        dismiss.addEventListener('click', pause);
        balloon.appendChild(dismiss);
        return balloon;
    }

    function title(text) {
        const element = document.createElement('h2');
        element.id = 'commission-guide-title';
        element.textContent = text;
        balloon.appendChild(element);
    }

    function paragraph(text, className = '') {
        const element = document.createElement('p');
        element.textContent = text;
        element.className = className;
        balloon.appendChild(element);
        return element;
    }

    function controls() {
        const element = document.createElement('div');
        element.className = 'guide-controls';
        balloon.appendChild(element);
        return element;
    }

    function button(parent, label, action, primary = false) {
        const element = document.createElement('button');
        element.type = 'button';
        element.textContent = label;
        if (primary) element.className = 'guide-primary';
        element.addEventListener('click', action);
        parent.appendChild(element);
    }

    function position() {
        if (!balloon) return;
        const gap = 12;
        const width = balloon.offsetWidth;
        const height = balloon.offsetHeight;
        const rect = highlighted?.getBoundingClientRect();
        const viewportHeight = window.visualViewport?.height ?? window.innerHeight;
        const viewportWidth = window.visualViewport?.width ?? window.innerWidth;
        let left = (viewportWidth - width) / 2;
        let top = (viewportHeight - height) / 2;
        if (rect && viewportWidth > 640) {
            left = rect.right + gap;
            if (left + width > viewportWidth - gap) left = rect.left - width - gap;
            if (left < gap) left = rect.left;
            top = rect.top;
            if (left < rect.right && left + width > rect.left) {
                top = rect.bottom + gap;
                if (top + height > viewportHeight - gap) top = rect.top - height - gap;
            }
        } else if (rect) {
            top = rect.bottom + gap;
            if (top + height > viewportHeight - gap) {
                top = rect.top - height - gap;
                if (top < gap) top = (viewportHeight - height) / 2;
            }
        }
        left = Math.max(gap, Math.min(left, viewportWidth - width - gap));
        top = Math.max(gap, Math.min(top, viewportHeight - height - gap));
        balloon.style.left = `${left}px`;
        balloon.style.top = `${top}px`;
        // A transformed drawer can become the containing block of fixed children.
        const actual = balloon.getBoundingClientRect();
        balloon.style.left = `${left + (left - actual.left)}px`;
        balloon.style.top = `${top + (top - actual.top)}px`;
    }

    function reposition() {
        if (frame) return;
        frame = requestAnimationFrame(() => { frame = null; position(); });
    }

    function focusBalloon() {
        position();
        balloon.focus({ preventScroll: true });
    }

    function save(id, step, status) {
        const operation = requestQueue.catch(() => {}).then(async () => {
            const response = await fetch(config.url, {
                method: 'PUT', credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrf },
                body: JSON.stringify({ guide: id, version: config.version, step, status }),
                keepalive: true,
            });
            if (!response.ok) throw new Error('Não foi possível salvar as orientações. Tente novamente.');
            progress[id] = { step, status };
        });
        requestQueue = operation;
        return operation;
    }

    async function run(action) {
        if (saving) return;
        saving = true;
        const currentBalloon = balloon;
        currentBalloon?.querySelectorAll('button').forEach(element => { element.disabled = true; });
        currentBalloon?.querySelector('.guide-error')?.remove();
        currentBalloon?.querySelector('[data-guide-error-controls]')?.remove();
        try { await action(); }
        catch (error) {
            if (balloon === currentBalloon) {
                const message = paragraph(error.message, 'guide-error');
                message.setAttribute('role', 'alert');
                const errorControls = controls();
                errorControls.setAttribute('data-guide-error-controls', '');
                button(errorControls, 'Fechar sem salvar', () => {
                    close();
                });
                position();
            }
        } finally {
            saving = false;
            balloon?.querySelectorAll('button').forEach(element => { element.disabled = false; });
        }
    }

    function showStep(id, index) {
        const steps = usableSteps(id);
        const current = steps.find(step => step.index >= index) ?? steps.at(-1);
        if (!current) { close(); return; }
        shell();
        active = { id, step: current.index };
        const visibleIndex = steps.indexOf(current);
        paragraph(`Passo ${visibleIndex + 1} de ${steps.length} · ${guides[id].title}`, 'guide-progress');
        title(current.title);
        paragraph(current.text);
        const target = findTarget(current.target);
        if (current.target && !target) paragraph('Este passo não está disponível na tela atual. Você pode continuar para a próxima orientação ou consultar o guia depois.');
        if (target) {
            highlighted = target;
            target.classList.add('commission-guide-highlight');
            target.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' });
        }
        const actions = controls();
        const currentBalloon = balloon;
        if (visibleIndex > 0) button(actions, 'Anterior', () => run(async () => {
            await save(id, steps[visibleIndex - 1].index, 'active');
            if (balloon === currentBalloon) showStep(id, steps[visibleIndex - 1].index);
        }));
        button(actions, 'Ver depois', pause);
        if (automatic && (current.id === taskSteps[id] || current.id.endsWith('-empty'))) {
            paragraph(current.id.endsWith('-empty') ? 'Ajuste os filtros ou volte quando houver inscrições disponíveis.' : id.endsWith('-list') ? 'Abra uma inscrição para continuar.' : id === 'judge-detail' ? 'Escolha a inscrição ou feche os detalhes para voltar à votação.' : 'Faça a ação indicada na tela para terminar esta tarefa.', 'guide-hint');
            focusBalloon();
            return;
        }
        const last = visibleIndex === steps.length - 1;
        button(actions, last ? 'Concluir guia' : 'Próximo', () => run(async () => {
            const next = last ? current.index : steps[visibleIndex + 1].index;
            await save(id, next, last ? 'completed' : 'active');
            if (balloon !== currentBalloon) return;
            if (last) close();
            else showStep(id, next);
        }), true);
        focusBalloon();
    }

    function start(id, replay = false) {
        automatic = false;
        const step = replay ? 0 : (progress[id]?.step ?? 0);
        const currentBalloon = balloon;
        return run(async () => {
            await save(id, step, 'active');
            if (balloon === currentBalloon) showStep(id, step);
        });
    }

    function pause() {
        if (!active || saving) return;
        dismissed = true;
        const { id, step } = active;
        const currentBalloon = balloon;
        return run(async () => {
            await save(id, step, 'paused');
            if (balloon === currentBalloon) close();
        });
    }

    function helpMenu() {
        if (saving) return;
        automatic = false;
        returnFocus = document.activeElement;
        shell();
        title('Rever orientações');
        paragraph('Escolha o guia desta tela. Os guias de outras funcionalidades estarão disponíveis quando você acessá-las.');
        const actions = controls();
        for (const id of availableContexts()) {
            button(actions, guides[id].title, () => start(id, progress[id]?.status === 'completed'));
        }
        if (!availableContexts().length) {
            for (const profile of config.profiles) button(actions, `Abrir ${profile === 'judge' ? 'julgamento' : profile === 'evaluator' ? 'avaliações' : 'indicações'}`, () => { window.location.href = config.entryUrls[profile]; });
        }
        button(actions, 'Boas-vindas', () => start('welcome', true));
        button(actions, 'Fechar orientação', () => close());
        focusBalloon();
    }

    help.addEventListener('click', helpMenu);
    document.querySelector('[data-guide-drawer-help]')?.addEventListener('click', helpMenu);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && balloon) {
            event.preventDefault();
            event.stopImmediatePropagation();
            if (active) pause(); else close();
        }
    }, true);
    document.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition);
    window.visualViewport?.addEventListener('resize', reposition);

    drawer?.addEventListener('sl-show', event => {
        if (event.target === drawer) { beforeDrawer = active; close(false); drawerOpen = true; }
    });
    drawer?.addEventListener('sl-after-show', async event => {
        if (event.target !== drawer) return;
        const previous = beforeDrawer;
        beforeDrawer = null;
        close(false);
        drawerOpen = true;
        if (previous) {
            try { await save(previous.id, previous.step, 'paused'); }
            catch { /* Keep the last server-confirmed progress. */ }
        }
        if (automatic && !dismissed && config.automaticProfiles.includes('judge')) showStep('judge-detail', 0);
    });
    drawer?.addEventListener('sl-after-hide', async event => {
        if (event.target !== drawer) return;
        const previous = active;
        close(false);
        drawerOpen = false;
        if (previous) {
            try { await save(previous.id, previous.step, 'paused'); }
            catch { /* Preserve server-confirmed progress without blocking the page. */ }
        }
        if (automatic && !dismissed && config.automaticProfiles.includes('judge') && availableContexts().includes('judge-panel')) {
            const index = guides['judge-panel'].steps.findIndex(step => step.id === 'JU-09');
            showStep('judge-panel', index);
        }
    });
    returnFocus = help;
    document.addEventListener('commission-guide-task-completed', event => {
        config.automaticProfiles = config.automaticProfiles.filter(profile => profile !== event.detail.profile);
        if (automatic) close();
    });
    const id = automaticGuide(availableContexts(), config.automaticProfiles);
    if (id) {
        automatic = true;
        // A new page continues the task; a completed tour is not a completed operation.
        const previous = progress[id];
        showStep(id, previous?.status === 'active' ? previous.step : 0);
    } else if (config.page === 'home' && config.automaticProfiles.length) {
        shell();
        title('Vamos começar');
        paragraph('Escolha sua atividade. O guia acompanha você até concluir a primeira tarefa. Você pode voltar a acessar o guia a qualquer momento pelo botão Rever orientações.');
        const actions = controls();
        for (const profile of config.automaticProfiles) {
            button(actions, profile === 'judge' ? 'Iniciar julgamento' : profile === 'evaluator' ? 'Avaliar' : 'Indicar', () => { window.location.href = config.entryUrls[profile]; }, true);
        }
        button(actions, 'Fechar', () => close());
        focusBalloon();
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeCommissionGuides);
else initializeCommissionGuides();
