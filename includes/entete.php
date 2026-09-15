<?php
/**
 * entete.php — bandeau supérieur commun à toutes les pages internes.
 * Attend une variable $titre_page définie avant l'inclusion.
 */
?>
<header class="top-bar">
    <div class="header-slashes" aria-hidden="true">
        <span class="slash-1"></span><span class="slash-2"></span><span class="slash-3"></span><span class="slash-4"></span>
        <span class="slash-5"></span><span class="slash-6"></span><span class="slash-7"></span><span class="slash-8"></span>
        <span class="slash-9"></span><span class="slash-10"></span><span class="slash-11"></span><span class="slash-12"></span>
        <span class="slash-13"></span><span class="slash-14"></span><span class="slash-15"></span><span class="slash-16"></span>
        <span class="slash-17"></span><span class="slash-18"></span><span class="slash-19"></span><span class="slash-20"></span>
        <span class="slash-21"></span><span class="slash-22"></span><span class="slash-23"></span><span class="slash-24"></span>
        <span class="slash-25"></span><span class="slash-26"></span><span class="slash-27"></span><span class="slash-28"></span>
    </div>
    <div class="top-bar-row">
        <div class="top-bar-identity">
            <button type="button" class="avatar profile-trigger" id="btn-profil" aria-label="Ouvrir le profil"><?= htmlspecialchars($etudiant['initiales']) ?></button>
            <div class="top-bar-copy">
                <span class="top-bar-name"><?= htmlspecialchars($etudiant['nom_complet']) ?></span>
            </div>
        </div>
        <div class="top-bar-actions">
            <a href="index.php" class="icon-btn" aria-label="Accueil"><img src="assets/img/icone-maison.jpeg" alt=""></a>
            <button type="button" class="icon-btn" id="btn-ouvrir-menu" aria-label="Ouvrir le menu"><img src="assets/img/icone-menu.svg" alt=""></button>
        </div>
    </div>
    <h1 class="top-bar-title"><?= htmlspecialchars($titre_page) ?></h1>
</header>
<div class="modale-fond" id="modale-deconnexion" aria-hidden="true">
    <div class="modale-boite" role="dialog" aria-modal="true" aria-labelledby="titre-deconnexion">
        <div class="modale-entete">
            <h2 id="titre-deconnexion">Se déconnecter ?</h2>
            <button type="button" class="modale-fermer" id="btn-fermer-deconnexion" aria-label="Fermer">&times;</button>
        </div>
        <p>Voulez-vous fermer votre session MyGES&nbsp;?</p>
        <div class="actions-deconnexion">
            <button type="button" class="btn-reinitialiser" id="btn-annuler-deconnexion">Annuler</button>
            <button type="button" class="btn-appliquer" id="btn-confirmer-deconnexion">Se déconnecter</button>
        </div>
    </div>
</div>
<div class="animation-deconnexion" id="animation-deconnexion" aria-hidden="true">
    <div class="deconnexion-anneau" aria-hidden="true"></div>
    <p>Déconnexion...</p>
</div>
