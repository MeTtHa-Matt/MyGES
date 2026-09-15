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
<link rel="stylesheet" href="assets/css/style.css?v=20260914-33">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu">
        <div class="barre-filtre">
            <button type="button" class="bouton-filtre" id="btn-ouvrir-filtre" aria-label="Filtrer"><?= icone_filtre() ?></button>
        </div>
        <hr class="separateur">
        <div class="etat-vide">
            <p>Aucun devoir pour les 7 prochains jours</p>
            <img class="etat-vide-image" src="assets/img/image.png" alt="Aucun résultat">
        </div>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>

    <div class="modale-fond" id="modale-filtre">
        <div class="modale-boite">
            <div class="modale-entete">
                <h2>Filtrer les devoirs</h2>
                <button type="button" class="modale-fermer" id="btn-fermer-filtre" aria-label="Fermer">&times;</button>
            </div>
            <form class="panneau-filtre" id="form-filtre">
                <label for="filtre-date-debut">Du</label>
                <input type="date" id="filtre-date-debut" name="date_debut">
                <label for="filtre-date-fin">Au</label>
                <input type="date" id="filtre-date-fin" name="date_fin">
                <div class="actions-filtre">
                    <button type="button" class="btn-reinitialiser" id="btn-reinitialiser-filtre">Réinitialiser</button>
                    <button type="submit" class="btn-appliquer">Appliquer</button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260914-8"></script>
<script src="assets/js/api.js?v=20260914-9"></script>
<script src="assets/js/storage.js?v=20260914-8"></script>
<script src="assets/js/app.js?v=20260914-10"></script>
</body>
</html>
