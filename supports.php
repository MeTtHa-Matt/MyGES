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
<link rel="stylesheet" href="assets/css/style.css?v=20260915-03">
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
        <div class="supports-list">
            <article class="support-card">
                <div class="support-card-icon">COURS</div>
                <div class="support-card-body">
                    <h2>Supports de cours</h2>
                    <p>Accéder aux supports disponibles dans MyGES</p>
                </div>
                <a class="support-download" href="https://myges.fr/student/courses-files" target="_blank" rel="noopener" title="Ouvrir les supports de cours" aria-label="Ouvrir les supports de cours">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 4h6v6m0-6-9 9M19 13v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h6"/></svg>
                </a>
            </article>
        </div>
    </main>
    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260915-01"></script>
<script src="assets/js/api.js?v=20260915-02"></script>
<script src="assets/js/storage.js?v=20260915-01"></script>
<script src="assets/js/app.js?v=20260915-02"></script>
</body>
</html>
