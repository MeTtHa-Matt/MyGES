<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Événements campus';
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
    <title>Événements campus — Portail Étudiant</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260917-05">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu events-page">
        <div class="events-summary">
            <div>
                <p class="eyebrow">Campus</p>
                <h2 class="events-title">Événements campus</h2>
            </div>
            <span class="events-badge" id="events-counter">0</span>
        </div>

        <section class="events-panel">
            <div class="events-panel-header">
                <h3>À venir</h3>
                <span class="muted-label">Inscription</span>
            </div>
            <div id="events-upcoming" class="events-list" aria-live="polite"></div>
        </section>

        <section class="events-panel">
            <div class="events-panel-header">
                <h3>Passés</h3>
                <span class="muted-label">Historique</span>
            </div>
            <div id="events-past" class="events-list" aria-live="polite"></div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>

<div class="events-modal" id="events-modal" aria-hidden="true">
    <div class="events-modal-card" role="dialog" aria-modal="true" aria-labelledby="events-modal-title">
        <button type="button" class="events-modal-close" id="events-modal-close" aria-label="Fermer">×</button>
        <p class="eyebrow">Détails</p>
        <h3 id="events-modal-title">Événement</h3>
        <p class="events-modal-meta" id="events-modal-meta"></p>
        <div class="events-modal-body" id="events-modal-body"></div>
    </div>
</div>

