/*
 * Tela de Julgamento (spec 0003) — wizard categoria a categoria.
 * Pré-seleção client-side (volátil, por design): grid + drawer Shoelace + submit da categoria.
 */
(function () {
    'use strict';

    function escapeSelector(value) {
        return (window.CSS && CSS.escape) ? CSS.escape(value) : value.replace(/:/g, '\\:');
    }

    function initJulgar() {
        var root = document.querySelector('[data-julgar]');
        if (!root) {
            return;
        }

        var categoryId = root.dataset.categoryId;
        var effectiveQuota = parseInt(root.dataset.effectiveQuota || '0', 10);
        var storageKey = 'julgar-preselects-' + categoryId;
        var cards = Array.prototype.slice.call(root.querySelectorAll('[data-card]'));
        var validKeys = {};
        cards.forEach(function (card) { validKeys[card.dataset.key] = true; });

        var selected = [];
        (JSON.parse(root.dataset.selectedIds || '[]') || []).forEach(function (key) {
            if (validKeys[key] && selected.indexOf(key) === -1) { selected.push(key); }
        });

        // Pré-seleções salvas no navegador (RF-06: voláteis, só na sessão do dispositivo).
        try {
            (JSON.parse(window.localStorage.getItem(storageKey) || '[]') || []).forEach(function (key) {
                if (validKeys[key] && selected.indexOf(key) === -1) { selected.push(key); }
            });
        } catch (e) { /* localStorage indisponível */ }

        var form = root.querySelector('[data-julgar-form]');
        var fieldsBox = root.querySelector('[data-inscription-fields]');
        var confirmBtn = root.querySelector('[data-confirm-btn]');
        var clearBtn = root.querySelector('[data-clear-btn]');
        var countEl = root.querySelector('[data-selected-count]');
        var remainingEl = root.querySelector('[data-remaining]');
        var drawer = root.querySelector('sl-drawer[data-drawer]');
        var drawerBody = drawer ? drawer.querySelector('[data-drawer-body]') : null;
        var drawerLabel = drawer ? drawer.querySelector('[data-drawer-label]') : null;

        function persist() {
            try {
                if (selected.length) {
                    window.localStorage.setItem(storageKey, JSON.stringify(selected));
                } else {
                    window.localStorage.removeItem(storageKey);
                }
            } catch (e) { /* localStorage indisponível */ }
        }

        function paint(card) {
            var isSel = selected.indexOf(card.dataset.key) !== -1;
            card.setAttribute('aria-pressed', isSel ? 'true' : 'false');
            card.classList.toggle('border-primary', isSel);
            card.classList.toggle('bg-primary/5', isSel);
            var check = card.querySelector('[data-card-check]');
            if (check) {
                check.classList.toggle('bg-primary', isSel);
                check.classList.toggle('border-primary', isSel);
                check.classList.toggle('text-primary-foreground', isSel);
                var icon = check.querySelector('svg');
                if (icon) { icon.classList.toggle('hidden', !isSel); }
            }
        }

        function renderFields() {
            if (!fieldsBox) { return; }
            fieldsBox.innerHTML = '';
            selected.forEach(function (key, index) {
                var parts = key.split(':');
                var type = document.createElement('input');
                type.type = 'hidden';
                type.name = 'inscriptions[' + index + '][type]';
                type.value = parts[0];
                var id = document.createElement('input');
                id.type = 'hidden';
                id.name = 'inscriptions[' + index + '][id]';
                id.value = parts[1];
                fieldsBox.appendChild(type);
                fieldsBox.appendChild(id);
            });
        }

        function render() {
            cards.forEach(paint);
            if (countEl) { countEl.textContent = String(selected.length); }
            if (remainingEl) { remainingEl.textContent = String(Math.max(0, effectiveQuota - selected.length)); }
            renderFields();
            if (confirmBtn) {
                var complete = effectiveQuota > 0 && selected.length === effectiveQuota;
                confirmBtn.disabled = !complete;
                confirmBtn.title = complete
                    ? ''
                    : 'Selecione ' + Math.max(0, effectiveQuota - selected.length) + ' inscrição(ões) para confirmar.';
            }
            persist();
        }

        function addSelection(key) {
            if (selected.indexOf(key) !== -1) { return true; }
            if (selected.length >= effectiveQuota) { return false; }
            selected.push(key);
            return true;
        }

        function openDrawer(card) {
            if (!drawer || !drawerBody) { return; }
            var tpl = root.querySelector(
                'template[data-drawer-template][data-for-key="' + escapeSelector(card.dataset.key) + '"]'
            );
            if (!tpl) { return; }
            if (drawerLabel) { drawerLabel.textContent = card.dataset.label; }
            drawerBody.innerHTML = '';
            drawerBody.appendChild(tpl.content.cloneNode(true));

            var confirm = drawerBody.querySelector('[data-drawer-confirm]');
            if (confirm) {
                confirm.addEventListener('click', function () {
                    if (addSelection(card.dataset.key)) { render(); }
                    drawer.hide();
                });
            }
            drawer.show();
        }

        cards.forEach(function (card) {
            card.addEventListener('click', function (event) {
                // Clique no "chip" de seleção alterna a pré-seleção; o restante abre o drawer.
                if (event.target.closest('[data-card-check]')) {
                    var key = card.dataset.key;
                    var index = selected.indexOf(key);
                    if (index !== -1) {
                        selected.splice(index, 1);
                    } else if (!addSelection(key)) {
                        return;
                    }
                    render();
                    return;
                }
                openDrawer(card);
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                selected = [];
                render();
            });
        }

        if (drawer) {
            var closeBtn = drawer.querySelector('[data-drawer-close]');
            if (closeBtn) {
                closeBtn.addEventListener('click', function () { drawer.hide(); });
            }
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                if (selected.length !== effectiveQuota) {
                    event.preventDefault();
                }
            });
        }

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initJulgar);
    } else {
        initJulgar();
    }
})();
