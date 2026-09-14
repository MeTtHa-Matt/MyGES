<?php
/**
 * entete.php — bandeau supérieur commun à toutes les pages internes.
 * Attend une variable $titre_page définie avant l'inclusion.
 */
?>
<header class="top-bar">
    <div class="top-bar-row">
        <div class="top-bar-identity">
            <div class="avatar"><?= htmlspecialchars($etudiant['initiales']) ?></div>
            <span class="top-bar-name"><?= htmlspecialchars($etudiant['nom_complet']) ?></span>
        </div>
        <div class="top-bar-actions">
            <a href="index.php" class="icon-btn" aria-label="Accueil"><?= icone_maison() ?></a>
            <button type="button" class="icon-btn" id="btn-ouvrir-menu" aria-label="Ouvrir le menu"><?= icone_menu_hamburger() ?></button>
        </div>
    </div>
    <h1 class="top-bar-title"><?= htmlspecialchars($titre_page) ?></h1>
</header>
