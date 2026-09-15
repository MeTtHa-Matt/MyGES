<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Support de cours';
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
<link rel="manifest" href="manifest.json">
<title>Support de cours — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260915-04">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>
    <main class="contenu supports-content">
        <div class="supports-heading">
            <div>
                <p class="supports-kicker">Cours</p>
                <h1>Support de cours</h1>
            </div>
        </div>
        <div class="supports-list" id="myges-supports-list">
            <div class="etat-vide"><p>Chargement des supports de cours…</p></div>
        </div>
    </main>
    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260915-01"></script>
<script src="assets/js/api.js?v=20260915-02"></script>
<script src="assets/js/storage.js?v=20260915-08"></script>
<script src="assets/js/app.js?v=20260915-09"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('myges-supports-list');
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&#38;', '<': '&#60;', '>': '&#62;', "'": '&#039;', '"': '&#34;' }[character]));
    const encodeFile = url => btoa(url).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    const icon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>';
    const backIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>';
    const folderCard = (title, detail, index, attribute) => `<button class="support-card documents-folder" type="button" ${attribute}="${index}"><div class="support-card-icon">DIR</div><div class="support-card-body"><h2>${escapeHtml(title)}</h2><p>${escapeHtml(detail)}</p></div><span class="documents-folder-toggle" aria-hidden="true">${icon}</span></button>`;
    const fileCard = file => `<article class="support-card"><div class="support-card-icon">DOC</div><div class="support-card-body"><h2>${escapeHtml(file.title)}</h2><p>Document disponible dans MyGES</p></div><a class="support-download" href="api/index.php?resource=document&file=${encodeURIComponent(encodeFile(file.url))}&name=${encodeURIComponent(file.title)}" download title="Télécharger" aria-label="Télécharger ${escapeHtml(file.title)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14"/></svg></a></article>`;
    const header = (label, back) => `<div class="documents-files-head"><button class="documents-back" type="button" data-support-back title="Retour" aria-label="Retour">${backIcon}</button><div><p class="supports-kicker">${escapeHtml(label)}</p></div></div>`;
    try {
        const response = await fetch('api/index.php?resource=supports&_fresh=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' });
        const periods = await response.json();
        if (!response.ok) throw new Error(periods.error || 'Supports de cours indisponibles');
        if (!periods.length) { list.innerHTML = '<div class="etat-vide"><p>Aucun support de cours disponible.</p></div>'; return; }
        list.innerHTML = `<div class="documents-slider-viewport"><div class="documents-slider supports-slider"><section class="documents-pane supports-level supports-level-periods"><div class="supports-list">${periods.map((period, index) => folderCard(period.period, `${period.subjects.length} matière${period.subjects.length > 1 ? 's' : ''}`, index, 'data-period-index')).join('')}</div></section><section class="documents-pane supports-level supports-level-subjects"></section><section class="documents-pane supports-level supports-level-files"></section></div></div>`;
        const slider = list.querySelector('.supports-slider');
        const subjectsPane = list.querySelector('.supports-level-subjects');
        const filesPane = list.querySelector('.supports-level-files');
        const showSubjects = periodIndex => {
            const period = periods[periodIndex];
            subjectsPane.innerHTML = `${header(period.period)}<div class="supports-list">${period.subjects.map((subject, index) => folderCard(subject.name, `${subject.files.length} fichier${subject.files.length > 1 ? 's' : ''}`, index, 'data-subject-index')).join('')}</div>`;
            slider.dataset.periodIndex = periodIndex;
            slider.className = 'documents-slider supports-slider show-subjects';
            slider.scrollLeft = 0;
        };
        const showFiles = subjectIndex => {
            const period = periods[Number(slider.dataset.periodIndex)];
            const subject = period.subjects[subjectIndex];
            filesPane.innerHTML = `${header(subject.name)}<div class="supports-list">${subject.files.length ? subject.files.map(fileCard).join('') : '<div class="etat-vide"><p>Aucun fichier dans cette matière.</p></div>'}</div>`;
            slider.className = 'documents-slider supports-slider show-files';
            slider.scrollLeft = 0;
        };
        list.addEventListener('click', event => {
            const subjectButton = event.target.closest('[data-subject-index]');
            const periodButton = event.target.closest('button[data-period-index]');
            const backButton = event.target.closest('[data-support-back]');
            if (subjectButton) showFiles(Number(subjectButton.dataset.subjectIndex));
            else if (periodButton) showSubjects(Number(periodButton.dataset.periodIndex));
            else if (backButton) {
                slider.className = slider.classList.contains('show-files') ? 'documents-slider supports-slider show-subjects' : 'documents-slider supports-slider';
                slider.scrollLeft = 0;
            }
        });
    } catch (error) {
        list.innerHTML = `<div class="etat-vide"><p>${escapeHtml(error.message)}</p></div>`;
    }
});
</script>
</body>
</html>
