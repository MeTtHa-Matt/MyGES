<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Projets pédagogiques';
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
    <title>Projets pédagogiques — Portail Étudiant</title>
    <link rel="stylesheet" href="assets/css/style.css?v=20260917-08">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu project-page">
        <div class="project-heading">
            <div>
                <p class="project-kicker">Cours</p>
                <h1>Projets pédagogiques</h1>
            </div>
        </div>

        <div class="project-toolbar">
            <label class="project-year-label" for="projects-year">
                <span>Année</span>
                <select id="projects-year" class="project-year-select">
                    <option value="">Chargement…</option>
                </select>
            </label>
        </div>

        <div class="project-list" id="myges-projects-list">
            <div class="etat-vide"><p>Chargement des projets pédagogiques…</p></div>
        </div>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>

<script src="assets/js/cache-reset.js?v=20260917-03"></script>
<script src="assets/js/api.js?v=20260917-03"></script>
<script src="assets/js/storage.js?v=20260917-03"></script>
<script src="assets/js/app.js?v=20260917-03"></script>
<script>
(function () {
    const list = document.getElementById('myges-projects-list');
    const selector = document.getElementById('projects-year');
    const state = { years: [], projects: [], selectedYear: '' };

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const normalizeProjectYear = value => String(value ?? '').replace(/[^0-9-]/g, '');

    const renderYears = () => {
        if (!selector) return;
        selector.innerHTML = '';
        if (!state.years.length) {
            selector.innerHTML = '<option value="">Aucune année</option>';
            selector.disabled = true;
            return;
        }

        selector.disabled = false;
        state.years.forEach(year => {
            const option = document.createElement('option');
            option.value = year.value;
            option.textContent = year.label;
            if (String(state.selectedYear || year.value) === String(year.value)) {
                option.selected = true;
            }
            selector.appendChild(option);
        });
    };

    const renderProjects = () => {
        if (!list) return;
        if (!state.projects.length) {
            list.innerHTML = '<div class="project-empty"><p>Aucun projet pédagogique pour cette année.</p><small>Vous pouvez consulter la liste MyGES pour vérifier d’autres années ou un accès plus récent.</small></div>';
            return;
        }

        list.innerHTML = state.projects.map(project => {
            const actions = (project.actions && project.actions.length)
                ? project.actions.slice(0, 3).map(action => {
                    const url = action.url || 'https://myges.fr/student/project-list';
                    const label = action.label || 'Ouvrir';
                    return `<button type="button" class="project-action-btn project-action-${escapeHtml(action.kind || 'action')}" data-project-url="${escapeHtml(url)}">${escapeHtml(label)}</button>`;
                }).join('')
                : '<button type="button" class="project-action-btn project-action-default" data-project-url="https://myges.fr/student/project-list">Ouvrir dans MyGES</button>';

            return `
                <article class="project-card">
                    <div class="project-card-top">
                        <span class="project-badge">${escapeHtml(project.subject || 'Projet')}</span>
                        <span class="project-date">${escapeHtml(project.modified || 'Mis à jour')}</span>
                    </div>
                    <h2>${escapeHtml(project.title || 'Projet pédagogique')}</h2>
                    <div class="project-meta">
                        <p><strong>Intervenant(s)</strong> : ${escapeHtml(project.teacher || 'À préciser')}</p>
                        <p><strong>Matière</strong> : ${escapeHtml(project.subject || 'Non renseignée')}</p>
                    </div>
                    <div class="project-actions">${actions}</div>
                </article>
            `;
        }).join('');

        list.querySelectorAll('[data-project-url]').forEach(button => {
            button.addEventListener('click', () => {
                const url = button.dataset.projectUrl;
                if (url) window.open(url, '_blank', 'noopener');
            });
        });
    };

    const loadProjects = async (year = '') => {
        try {
            list.innerHTML = '<div class="etat-vide"><p>Chargement des projets pédagogiques…</p></div>';
            const payload = await window.mygesApi.projects(year || state.selectedYear || '');
            state.years = Array.isArray(payload.years) ? payload.years : [];
            state.projects = Array.isArray(payload.projects) ? payload.projects : [];
            state.selectedYear = payload.selectedYear || year || state.selectedYear || state.years[0]?.value || '';
            renderYears();
            renderProjects();
        } catch (error) {
            list.innerHTML = `<div class="project-empty"><p>Les projets pédagogiques sont indisponibles pour le moment.</p><small>${escapeHtml(error.message || 'Essayez de vous reconnecter à MyGES.')}</small></div>`;
        }
    };

    selector?.addEventListener('change', async event => {
        state.selectedYear = event.target.value;
        await loadProjects(event.target.value);
    });

    (async () => {
        await loadProjects();
    })();
})();
</script>
</body>
</html>
