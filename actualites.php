<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Actualités';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#ff555a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="icon" href="assets/img/favicon.jpeg" type="image/jpeg">
<link rel="apple-touch-icon" href="assets/img/favicon.jpeg">
<link rel="manifest" href="manifest.json">
<title>Actualités — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260917-03">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu actualites-page">
        <div class="actualites-summary">
            <div>
                <p class="eyebrow">Mise à jour</p>
                <h2 class="actualites-title">Actualités</h2>
            </div>
            <span class="actualites-badge" id="news-unread-count">0 nouveau</span>
        </div>

        <section class="news-panel">
            <div class="news-panel-header">
                <h3>Réseau GES</h3>
                <span class="muted-label">Général</span>
            </div>
            <div id="news-general-list" class="news-list" aria-live="polite"></div>
        </section>

        <section class="news-panel">
            <div class="news-panel-header">
                <h3>École</h3>
                <span class="muted-label">ESGI</span>
            </div>
            <div id="news-school-list" class="news-list" aria-live="polite"></div>
        </section>

        <nav class="news-pagination" id="news-pagination" aria-label="Pagination des actualités"></nav>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>

<div class="news-modal" id="news-modal" aria-hidden="true">
    <div class="news-modal-card" role="dialog" aria-modal="true" aria-labelledby="news-modal-title">
        <button type="button" class="news-modal-close" id="news-modal-close" aria-label="Fermer">×</button>
        <p class="eyebrow">Article</p>
        <h3 id="news-modal-title">Titre</h3>
        <p class="news-modal-meta" id="news-modal-meta"></p>
        <div class="news-modal-body" id="news-modal-body"></div>
    </div>
</div>

