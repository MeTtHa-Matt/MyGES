<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Statut d\'absence et retard';

$nb_absences = count($absences);
$duree_totale = '00h00';
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
<title>Absences — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260916-04">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu">

        <div class="carte-resume">
            <span class="barre-icone"><?= icone_liste() ?></span>
            <div class="carte-resume-texte">
                <div class="titre-nombre">Absences <?= $nb_absences ?></div>
                <div class="sous-titre">À justifier : 0 (<?= $duree_totale ?>)</div>
            </div>
            <button type="button" class="chevron-toggle" id="btn-toggle-resume" aria-label="Détails"><?= icone_chevron_bas() ?></button>
        </div>

        <div class="details-resume" id="details-resume">
            <p>Récapitulatif de l'année scolaire en cours :</p>
            <ul>
                <li>Absences justifiées : 0</li>
                <li>Absences non justifiées : 0</li>
                <li>Retards : 0</li>
            </ul>
        </div>

        <div class="barre-filtre">
            <label class="notes-label" for="select-absence-period">Semestre</label>
            <select id="select-absence-period" class="notes-select" aria-label="Choisir le semestre des absences"></select>
            <button type="button" class="bouton-filtre" id="btn-ouvrir-filtre" aria-label="Filtrer"><?= icone_filtre() ?></button>
        </div>
        <hr class="separateur">

        <?php if ($nb_absences === 0): ?>
            <div class="etat-vide absence-content">
                <p>Aucune absence signalée</p>
                <img class="etat-vide-image" src="assets/img/image.png" alt="Aucun résultat">
            </div>
        <?php else: ?>
            <!-- Liste des absences (non utilisée dans cette démo) -->
        <?php endif; ?>

    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>

    <!-- Modale filtre -->
    <div class="modale-fond" id="modale-filtre">
        <div class="modale-boite">
            <div class="modale-entete">
                <h2>Filtrer les absences</h2>
                <button type="button" class="modale-fermer" id="btn-fermer-filtre" aria-label="Fermer">&times;</button>
            </div>
            <form class="panneau-filtre" id="form-filtre">
                <label for="filtre-date-debut">Du</label>
                <input type="date" id="filtre-date-debut" name="date_debut">

                <label for="filtre-date-fin">Au</label>
                <input type="date" id="filtre-date-fin" name="date_fin">

                <label for="filtre-type">Type</label>
                <select id="filtre-type" name="type">
                    <option value="">Tous</option>
                    <option value="justifiee">Justifiée</option>
                    <option value="non_justifiee">Non justifiée</option>
                    <option value="retard">Retard</option>
                </select>

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
<script src="assets/js/api.js?v=20260916-01"></script>
<script src="assets/js/storage.js?v=20260915-08"></script>
<script src="assets/js/app.js?v=20260916-04"></script>
</body>
</html>
