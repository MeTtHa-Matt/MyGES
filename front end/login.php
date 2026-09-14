<?php
require __DIR__ . '/includes/data.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page-login">
    <div class="carte-login">
        <div class="logo-login">PE</div>
        <h1>Portail Étudiant</h1>
        <p class="sous-texte">Connectez-vous pour accéder à votre espace</p>

        <form id="form-login" autocomplete="off">
            <div class="champ-login">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" name="identifiant" placeholder="prenom.nom">
            </div>
            <div class="champ-login">
                <label for="mot-de-passe">Mot de passe</label>
                <input type="password" id="mot-de-passe" name="mot_de_passe" placeholder="••••••••">
            </div>
            <div class="ligne-options">
                <label><input type="checkbox" id="souvenir"> Se souvenir de moi</label>
                <a href="#" id="lien-mdp-oublie">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="bouton-connexion">Se connecter</button>
        </form>

        <p class="note-demo">
            Écran de connexion non fonctionnel pour le moment (démo front-end).
            <br><a href="index.php" style="color:#E85555;font-weight:700;">Accéder à la démo sans connexion →</a>
        </p>
    </div>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
