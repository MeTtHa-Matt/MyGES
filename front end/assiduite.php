<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Assiduité';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assiduité — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu">
        <div class="etat-vide">
            <p>Aucun nouvel événement</p>
            <?= illustration_boite_vide() ?>
        </div>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
