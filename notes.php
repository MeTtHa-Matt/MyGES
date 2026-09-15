<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Notes';
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
<title>Notes — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260915-07">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>
    <main class="contenu notes-content">
        <div class="etat-vide"><p>Chargement des notes…</p></div>
    </main>
    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260915-07"></script>
<script src="assets/js/api.js?v=20260915-07"></script>
<script src="assets/js/storage.js?v=20260915-08"></script>
<script src="assets/js/app.js?v=20260915-09"></script>
</body>
</html>
