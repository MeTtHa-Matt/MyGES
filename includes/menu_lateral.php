<?php
/**
 * menu_lateral.php — panneau de navigation coulissant + rail d'icônes.
 * Utilise $menu défini dans data.php.
 */
?>
<div class="overlay-menu" id="overlay-menu"></div>

<nav class="rail-lateral">
    <button type="button" class="icon-btn" id="btn-fermer-menu-rail" aria-label="Fermer le menu"><img src="assets/img/icone-menu.svg" alt=""></button>
    <div class="separateur-rail"></div>
    <span class="icone-motif"><?= icone_motif() ?></span>
    <div class="separateur-rail separateur-rail-bas"></div>
    <button type="button" class="icon-btn icone-pouvoir" id="btn-deconnexion" aria-label="Déconnexion"><?= icone_pouvoir() ?></button>
</nav>

<aside class="panneau-menu" aria-label="Menu de navigation">
    <div class="panneau-entete">
        <div class="avatar menu-avatar"><?= icone_profil_menu() ?></div>
        <span class="nom"><?= htmlspecialchars($etudiant['nom_complet']) ?></span>
    </div>
    <ul class="panneau-liste">
        <?php foreach ($menu as $section): ?>
            <li class="panneau-item">
                <?php if (!empty($section['sous_items'])): ?>
                    <button type="button" class="panneau-item-entete" data-toggle-sousmenu>
                        <span><?= htmlspecialchars($section['label']) ?></span>
                        <?= icone_chevron_bas() ?>
                    </button>
                    <ul class="sous-menu">
                        <?php foreach ($section['sous_items'] as $sous): ?>
                            <li>
                                <?php if (!empty($sous['href'])): ?>
                                    <a href="<?= htmlspecialchars($sous['href']) ?>"><?= htmlspecialchars($sous['label']) ?></a>
                                <?php else: ?>
                                    <a href="#" data-action-placeholder="<?= htmlspecialchars($sous['label']) ?>"><?= htmlspecialchars($sous['label']) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <button type="button" class="panneau-item-entete" data-action-placeholder="<?= htmlspecialchars($section['label']) ?>" style="cursor:pointer;">
                        <span><?= htmlspecialchars($section['label']) ?></span>
                    </button>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>
