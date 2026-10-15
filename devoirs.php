<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Connexions externes';
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
<title>Connexions externes — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20261015-02">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu devoirs-page">
        <section class="devoirs-calendar-section" aria-labelledby="devoirs-calendar-title">
            <div class="devoirs-section-heading">
                <div>
                    <p class="devoirs-eyebrow">SOURCES CONNECTÉES</p>
                    <h2 id="devoirs-calendar-title">Calendriers connectés</h2>
                </div>
                <button type="button" class="devoirs-refresh" id="devoirs-refresh" aria-label="Actualiser les calendriers" title="Actualiser">↻</button>
            </div>
            <form class="devoirs-add-form" id="devoirs-add-form">
                <label for="devoirs-calendar-url">Lien du calendrier Google ou Garmin Connect</label>
                <div class="devoirs-add-row">
                    <input type="url" id="devoirs-calendar-url" name="calendar" placeholder="https://connect.garmin.com/modern/calendar/export/…" required>
                </div>
                <p class="devoirs-calendar-help">Utilise un lien Google public ou le lien d’export Garmin Connect.</p>
                <label class="devoirs-type-label" for="devoirs-calendar-type">Type de calendrier</label>
                <select id="devoirs-calendar-type" name="calendarType" required>
                    <option value="" selected disabled>Choisir un type…</option>
                    <option value="devoirs">Devoirs</option>
                    <option value="garmin">Garmin</option>
                    <option value="autre">Autre</option>
                </select>
                <button type="submit" class="btn-appliquer devoirs-add-submit">Ajouter</button>
                <p class="devoirs-form-status" id="devoirs-form-status" aria-live="polite"></p>
            </form>
            <div class="devoirs-calendar-list" id="devoirs-calendar-list"></div>
            <section class="devoirs-events-section" id="devoirs-selected-events" aria-labelledby="devoirs-events-title" hidden>
                <div class="devoirs-section-heading">
                    <div>
                        <p class="devoirs-eyebrow">CALENDRIER SÉLECTIONNÉ</p>
                        <h2 id="devoirs-events-title">Événements à venir</h2>
                    </div>
                    <div class="devoirs-count-block" aria-label="Nombre d’événements à venir">
                        <span class="devoirs-event-count" id="devoirs-event-count">0</span>
                        <small>à venir</small>
                    </div>
                </div>
                <div class="devoirs-event-list" id="devoirs-event-list" aria-live="polite"></div>
            </section>
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
<script src="assets/js/api.js?v=20261011-01"></script>
<script src="assets/js/storage.js?v=20261011-01"></script>
<script src="assets/js/app.js?v=20261015-05"></script>
</body>
</html>
