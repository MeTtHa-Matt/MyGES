<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Documents';
$url_blocs_2i = 'assets/img/2i.png';
$url_blocs_1i = 'assets/img/blocs%201i.png';
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
<title>Documents — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260915-04">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>
    <main class="contenu supports-content">
        <div class="supports-heading">
            <div>
                <p class="supports-kicker">Administration</p>
                <h1>Documents</h1>
            </div>
        </div>
        <div class="supports-list" id="myges-documents-list">
            <div class="etat-vide"><p>Chargement des documents MyGES…</p></div>
        </div>
    </main>
    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const list = document.getElementById('myges-documents-list');
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&#38;', '<': '&#60;', '>': '&#62;', "'": '&#039;', '"': '&#34;' }[character]));
    const encodeFile = url => btoa(url).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    const renderDocument = document => {
        const href = document.localUrl ? document.localUrl : `api/index.php?resource=document&file=${encodeURIComponent(encodeFile(document.url))}&name=${encodeURIComponent(document.title)}`;
        return `<article class="support-card"><div class="support-card-icon">${document.localUrl ? 'IMG' : 'DOC'}</div><div class="support-card-body"><h2>${escapeHtml(document.title)}</h2><p>${document.localUrl ? 'Document disponible dans le portail' : 'Document disponible dans MyGES'}</p></div><a class="support-download" href="${href}" download="${escapeHtml(document.filename || document.title)}" title="Télécharger" aria-label="Télécharger ${escapeHtml(document.title)}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14"/></svg></a></article>`;
    };
    const renderFolder = (folder, index) => `<button class="support-card documents-folder" type="button" data-folder-index="${index}"><div class="support-card-icon">DIR</div><div class="support-card-body"><h2>${escapeHtml(folder.period)}</h2><p>${folder.documents.length} document${folder.documents.length > 1 ? 's' : ''}</p></div><span class="documents-folder-toggle" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></span></button>`;
    const renderFiles = folder => `<div class="documents-files-head"><button class="documents-back" type="button" title="Retour aux dossiers" aria-label="Retour aux dossiers"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg></button><div><p class="supports-kicker">Dossier</p><h2>${escapeHtml(folder.period)}</h2></div></div><div class="supports-list">${folder.documents.map(renderDocument).join('')}</div>`;
    try {
        const response = await fetch('api/index.php?resource=documents&_fresh=' + Date.now(), { credentials: 'same-origin', cache: 'no-store' });
        const folders = await response.json();
        if (!response.ok) throw new Error(folders.error || 'Documents indisponibles');
        folders.unshift({ period: 'Blocs des matières', documents: [
            { title: 'Blocs 2i.png', filename: '2i.png', localUrl: <?= json_encode($url_blocs_2i, JSON_UNESCAPED_SLASHES) ?> },
            { title: 'Blocs 1i.png', filename: 'blocs 1i.png', localUrl: <?= json_encode($url_blocs_1i, JSON_UNESCAPED_SLASHES) ?> }
        ] });
        if (!folders.length) {
            list.innerHTML = '<div class="etat-vide"><p>Aucun document MyGES disponible.</p></div>';
            return;
        }
        list.innerHTML = `<div class="documents-slider"><section class="documents-pane documents-folders"><div class="supports-list">${folders.map(renderFolder).join('')}</div></section><section class="documents-pane documents-files">${renderFiles(folders[0])}</section></div>`;
        const slider = list.querySelector('.documents-slider');
        const filesPane = list.querySelector('.documents-files');
        list.querySelectorAll('[data-folder-index]').forEach(folderButton => folderButton.addEventListener('click', () => {
            filesPane.innerHTML = renderFiles(folders[Number(folderButton.dataset.folderIndex)]);
            slider.classList.add('show-files');
            filesPane.querySelector('.documents-back').focus();
        }));
        filesPane.addEventListener('click', event => {
            if (event.target.closest('.documents-back')) slider.classList.remove('show-files');
        });
    } catch (error) {
        list.innerHTML = `<div class="etat-vide"><p>${escapeHtml(error.message)}</p></div>`;
    }
});
</script>
<script src="assets/js/cache-reset.js?v=20260915-01"></script>
<script src="assets/js/api.js?v=20260915-04"></script>
<script src="assets/js/storage.js?v=20260915-08"></script>
<script src="assets/js/app.js?v=20260915-09"></script>
</body>
</html>
