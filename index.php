<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Accueil';

// Prochains cours : on prend le jour suivant "aujourd'hui" (fin de journée simulée)
$date_demain = date('Y-m-d', strtotime($aujourdhui . ' +1 day'));
$prochains_cours = $emploi_du_temps[$date_demain] ?? [];
$prochains_cours = array_slice($prochains_cours, 0, 3);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="manifest" href="manifest.json">
<title>Accueil — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260914-29">
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu">

        <div class="section-lien-tout">
            <a href="emploi_du_temps.php" class="lien-voir-tout">Voir tout <?= icone_fleche_lien() ?></a>
        </div>
        <div class="section-accueil">
            <h2 class="section-titre">3 prochains cours</h2>
            <p class="libelle-jour">Demain</p>
            <?php if (empty($prochains_cours)): ?>
                <p class="etat-vide-mini planning-loading">Chargement de l’emploi du temps…</p>
            <?php else: ?>
                <?php foreach ($prochains_cours as $c): ?>
                    <div class="cours-item">
                        <div class="cours-horaires"><?= $c['debut'] ?><br><?= $c['fin'] ?></div>
                        <div class="cours-barre" style="background:<?= $c['couleur'] ?>"></div>
                        <div class="cours-details">
                            <div class="titre"><?= htmlspecialchars($c['titre']) ?></div>
                            <div class="meta">
                                <?php if ($c['type'] !== 'note'): ?><?= htmlspecialchars($c['type']) ?><br><?php endif; ?>
                                <?= htmlspecialchars($c['prof']) ?><?php if ($c['salle']): ?><br><?= htmlspecialchars($c['salle']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <hr class="separateur">
        <div class="section-lien-tout">
            <a href="devoirs.php" class="lien-voir-tout">Voir tout <?= icone_fleche_lien() ?></a>
        </div>
        <div class="section-accueil">
            <h2 class="section-titre">Devoirs</h2>
            <p class="etat-vide-mini">Aucun devoir pour les 7 prochains jours</p>
        </div>

        <hr class="separateur">
        <div class="section-lien-tout">
            <a href="assiduite.php" class="lien-voir-tout">Voir tout <?= icone_fleche_lien() ?></a>
        </div>
        <div class="section-accueil">
            <h2 class="section-titre">Assiduité</h2>
            <p class="etat-vide-mini">Aucun nouvel événement</p>
        </div>

    </main>

    <button type="button" class="bouton-flottant" id="btn-telecharger-ical" aria-label="Télécharger l'agenda (.ical)"><?= icone_calendrier() ?></button>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>

<script>
    const DONNEES_EMPLOI_DU_TEMPS = <?= json_encode($emploi_du_temps, JSON_UNESCAPED_UNICODE) ?>;
    const NOM_ETUDIANT = <?= json_encode($etudiant['nom_complet'], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/cache-reset.js?v=20260914-8"></script>
<script src="assets/js/api.js?v=20260914-9"></script>
<script src="assets/js/storage.js?v=20260914-8"></script>
<script src="assets/js/app.js?v=20260914-10"></script>
</body>
</html>
