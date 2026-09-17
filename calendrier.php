<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Calendrier académique';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#ff555a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-touch-icon" content="assets/img/favicon.jpeg">
<link rel="icon" href="assets/img/favicon.jpeg" type="image/jpeg">
<link rel="manifest" href="manifest.json">
<title>Calendrier académique — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260917-10">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu academic-page">
        <div class="academic-heading">
            <div>
                <p class="academic-kicker">Scolarité</p>
                <h1>Calendrier académique</h1>
            </div>
        </div>

        <div class="academic-toolbar">
            <label for="academic-year">Année scolaire</label>
            <select id="academic-year" aria-label="Année scolaire">
                <option value="2026">2026-2027</option>
                <option value="2025">2025-2026</option>
            </select>
        </div>

        <p class="academic-source">Synchronisé avec le planning MyGES.</p>
        <div class="academic-status" id="academic-status" aria-live="polite">Chargement du calendrier…</div>
        <div class="academic-filters" role="tablist" aria-label="Filtrer les périodes">
            <button type="button" class="academic-filter is-active" data-filter="all">Tout</button>
            <button type="button" class="academic-filter" data-filter="courses">Cours</button>
            <button type="button" class="academic-filter" data-filter="exams">Examens</button>
            <button type="button" class="academic-filter" data-filter="holidays">Vacances</button>
            <button type="button" class="academic-filter" data-filter="start">Rentrée</button>
        </div>
        <section class="academic-list" id="academic-list" aria-live="polite"></section>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260917-03"></script>
<script src="assets/js/api.js?v=20260917-05"></script>
<script src="assets/js/storage.js?v=20260917-03"></script>
<script src="assets/js/app.js?v=20260917-03"></script>
<script>
(function () {
    const yearSelect = document.getElementById('academic-year');
    const list = document.getElementById('academic-list');
    const status = document.getElementById('academic-status');
    const filters = [...document.querySelectorAll('.academic-filter')];
    let activeFilter = 'all';
    let periods = [];

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));

    const unwrap = payload => {
        if (Array.isArray(payload)) return payload;
        if (!payload || typeof payload !== 'object') return [];
        for (const key of ['data', 'result', 'events', 'items', 'courses', 'lessons', 'planning', 'schedule']) {
            if (Array.isArray(payload[key])) return payload[key];
        }
        return Object.values(payload).filter(value => value && typeof value === 'object').flatMap(unwrap);
    };

    const text = (item, keys, fallback = '') => {
        for (const key of keys) {
            if (item && item[key] !== undefined && item[key] !== null && String(item[key]).trim()) return String(item[key]).trim();
        }
        return fallback;
    };

    const dateValue = item => {
        const keys = ['startDate', 'start_date', 'startAt', 'start_at', 'startTime', 'start_time', 'date', 'day', 'dateStart', 'date_debut'];
        for (const key of keys) {
            const value = item?.[key];
            if (typeof value === 'number') return new Date(value < 100000000000 ? value * 1000 : value);
            if (value && !Number.isNaN(new Date(value).getTime())) return new Date(value);
        }
        return null;
    };

    const formatDate = date => date ? new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }).format(date) : 'Date non communiquée';
    const categoryFor = title => {
        if (/examen|examens|partiel|rattrapage|soutenance/i.test(title)) return 'exams';
        if (/rentr[ée]e|pr[ée]-?rentr[ée]e|intégration/i.test(title)) return 'start';
        if (/vacance|fermé|férié|jour sans cours/i.test(title)) return 'holidays';
        return 'courses';
    };

    const render = () => {
        const visible = periods.filter(period => activeFilter === 'all' || period.category === activeFilter);
        if (!visible.length) {
            list.innerHTML = '<div class="academic-empty"><strong>Aucune période publiée pour ce filtre.</strong><span>MyGES ne fournit pas de date correspondante dans le planning synchronisé.</span></div>';
            return;
        }
        list.innerHTML = visible.map(period => `
            <article class="academic-card academic-${period.category}">
                <div class="academic-card-icon">${escapeHtml(period.label)}</div>
                <div class="academic-card-content">
                    <h2>${escapeHtml(period.title)}</h2>
                    <p>${escapeHtml(period.dateLabel)}</p>
                    <small>${escapeHtml(period.detail)}</small>
                </div>
            </article>
        `).join('');
    };

    const buildPeriods = items => {
        const dated = items.map(item => ({ item, date: dateValue(item) })).filter(entry => entry.date && !Number.isNaN(entry.date.getTime()));
        const special = dated.filter(entry => categoryFor(text(entry.item, ['title', 'name', 'label', 'subject', 'course', 'courseName'], 'Cours')) !== 'courses');
        const result = special.map(entry => {
            const title = text(entry.item, ['title', 'name', 'label', 'subject', 'course', 'courseName'], 'Événement académique');
            const category = categoryFor(title);
            return { category, title, dateLabel: formatDate(entry.date), detail: 'Élément synchronisé depuis le planning MyGES.', label: category === 'exams' ? 'Examen' : category === 'start' ? 'Rentrée' : 'Vacances' };
        });
        if (dated.length) {
            const dates = dated.map(entry => entry.date.getTime());
            result.unshift({ category: 'courses', title: 'Période de cours synchronisée', dateLabel: `${formatDate(new Date(Math.min(...dates)))} au ${formatDate(new Date(Math.max(...dates)))}`, detail: `${dated.length} créneau${dated.length > 1 ? 'x' : ''} trouvé${dated.length > 1 ? 's' : ''} dans le planning MyGES.`, label: 'Cours' });
        }
        return result;
    };

    const load = async () => {
        status.textContent = 'Synchronisation du planning MyGES…';
        list.innerHTML = '';
        try {
            const year = yearSelect.value;
            const payload = await window.mygesApi.planning(`${year}-09-15`);
            periods = buildPeriods(unwrap(payload));
            status.textContent = periods.length ? 'Calendrier synchronisé.' : 'Aucune donnée de planning pour cette année.';
            render();
        } catch (error) {
            status.textContent = 'Synchronisation indisponible.';
            list.innerHTML = `<div class="academic-empty"><strong>Le calendrier académique est indisponible.</strong><span>${escapeHtml(error.message || 'Réessayez plus tard.')}</span></div>`;
        }
    };

    yearSelect.addEventListener('change', load);
    filters.forEach(button => button.addEventListener('click', () => {
        activeFilter = button.dataset.filter;
        filters.forEach(filter => filter.classList.toggle('is-active', filter === button));
        render();
    }));
    load();
})();
</script>
</body>
</html>
