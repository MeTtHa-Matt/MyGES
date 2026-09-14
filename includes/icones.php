<?php
/**
 * icones.php — petites fonctions retournant du SVG inline (trait fin, style cohérent).
 */

function icone_maison() {
    return '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="m2.5 10.6 9.5-8 9.5 8v10.2a1.2 1.2 0 0 1-1.2 1.2h-5.1v-6.2H8.8V22H3.7a1.2 1.2 0 0 1-1.2-1.2V10.6Z"/><path d="M9.1 15.6h5.8V22H9.1z" fill="#ff555a"/></svg>';
}

function icone_profil_menu() {
    return '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="7.5" r="4"/><path d="M4 21c.5-4.3 3.5-6.5 8-6.5s7.5 2.2 8 6.5H4Z"/></svg>';
}

function icone_menu_hamburger() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M3 6h18"/><path d="M7 12h14"/><path d="M3 18h18"/></svg>';
}

function icone_chevron_bas() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>';
}

function icone_chevron_gauche() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
}

function icone_chevron_droite() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>';
}

function icone_fleche_lien() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7"/><path d="M9 7h8v8"/></svg>';
}

function icone_filtre() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16l-6 7.5V19l-4 2v-8.5z"/></svg>';
}

function icone_liste() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>';
}

function icone_calendrier() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M3.5 10h17"/></svg>';
}

function icone_pdf() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1z"/><path d="M14 3v5h5"/><text x="7.3" y="17" font-size="7" fill="currentColor" stroke="none" font-family="Arial" font-weight="700">PDF</text></svg>';
}

function icone_pouvoir() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v9"/><path d="M6.5 6.5a8 8 0 1 0 11 0"/></svg>';
}

function icone_motif() {
    // icone décorative "papillon" du rail latéral
    return '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M11.2 10.1C9.8 6.2 6 4 3.7 5.3c-2.5 1.4-1.2 5.3 1.4 6.2 1.9.7 4.2.2 6.1-1.4Z"/><path d="M12.8 10.1C14.2 6.2 18 4 20.3 5.3c2.5 1.4 1.2 5.3-1.4 6.2-1.9.7-4.2.2-6.1-1.4Z"/><path d="M11.3 12.4c-2.3-1.9-5.9-1.4-6.8 1.3-.8 2.5 1.7 5.3 4.3 4.6 1.8-.5 2.9-2.6 3.3-4.7Z"/><path d="M12.7 12.4c2.3-1.9 5.9-1.4 6.8 1.3.8 2.5-1.7 5.3-4.3 4.6-1.8-.5-2.9-2.6-3.3-4.7Z"/><path d="M11.2 8.3h1.6v10.6h-1.6z"/></svg>';
}

function illustration_boite_vide() {
    return '<svg viewBox="0 0 200 170" fill="none" xmlns="http://www.w3.org/2000/svg">
        <ellipse cx="100" cy="152" rx="62" ry="8" fill="#F1F1F1"/>
        <path d="M35 78 L100 55 L165 78 L165 148 L100 165 L35 148 Z" fill="#FFC93C" stroke="#5A3B1E" stroke-width="3" stroke-linejoin="round"/>
        <path d="M35 78 L100 100 L165 78" fill="none" stroke="#5A3B1E" stroke-width="3" stroke-linejoin="round"/>
        <path d="M100 100 L100 165" stroke="#5A3B1E" stroke-width="3"/>
        <path d="M35 78 L58 62 L123 62 L100 78 Z" fill="#FFFFFF" stroke="#5A3B1E" stroke-width="3" stroke-linejoin="round"/>
        <path d="M165 78 L142 62 L77 62 L100 78 Z" fill="#FFFFFF" stroke="#5A3B1E" stroke-width="3" stroke-linejoin="round"/>
        <rect x="80" y="20" width="10" height="55" rx="4" fill="#3AA35A" stroke="#5A3B1E" stroke-width="2.5" transform="rotate(-8 85 47)"/>
        <rect x="102" y="14" width="11" height="60" rx="4" fill="#FF6B6B" stroke="#5A3B1E" stroke-width="2.5" transform="rotate(6 107 44)"/>
        <circle cx="152" cy="45" r="10" fill="#FF8A8A" stroke="#5A3B1E" stroke-width="2.5"/>
        <circle cx="168" cy="55" r="7" fill="#FF8A8A" stroke="#5A3B1E" stroke-width="2.5"/>
        <path d="M148 50c6 8 3 20-5 18" stroke="#FFC93C" stroke-width="2" fill="none"/>
    </svg>';
}