<script src="assets/js/cache-reset.js?v=20260917-03"></script>
<script src="assets/js/api.js?v=20260917-03"></script>
<script src="assets/js/storage.js?v=20260917-03"></script>
<script src="assets/js/app.js?v=20260917-03"></script>
<script>
(function () {
    const pageSize = 4;
    const state = { items: [], page: 1 };
    const unreadKey = 'myges-news-unread-v1';

    const normalize = value => value === null || value === undefined ? '' : String(value).trim();

    const formatDate = raw => {
        if (!raw) return 'Date inconnue';
        const date = new Date(raw);
        if (Number.isNaN(date.getTime())) return normalize(raw);
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
    };

    const htmlToText = html => {
        const temp = document.createElement('div');
        temp.innerHTML = html || '';
        return normalize(temp.textContent || '').replace(/\s+/g, ' ');
    };

    const isSchoolNews = item => {
        const haystack = `${item.title || ''} ${item.summary || ''} ${item.body || ''}`.toLowerCase();
        return /esgi|campus|paris|grenoble|lille|toulouse|marseille|bordeaux|lyon|nantes|ecole|school/i.test(haystack);
    };

    const readSeen = () => {
        try {
            return JSON.parse(localStorage.getItem(unreadKey) || '[]');
        } catch {
            return [];
        }
    };

    const writeSeen = items => {
        localStorage.setItem(unreadKey, JSON.stringify(items));
    };

    const currentUnread = items => {
        const seen = readSeen();
        return items.filter(item => !seen.includes(item.id || item.link)).length;
    };

    const updateUnread = items => {
        const badge = document.getElementById('news-unread-count');
        const count = currentUnread(items);
        if (badge) {
            badge.textContent = count > 0 ? `${count} nouveau${count > 1 ? 'x' : ''}` : 'À jour';
        }
    };

    const renderList = (targetId, items) => {
        const container = document.getElementById(targetId);
        if (!container) return;
        if (!items.length) {
            container.innerHTML = '<div class="etat-vide-mini">Aucune actualité disponible pour le moment.</div>';
            return;
        }

        container.innerHTML = items.map(item => {
            const seen = readSeen();
            const isUnread = !seen.includes(item.id || item.link);
            return `
                <article class="news-card ${isUnread ? 'is-unread' : ''}" data-link="${(item.link || '#').replace(/"/g, '&quot;')}">
                    <div class="news-card-top">
                        <span class="news-tag">${targetId === 'news-school-list' ? 'École' : 'Réseau GES'}</span>
                        ${isUnread ? '<span class="news-dot" aria-label="Nouvel article"></span>' : ''}
                    </div>
                    <h4>${(item.title || 'Actualité').replace(/</g, '&lt;')}</h4>
                    <p class="news-meta">${formatDate(item.pubDate)}</p>
                    <p class="news-summary">${(item.summary || htmlToText(item.body || '') || 'Consulter l’article complet.').slice(0, 170)}${((item.summary || htmlToText(item.body || '') || 'Consulter l’article complet').length > 170 ? '…' : '')}</p>
                    <div class="news-actions">
                        <button type="button" class="news-read" data-link="${(item.link || '#').replace(/"/g, '&quot;')}">Lire l’article</button>
                        <a href="${item.link || '#'}" target="_blank" rel="noreferrer" class="news-link">Ouvrir</a>
                    </div>
                </article>
            `;
        }).join('');

        container.querySelectorAll('.news-read').forEach(button => {
            button.addEventListener('click', () => {
                const item = state.items.find(entry => (entry.id || entry.link) === button.dataset.link || entry.link === button.dataset.link);
                if (!item) return;
                const seen = readSeen();
                const id = item.id || item.link;
                if (id && !seen.includes(id)) {
                    seen.push(id);
                    writeSeen(seen);
                    updateUnread(state.items);
                }
                openModal(item);
            });
        });
    };

    const renderPagination = () => {
        const pagination = document.getElementById('news-pagination');
        if (!pagination || !state.items.length) return;
        const general = state.items.filter(item => !isSchoolNews(item));
        const school = state.items.filter(isSchoolNews);
        const totalPages = Math.max(1, Math.ceil(Math.max(general.length, school.length || 1) / pageSize));
        const labels = [];
        labels.push(`<button type="button" class="page-btn ${state.page === 1 ? 'is-disabled' : ''}" data-page="${state.page - 1}" ${state.page === 1 ? 'disabled' : ''}>Précédent</button>`);
        labels.push(`<span class="page-indicator">Page ${state.page}/${totalPages}</span>`);
        labels.push(`<button type="button" class="page-btn ${state.page >= totalPages ? 'is-disabled' : ''}" data-page="${state.page + 1}" ${state.page >= totalPages ? 'disabled' : ''}>Suivant</button>`);
        pagination.innerHTML = labels.join('');

        pagination.querySelectorAll('[data-page]').forEach(button => {
            button.addEventListener('click', () => {
                const nextPage = Number(button.dataset.page);
                if (!Number.isFinite(nextPage) || nextPage < 1 || nextPage > totalPages) return;
                state.page = nextPage;
                renderNews();
            });
        });
    };

    const renderNews = () => {
        const generalItems = state.items.filter(item => !isSchoolNews(item));
        const schoolItems = state.items.filter(isSchoolNews);
        const generalSlice = generalItems.slice((state.page - 1) * pageSize, state.page * pageSize);
        const schoolSlice = schoolItems.slice((state.page - 1) * pageSize, state.page * pageSize);
        renderList('news-general-list', generalSlice.length ? generalSlice : generalItems.slice(0, pageSize));
        renderList('news-school-list', schoolSlice.length ? schoolSlice : schoolItems.slice(0, pageSize));
        renderPagination();
        updateUnread(state.items);
    };

    const openModal = item => {
        const modal = document.getElementById('news-modal');
        const title = document.getElementById('news-modal-title');
        const meta = document.getElementById('news-modal-meta');
        const body = document.getElementById('news-modal-body');
        if (!modal || !title || !meta || !body) return;
        title.textContent = item.title || 'Actualité';
        meta.textContent = `${formatDate(item.pubDate)} · ${item.source || 'Réseau GES'}`;
        const html = item.body && item.body.trim() ? item.body : `<p>${(item.summary || htmlToText(item.body) || 'Aucun contenu détaillé disponible.').replace(/"/g, '&quot;')}</p>`;
        body.innerHTML = html;
        modal.classList.add('is-visible');
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = () => {
        const modal = document.getElementById('news-modal');
        if (!modal) return;
        modal.classList.remove('is-visible');
        modal.setAttribute('aria-hidden', 'true');
    };

    document.getElementById('news-modal-close')?.addEventListener('click', closeModal);
    document.getElementById('news-modal')?.addEventListener('click', event => {
        if (event.target === event.currentTarget) closeModal();
    });

    const loadNews = async () => {
        try {
            const payload = await window.mygesApi.news();
            const items = Array.isArray(payload) ? payload : (payload.items || []);
            state.items = items.map((item, index) => ({
                id: item.id || item.link || `${item.title || 'news'}-${index}`,
                title: item.title || 'Actualité',
                summary: item.summary || htmlToText(item.body || item.content || ''),
                body: item.body || item.content || '<p>Contenu indisponible.</p>',
                link: item.link || '#',
                pubDate: item.pubDate || new Date().toISOString(),
                source: item.source || 'Réseau GES'
            }));
            if (!state.items.length) {
                renderList('news-general-list', []);
                renderList('news-school-list', []);
                document.getElementById('news-pagination').innerHTML = '';
                updateUnread([]);
                return;
            }
            state.page = 1;
            renderNews();
        } catch (error) {
            document.getElementById('news-general-list').innerHTML = '<div class="etat-vide-mini">Actualités momentanément indisponibles.</div>';
            document.getElementById('news-school-list').innerHTML = '<div class="etat-vide-mini">Réessayez dans quelques instants.</div>';
            const badge = document.getElementById('news-unread-count');
            if (badge) badge.textContent = 'Indisponible';
            if (window.toast) window.toast(error.message || 'Erreur');
        }
    };

    loadNews();
})();
</script>
</body>
</html>
