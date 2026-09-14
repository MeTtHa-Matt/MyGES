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
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="manifest" href="manifest.json">
<title>Assiduité — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260914-29">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu">
        <div class="etat-vide">
            <p>Aucun nouvel événement</p>
            <img class="etat-vide-image" src="assets/img/image.png" alt="Aucun résultat">
        </div>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260914-8"></script>
<script src="assets/js/api.js?v=20260914-9"></script>
<script src="assets/js/storage.js?v=20260914-8"></script>
<script src="assets/js/app.js?v=20260914-10"></script>
</body>
</html>
