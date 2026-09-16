<?php
require __DIR__ . '/includes/data.php';
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
<link rel="apple-touch-icon" href="assets/img/favicon.jpeg">
<link rel="manifest" href="manifest.json">
<title>Connexion — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260915-12">
</head>
<body>
<div class="page-login">
    <div class="carte-login">
        <div class="logo-login" role="img" aria-label="Logo MyGES"></div>
        <h1>Portail Étudiant</h1>
        <p class="sous-texte">Connectez-vous pour accéder à votre espace</p>

        <form id="form-login" autocomplete="on">
            <div class="champ-login">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" name="identifiant" placeholder="p.nom" autocomplete="username">
            </div>
            <div class="champ-login">
                <label for="mot-de-passe">Mot de passe</label>
                <div class="password-field">
                    <input type="password" id="mot-de-passe" name="mot_de_passe" placeholder="••••••••" autocomplete="current-password">
                    <button type="button" class="password-toggle" id="toggle-password" aria-label="Afficher le mot de passe" aria-pressed="false">
                        <svg class="eye-icon eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        <svg class="eye-icon eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6 0 9.5 6 9.5 6a18 18 0 0 1-3.1 3.7M6.2 6.8C3.9 8.3 2.5 12 2.5 12s3.5 6 9.5 6a10 10 0 0 0 3-.5"/></svg>
                    </button>
                </div>
            </div>
            <div class="ligne-options">
                <label><input type="checkbox" id="souvenir"> Se souvenir de moi</label>
            </div>
            <button type="submit" class="bouton-connexion">Se connecter</button>
        </form>

        <p class="note-demo" id="login-error" role="alert" hidden></p>
    </div>
    <div class="invite-identifiants" id="invite-identifiants" aria-hidden="true" hidden>
        <div class="invite-identifiants-boite" role="dialog" aria-modal="true" aria-labelledby="invite-identifiants-titre">
            <div class="invite-identifiants-icone" aria-hidden="true">&#128273;</div>
            <h2 id="invite-identifiants-titre">Retrouver votre connexion&nbsp;?</h2>
            <p>Un identifiant enregistré sur cet appareil est disponible pour MyGES.</p>
            <div class="invite-identifiants-actions">
                <button type="button" class="btn-invite-secondaire" id="refuser-identifiants">Plus tard</button>
                <button type="button" class="btn-invite-principal" id="utiliser-identifiants">Utiliser</button>
            </div>
        </div>
    </div>
    <div class="animation-connexion" id="animation-connexion" aria-hidden="true">
        <div class="connexion-sceau" aria-hidden="true"><span>✓</span></div>
        <p>Connexion réussie</p>
    </div>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260914-8"></script>
<script src="assets/js/api.js?v=20260915-04"></script>
<script src="assets/js/storage.js?v=20260915-08"></script>
<script src="assets/js/app.js?v=20260915-09"></script>
</body>
</html>
