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
    initMediaPicker();
    initRichTextEditors();
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

/* ---- Sélecteur de médias (bouton "Parcourir" des champs image) ---- */
function initMediaPicker() {
    const dialog = document.getElementById('mediaPicker');
    if (!dialog) return;

    const grid = dialog.querySelector('#mediaPickerGrid');
    let targetInput = null;

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-media-picker]');
        if (!btn) return;
        e.preventDefault();

        targetInput = btn.closest('[data-media-field]')?.querySelector('input');
        grid.innerHTML = '<p class="col-span-full py-6 text-center text-sm text-stone-400">Chargement…</p>';
        dialog.showModal();

        try {
            const medias = await (await fetch(dialog.dataset.endpoint)).json();
            grid.innerHTML = '';

            if (!medias.length) {
                grid.innerHTML = '<p class="col-span-full py-6 text-center text-sm text-stone-400">Bibliothèque vide — importez des images via « Importer de nouvelles images ».</p>';
                return;
            }

            medias.forEach((media) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'group overflow-hidden rounded-xl border border-stone-200 text-left transition hover:border-emerald-600';

                const img = document.createElement('img');
                img.src = media.thumb;
                img.alt = media.alt;
                img.loading = 'lazy';
                img.className = 'aspect-[4/3] w-full object-cover';

                const name = document.createElement('span');
                name.textContent = media.name;
                name.className = 'block truncate px-2.5 py-2 text-[11px] font-semibold text-stone-600 group-hover:text-emerald-700';

                card.append(img, name);
                card.addEventListener('click', () => {
                    if (targetInput) {
                        targetInput.value = media.url;
                        targetInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    dialog.close();
                });
                grid.appendChild(card);
            });
        } catch {
            grid.innerHTML = '<p class="col-span-full py-6 text-center text-sm text-red-600">Erreur de chargement de la bibliothèque.</p>';
        }
    });

    dialog.querySelector('[data-close]')?.addEventListener('click', () => dialog.close());
}

/* ---- Éditeur de texte riche (bloc Texte libre, articles…) ---- */
function initRichTextEditors() {
    document.querySelectorAll('[data-rte]').forEach((wrapper) => {
        const area = wrapper.querySelector('[data-rte-area]');
        const textarea = wrapper.querySelector('textarea');
        if (!area || !textarea) return;

        area.innerHTML = textarea.value;
        const sync = () => { textarea.value = area.innerHTML; };
        area.addEventListener('input', sync);

        wrapper.querySelectorAll('[data-rte-cmd]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const [cmd, arg] = btn.dataset.rteCmd.split(':');
                area.focus();
                if (cmd === 'createLink') {
                    const url = window.prompt('URL du lien :', 'https://');
                    if (url) document.execCommand('createLink', false, url);
                } else if (cmd === 'formatBlock') {
                    document.execCommand('formatBlock', false, arg);
                } else {
                    document.execCommand(cmd, false, null);
                }
                sync();
            });
        });

        // Sécurité : synchronise juste avant l'envoi du formulaire
        textarea.form?.addEventListener('submit', sync);
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
