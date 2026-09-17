<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/** Données de présentation communes. Les données MyGES sont chargées par l'API côté navigateur. */

$aujourdhui = date('Y-m-d');

$etudiant = [
    'nom_complet' => 'Étudiant MyGES',
    'initiales'   => 'MG',
    'classe'      => '',
];

// Emploi du temps : clé = date (Y-m-d), valeur = liste de créneaux
$emploi_du_temps = [];

// Absences (vide pour la démo, comme dans la maquette d'origine)
$absences = [];

// Menu latéral
$menu = [
    [
        'label' => 'Cours',
        'sous_items' => [
            ['label' => 'Emploi du temps', 'href' => 'emploi_du_temps.php'],
            ['label' => 'Notes', 'href' => 'notes.php'],
            ['label' => 'Support de cours', 'href' => 'supports.php'],
            ['label' => 'Projets pédagogiques', 'href' => 'projets.php'],
        ],
    ],
    [
        'label' => 'Administration',
        'sous_items' => [
            ['label' => 'Notifications et messages', 'href' => 'notifications.php'],
            ['label' => 'Absences', 'href' => 'absences.php'],
            ['label' => 'Documents', 'href' => 'documents.php'],
            ['label' => 'Événements campus', 'href' => 'evenements.php'],
        ],
    ],
];