<script src="assets/js/cache-reset.js?v=20260917-03"></script>
<script src="assets/js/api.js?v=20260917-03"></script>
<script src="assets/js/storage.js?v=20260917-03"></script>
<script src="assets/js/app.js?v=20260917-03"></script>
<script>
(function () {
    const state = { items: [] };
    const countertop = document.getElementById('events-counter');
    const upcomingList = document.getElementById('events-upcoming');
    const pastList = document.getElementById('events-past');
    const modal = document.getElementById('events-modal');
    const modalTitle = document.getElementById('events-modal-title');
    const modalMeta = document.getElementById('events-modal-meta');
    const modalBody = document.getElementById('events-modal-body');

    const normalize = value => value === null || value === undefined ? '' : String(value).trim();
    const escapeHtml = value => normalize(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

    const parseFrenchDate = raw => {
        const value = normalize(raw);
        if (!value) return null;
        const match = value.match(/(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2})h(\d{2}))?/i);
        if (!match) return null;
        const [, day, month, year, hours = '00', minutes = '00'] = match;
        const date = new Date(Number(year), Number(month) - 1, Number(day), Number(hours), Number(minutes), 0);
        return Number.isNaN(date.getTime()) ? null : date;
    };

    const formatDateTime = raw => {
        const date = parseFrenchDate(raw);
        if (!date) return raw || 'Date inconnue';
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }).format(date);
    };

    const formatDate = raw => {
        const date = parseFrenchDate(raw);
        if (!date) return raw || 'Date inconnue';
        return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' }).format(date);
    };

    const sortByDate = items => [...items].sort((first, second) => {
        const firstDate = parseFrenchDate(first.eventDate) || parseFrenchDate(first.registrationStart) || new Date(0);
        const secondDate = parseFrenchDate(second.eventDate) || parseFrenchDate(second.registrationStart) || new Date(0);
        return firstDate.getTime() - secondDate.getTime();
    });

    const statusClass = item => (item.registered ? 'is-registered' : 'is-pending');
    const statusText = item => item.status || (item.registered ? 'Inscrit(e)' : 'Non inscrit(e)');

    const buildIcs = item => {
        const startDate = parseFrenchDate(item.eventDate || item.registrationStart);
        const endDate = startDate ? new Date(startDate.getTime() + 60 * 60 * 1000) : new Date();
        const escapeIcs = value => String(value ?? '').replace(/\\/g, '\\\\').replace(/;/g, '\\;').replace(/,/g, '\\,').replace(/\n/g, '\\n');
        const formatIcsDate = date => date.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}Z$/, 'Z');
        const location = escapeIcs(item.location || 'Campus');
        const description = escapeIcs([item.registrationStart && item.registrationEnd ? `Inscriptions : ${item.registrationStart} au ${item.registrationEnd}` : '', item.description || 'Événement campus'].filter(Boolean).join('\n'));
        const payload = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//MyGES//FR',
            'BEGIN:VEVENT',
            `UID:${(item.id || item.title || 'event')}-${Date.now()}@myges.local`,
            `DTSTAMP:${formatIcsDate(new Date())}`,
            `DTSTART:${formatIcsDate(startDate || new Date())}`,
            `DTEND:${formatIcsDate(endDate)}`,
            `SUMMARY:${escapeIcs(item.title || 'Événement campus')}`,
            `DESCRIPTION:${description}`,
            `LOCATION:${location}`,
            'END:VEVENT',
            'END:VCALENDAR'
        ].join('\r\n');
        const blob = new Blob([payload], { type: 'text/calendar;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${(item.title || 'evenement-campus').toLowerCase().replace(/[^a-z0-9]+/g, '-')}.ics`;
        link.click();
        URL.revokeObjectURL(link.href);
    };

    const renderEmpty = (target, label) => {
        if (!target) return;
        target.innerHTML = `<div class="event-empty">${label}</div>`;
    };

    const renderModal = detail => {
        if (!modal || !modalTitle || !modalMeta || !modalBody) return;
        const metaParts = [
            detail.eventDate ? `Le : ${formatDateTime(detail.eventDate)}` : 'Date à confirmer',
            detail.location ? `Lieu : ${detail.location}` : 'Lieu : à préciser',
            `Statut : ${statusText(detail)}`
        ];
        modalTitle.textContent = detail.title || 'Événement';
        modalMeta.innerHTML = metaParts.join(' · ');
        const registrationText = detail.registrationStart && detail.registrationEnd
            ? `Inscriptions : ${formatDate(detail.registrationStart)} au ${formatDate(detail.registrationEnd)}`
            : 'Dates d’inscription non communiquées';
        modalBody.innerHTML = `
            <p><strong>Inscription :</strong> ${escapeHtml(registrationText)}</p>
            <p><strong>Organisateur :</strong> ${escapeHtml(detail.organizer || 'Réseau GES')}</p>
            <p><strong>Statut :</strong> ${escapeHtml(statusText(detail))}</p>
            <p><strong>Description :</strong> ${escapeHtml(detail.description || 'Aucune description disponible.')}</p>
        `;
        modal.classList.add('is-visible');
        modal.setAttribute('aria-hidden', 'false');
    };

    const openModal = async item => {
        try {
            const detail = await window.mygesApi.event(item.id);
            renderModal({ ...item, ...detail });
        } catch {
            renderModal(item);
        }
    };

    const renderList = (items, target, emptyLabel) => {
        if (!target) return;
        if (!items.length) {
            renderEmpty(target, emptyLabel);
            return;
        }

        target.innerHTML = items.map(item => `
            <article class="event-card">
                <div class="event-card-head">
                    <h4>${escapeHtml(item.title || 'Événement')}</h4>
                    <span class="event-status ${statusClass(item)}">${escapeHtml(statusText(item))}</span>
                </div>
                <p class="event-meta"><strong>Le :</strong> ${escapeHtml(item.eventDate ? formatDateTime(item.eventDate) : 'Date inconnue')}</p>
                ${item.registrationStart && item.registrationEnd ? `<p class="event-meta"><strong>Inscriptions :</strong> ${escapeHtml(`${formatDate(item.registrationStart)} au ${formatDate(item.registrationEnd)}`)}</p>` : '<p class="event-meta"><strong>Inscriptions :</strong> non communiquées</p>'}
                ${item.location ? `<p class="event-meta"><strong>Lieu :</strong> ${escapeHtml(item.location)}</p>` : ''}
                <div class="event-actions">
                    <button type="button" class="event-button" data-event-id="${escapeHtml(item.id || '')}">Détails</button>
                    <button type="button" class="event-export" data-export-id="${escapeHtml(item.id || '')}">Ajouter au calendrier</button>
                </div>
            </article>
        `).join('');

        target.querySelectorAll('[data-event-id]').forEach(button => {
            button.addEventListener('click', () => {
                const eventItem = items.find(item => String(item.id) === String(button.dataset.eventId));
                if (eventItem) openModal(eventItem);
            });
        });

        target.querySelectorAll('[data-export-id]').forEach(button => {
            button.addEventListener('click', () => {
                const eventItem = items.find(item => String(item.id) === String(button.dataset.exportId));
                if (eventItem) buildIcs(eventItem);
            });
        });
    };

    const hydrateMissingDates = async () => {
        const missing = state.items.filter(item => !item.eventDate);
        if (!missing.length) return;
        const hydrated = await Promise.all(missing.map(async item => {
            try {
                const detail = await window.mygesApi.event(item.id);
                return { ...item, ...detail };
            } catch {
                return item;
            }
        }));
        state.items = state.items.map(item => {
            const detail = hydrated.find(candidate => String(candidate.id) === String(item.id));
            return detail || item;
        });
        renderEvents();
    };

    const renderEvents = () => {
        if (!countertop) return;
        const now = new Date();
        const upcoming = sortByDate(state.items.filter(item => {
            const eventDate = parseFrenchDate(item.eventDate) || parseFrenchDate(item.registrationStart) || new Date();
            return eventDate.getTime() >= now.getTime();
        }));
        const past = sortByDate(state.items.filter(item => {
            const eventDate = parseFrenchDate(item.eventDate) || parseFrenchDate(item.registrationStart) || new Date();
            return eventDate.getTime() < now.getTime();
        })).reverse();

        countertop.textContent = `${state.items.length}`;
        renderList(upcoming, upcomingList, 'Aucun événement à venir pour le moment.');
        renderList(past, pastList, 'Aucun événement passé à afficher.');
    };

    document.getElementById('events-modal-close')?.addEventListener('click', () => {
        modal?.classList.remove('is-visible');
        modal?.setAttribute('aria-hidden', 'true');
    });
    modal?.addEventListener('click', event => {
        if (event.target === modal) {
            modal.classList.remove('is-visible');
            modal.setAttribute('aria-hidden', 'true');
        }
    });

    window.mygesApi.events().then(items => {
        state.items = Array.isArray(items) ? items : [];
        renderEvents();
        hydrateMissingDates();
    }).catch(() => {
        renderEmpty(upcomingList, 'Événements campus indisponibles pour le moment.');
        renderEmpty(pastList, 'Aucun historique disponible.');
    });
})();
</script>
</body>
</html>
