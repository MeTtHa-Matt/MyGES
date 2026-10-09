<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Devoirs';
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
<title>Devoirs — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20261009-07">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu devoirs-page">
        <section class="devoirs-events-section" aria-labelledby="devoirs-events-title">
            <div class="devoirs-section-heading">
                <div>
                    <p class="devoirs-eyebrow">AGENDA ACADÉMIQUE</p>
                    <h2 id="devoirs-events-title">Prochaines évaluations</h2>
                </div>
                <div class="devoirs-count-block" aria-label="Nombre d’évaluations à venir">
                    <span class="devoirs-event-count" id="devoirs-event-count">0</span>
                    <small>à venir</small>
                </div>
            </div>
            <div class="devoirs-event-list" id="devoirs-event-list" aria-live="polite">
                <div class="etat-vide-mini">Chargement des calendriers…</div>
            </div>
        </section>
        <section class="devoirs-calendar-section" aria-labelledby="devoirs-calendar-title">
            <div class="devoirs-section-heading">
                <div>
                    <p class="devoirs-eyebrow">SOURCES CONNECTÉES</p>
                    <h2 id="devoirs-calendar-title">Mes calendriers</h2>
                </div>
                <button type="button" class="devoirs-refresh" id="devoirs-refresh" aria-label="Actualiser les calendriers" title="Actualiser">↻</button>
            </div>
            <form class="devoirs-add-form" id="devoirs-add-form">
                <label for="devoirs-calendar-url">Ajouter un calendrier Google</label>
                <div class="devoirs-add-row">
                    <input type="url" id="devoirs-calendar-url" name="calendar" placeholder="https://calendar.google.com/calendar/..." required>
                    <button type="submit" class="btn-appliquer">Ajouter</button>
                </div>
                <p class="devoirs-form-status" id="devoirs-form-status" aria-live="polite"></p>
            </form>
            <div class="devoirs-calendar-list" id="devoirs-calendar-list"></div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>

    <div class="modale-fond devoir-modal" id="devoir-modal" aria-hidden="true">
        <div class="modale-boite" role="dialog" aria-modal="true" aria-labelledby="devoir-modal-title">
            <div class="modale-entete">
                <h2 id="devoir-modal-title">Évaluation</h2>
                <button type="button" class="modale-fermer" id="devoir-modal-close" aria-label="Fermer">&times;</button>
            </div>
            <div id="devoir-modal-details"></div>
        </div>
    </div>

    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260914-8"></script>
<script src="assets/js/api.js?v=20261009-03"></script>
<script src="assets/js/storage.js?v=20260915-08"></script>
<script src="assets/js/app.js?v=20261009-03"></script>
</body>
</html>
