<?php
/**
 * data.php — Données fictives pour la démo (front-end uniquement).
 * Aucune base de données : tout est stocké en tableaux PHP en dur.
 */

// Date de référence fixe pour la démo (indépendante de la date réelle du serveur)
$aujourdhui = '2026-09-14';

$etudiant = [
    'nom_complet' => 'MARTIN LOU',
    'initiales'   => 'ML',
    'classe'      => 'BUT HSE — 3ème année',
];

// Emploi du temps : clé = date (Y-m-d), valeur = liste de créneaux
$emploi_du_temps = [
    '2026-09-14' => [
        ['debut' => '08h15', 'fin' => '12h15', 'titre' => 'R3.02 Form. SST', 'type' => 'TP_4h', 'prof' => 'Mme MONNEREAU', 'salle' => 'A011 SST', 'couleur' => '#E0B84C'],
        ['debut' => '13h30', 'fin' => '17h30', 'titre' => 'R3.05 Sécurité Incendie', 'type' => 'TP_4h', 'prof' => 'M. PASQUEREAU — SDIS', 'salle' => 'Laboratoire Feu', 'couleur' => '#4C8FE0'],
    ],
    '2026-09-15' => [
        ['debut' => '10h15', 'fin' => '12h15', 'titre' => 'R3.01 Risque Chimique', 'type' => 'TP_2h', 'prof' => 'Mme JAILLET', 'salle' => 'A208 Informatique', 'couleur' => '#8E5CE0'],
        ['debut' => '13h00', 'fin' => '13h30', 'titre' => 'Réunion', 'type' => 'note', 'prof' => 'Amphi HSE', 'salle' => '', 'couleur' => '#E05C5C'],
        ['debut' => '13h30', 'fin' => '15h30', 'titre' => 'R3.06 Droit des Respons.', 'type' => 'TD_2h', 'prof' => 'Mme JOLY', 'salle' => 'A314', 'couleur' => '#4CA6E0'],
    ],
    '2026-09-16' => [
        ['debut' => '09h00', 'fin' => '12h00', 'titre' => 'R3.03 Ergonomie', 'type' => 'CM_3h', 'prof' => 'M. FABRE', 'salle' => 'Amphi B', 'couleur' => '#4CA6E0'],
    ],
    '2026-09-17' => [
        ['debut' => '08h30', 'fin' => '11h30', 'titre' => 'R3.04 Audit HSE', 'type' => 'TD_3h', 'prof' => 'Mme DUBOIS', 'salle' => 'A210', 'couleur' => '#8E5CE0'],
        ['debut' => '14h00', 'fin' => '17h00', 'titre' => 'R3.07 Anglais Technique', 'type' => 'TD_3h', 'prof' => 'Mr SMITH', 'salle' => 'B105', 'couleur' => '#E0B84C'],
    ],
    '2026-09-18' => [],
];

// Absences (vide pour la démo, comme dans la maquette d'origine)
$absences = [
    // Exemple de structure si besoin plus tard :
    // ['date' => '2026-09-10', 'debut' => '08h15', 'fin' => '12h15', 'motif' => 'Non justifiée', 'duree' => '4h00'],
];

// Menu latéral — 'href' = null si la page n'existe pas encore dans la démo (affiche un message)
$menu = [
    [
        'label' => 'Cours',
        'sous_items' => [
            ['label' => 'Ressources de cours', 'href' => null],
            ['label' => 'Documents partagés', 'href' => null],
            ['label' => 'Annonces', 'href' => null],
        ],
    ],
    [
        'label' => 'Gestion des étudiants',
        'sous_items' => [
            ['label' => "Statut d'absence et retard", 'href' => 'absences.php'],
            ['label' => 'Liste des interlocuteurs', 'href' => null],
            ['label' => 'Suivis', 'href' => null],
            ['label' => 'Calendrier universitaire', 'href' => null],
        ],
    ],
    [
        'label' => 'Études',
        'sous_items' => [
            ['label' => 'Notes', 'href' => null],
            ['label' => 'Relevés', 'href' => null],
            ['label' => 'Emploi du temps', 'href' => 'emploi_du_temps.php'],
        ],
    ],
    [
        'label' => 'En entreprise',
        'sous_items' => [
            ['label' => 'Conventions de stage', 'href' => null],
            ['label' => 'Suivi alternance', 'href' => null],
        ],
    ],
    [
        'label' => 'Informations et sondages',
        'sous_items' => null,
        'href' => null,
    ],
    [
        'label' => 'Informations personnelles',
        'sous_items' => [
            ['label' => 'Coordonnées', 'href' => null],
            ['label' => 'Documents administratifs', 'href' => null],
        ],
    ],
    [
        'label' => 'Changer de compte',
        'sous_items' => null,
        'href' => null,
    ],
];
