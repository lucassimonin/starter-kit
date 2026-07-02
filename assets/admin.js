import Sortable from 'sortablejs';

/* =========================================================
   Admin CMS — drag & drop, collections, confirmations
   ========================================================= */

document.addEventListener('DOMContentLoaded', () => {
    initBlockSorting();
    initCollections();
    initConfirms();
    initToggles();
    initCopyButtons();
    initFlashes();
});

/* ---- Réorganisation des blocs (SortableJS) ---- */
function initBlockSorting() {
    const list = document.getElementById('blocks-list');
    if (!list) return;

    Sortable.create(list, {
        handle: '[data-drag-handle]',
        animation: 180,
        ghostClass: 'opacity-40',
        onEnd: async () => {
            const order = [...list.querySelectorAll('[data-block-id]')].map((el) => el.dataset.blockId);
            try {
                const response = await fetch(list.dataset.reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': list.dataset.csrf,
                    },
                    body: JSON.stringify({ order }),
                });
                if (!response.ok) throw new Error();
                toast('Ordre des blocs enregistré');
            } catch {
                toast('Erreur lors de la réorganisation', true);
            }
        },
    });
}

/* ---- Collections de formulaires (cartes, catégories…) ---- */
function initCollections() {
    document.querySelectorAll('[data-collection]').forEach((collection) => {
        let index = parseInt(collection.dataset.index || '0', 10);
        const items = collection.querySelector('[data-collection-items]');
        const addBtn = collection.querySelector('[data-collection-add]');

        addBtn?.addEventListener('click', () => {
            const html = collection.dataset.prototype.replace(/__name__/g, index++);
            const item = document.createElement('div');
            item.setAttribute('data-collection-item', '');
            item.className = 'relative rounded-lg border border-stone-200 bg-stone-50/80 p-4';
            item.innerHTML =
                '<button type="button" data-collection-remove class="absolute right-3 top-3 text-xs font-semibold text-stone-400 transition hover:text-red-600">Retirer</button>' +
                html;
            items.appendChild(item);
            collection.dataset.index = String(index);
        });
    });

    // Suppression d'un élément (délégation, couvre les éléments ajoutés)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-collection-remove]');
        if (btn) btn.closest('[data-collection-item]')?.remove();
    });
}

/* ---- Confirmations de suppression ---- */
function initConfirms() {
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-confirm]');
        if (form && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
        }
    });
}

/* ---- Ouverture/fermeture des formulaires de bloc ---- */
function initToggles() {
    document.querySelectorAll('[data-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelector(btn.dataset.toggle)?.classList.toggle('hidden');
        });
    });

    // Ouvre automatiquement le bloc ciblé par l'ancre (#bloc-12)
    if (window.location.hash.startsWith('#bloc-')) {
        document.querySelector(`#form-${window.location.hash.slice(1)}`)?.classList.remove('hidden');
        document.querySelector(window.location.hash)?.scrollIntoView({ block: 'center' });
    }
}

/* ---- Copier l'URL d'un média ---- */
function initCopyButtons() {
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = new URL(btn.dataset.copy, window.location.origin).href;
            try {
                await navigator.clipboard.writeText(url);
                toast('URL copiée dans le presse-papiers');
            } catch {
                window.prompt('Copiez cette URL :', url);
            }
        });
    });
}

/* ---- Flashs auto-masqués + toasts JS ---- */
function initFlashes() {
    document.querySelectorAll('[data-flash]').forEach((flash) => {
        setTimeout(() => {
            flash.style.transition = 'opacity 400ms';
            flash.style.opacity = '0';
            setTimeout(() => flash.remove(), 450);
        }, 4000);
    });
}

function toast(message, isError = false) {
    const container = document.getElementById('flashes') || document.body;
    const el = document.createElement('div');
    el.className = `rounded-xl px-4 py-3 text-sm font-medium text-white shadow-xl ${isError ? 'bg-red-600' : 'bg-stone-900'}`;
    el.textContent = message;
    container.appendChild(el);
    setTimeout(() => {
        el.style.transition = 'opacity 400ms';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 450);
    }, 2500);
}
