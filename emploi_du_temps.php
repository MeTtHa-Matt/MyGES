<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Emploi du temps';

$date_affichee = $_GET['date'] ?? $aujourdhui;
// validation simple du format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_affichee)) {
    $date_affichee = $aujourdhui;
}
$ts = strtotime($date_affichee);
$date_precedente = date('Y-m-d', strtotime('-1 day', $ts));
$date_suivante   = date('Y-m-d', strtotime('+1 day', $ts));

$jours_semaine_fr = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
$mois_fr = ['','janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];

$date_courante = new DateTimeImmutable($date_affichee . ' 00:00:00');
$annee_scolaire = (int) $date_courante->format('n') >= 9
    ? (int) $date_courante->format('Y')
    : (int) $date_courante->format('Y') - 1;
$debut_annee_scolaire = new DateTimeImmutable($annee_scolaire . '-09-01 00:00:00');
if ((int) $debut_annee_scolaire->format('N') !== 1) {
    $debut_annee_scolaire = $debut_annee_scolaire->modify('next monday');
}
$numero_semaine = max(1, (int) floor(($date_courante->getTimestamp() - $debut_annee_scolaire->getTimestamp()) / (7 * 86400)) + 1);
$libelle_bouton = ($date_affichee === $aujourdhui) ? "Aujourd'hui" : $jours_semaine_fr[(int)date('N', $ts) - 1] . ' ' . (int)date('j', $ts) . ' ' . $mois_fr[(int)date('n', $ts)];

$cours_du_jour = $emploi_du_temps[$date_affichee] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="manifest" href="manifest.json">
<title>Emploi du temps — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260914-29">
<style media="print">
    .top-bar-actions, .rail-lateral, .panneau-menu, .overlay-menu, .actions-flottantes, .nav-jour button { display: none !important; }
</style>
</head>
<body>
<div class="app-frame">

    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu">

        <div class="nav-jour">
            <a href="?date=<?= $date_precedente ?>" aria-label="Jour précédent"><?= icone_chevron_gauche() ?></a>
            <button type="button" class="libelle-date-btn" id="btn-ouvrir-selecteur"><?= htmlspecialchars($libelle_bouton) ?></button>
            <a href="?date=<?= $date_suivante ?>" aria-label="Jour suivant"><?= icone_chevron_droite() ?></a>
        </div>

        <p class="libelle-semaine">Semaine <?= $numero_semaine ?></p>

        <?php if (empty($cours_du_jour)): ?>
            <div class="etat-vide planning-empty planning-loading">
                <p>Chargement de l’emploi du temps…</p>
            </div>
        <?php else: ?>
            <?php foreach ($cours_du_jour as $c): ?>
                <div class="creneau">
                    <div class="creneau-heure"><?= $c['debut'] ?><?php if ($c['type'] !== 'note'): ?><br><?= $c['fin'] ?><?php endif; ?></div>
                    <div class="creneau-barre" style="background:<?= $c['couleur'] ?>"></div>
                    <div class="creneau-corps">
                        <div class="titre"><?= htmlspecialchars($c['titre']) ?></div>
                        <div class="meta">
                            <?php if ($c['type'] !== 'note'): ?><?= htmlspecialchars($c['type']) ?><br><?php endif; ?>
                            <?= htmlspecialchars($c['prof']) ?><?php if ($c['salle']): ?><br><?= htmlspecialchars($c['salle']) ?><?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </main>

    <div class="actions-flottantes">
        <button type="button" class="action-ronde ical" id="btn-telecharger-ical" aria-label="Exporter en .ical"><?= icone_calendrier() ?></button>
        <button type="button" class="action-ronde pdf" id="btn-imprimer-pdf" aria-label="Exporter en PDF"><?= icone_pdf() ?></button>
    </div>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>

    <!-- Modale sélection de jour -->
    <div class="modale-fond" id="modale-jour">
        <div class="modale-boite">
            <div class="modale-entete">
                <h2>Sélectionner un jour</h2>
                <button type="button" class="modale-fermer" id="btn-fermer-modale-jour" aria-label="Fermer">&times;</button>
            </div>
            <div class="nav-mois">
                <button type="button" id="mois-precedent" aria-label="Mois précédent"><?= icone_chevron_gauche() ?></button>
                <span id="libelle-mois"></span>
                <button type="button" id="mois-suivant" aria-label="Mois suivant"><?= icone_chevron_droite() ?></button>
            </div>
            <div class="grille-calendrier" id="grille-calendrier"></div>
            <button type="button" class="bouton-fermer-modale" id="btn-fermer-modale-jour-bas">Fermer</button>
        </div>
    </div>

    <div class="toast" id="toast"></div>
</div>

<script>
    const DATE_AFFICHEE = <?= json_encode($date_affichee) ?>;
    const AUJOURDHUI = <?= json_encode($aujourdhui) ?>;
    const DONNEES_EMPLOI_DU_TEMPS = <?= json_encode($emploi_du_temps, JSON_UNESCAPED_UNICODE) ?>;
    const NOM_ETUDIANT = <?= json_encode($etudiant['nom_complet'], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/cache-reset.js?v=20260914-8"></script>
<script src="assets/js/api.js?v=20260914-9"></script>
<script src="assets/js/storage.js?v=20260914-8"></script>
<script src="assets/js/app.js?v=20260914-10"></script>
</body>
</html>
