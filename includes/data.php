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
            ['label' => 'Notes', 'href' => 'notes.php'],
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
