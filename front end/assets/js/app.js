/* ============================================================
   Portail Étudiant — script principal (vanille JS)
   ============================================================ */
document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Toast ---------- */
    const toastEl = document.getElementById('toast');
    let toastTimer = null;
    function afficherToast(message) {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.add('visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('visible'), 2600);
    }

    /* ---------- Menu latéral ---------- */
    const appFrame = document.querySelector('.app-frame');
    const btnOuvrirMenu = document.getElementById('btn-ouvrir-menu');
    const btnFermerMenuRail = document.getElementById('btn-fermer-menu-rail');
    const overlayMenu = document.getElementById('overlay-menu');

    function ouvrirMenu() { appFrame && appFrame.classList.add('menu-ouvert'); }
    function fermerMenu() { appFrame && appFrame.classList.remove('menu-ouvert'); }

    if (btnOuvrirMenu) btnOuvrirMenu.addEventListener('click', ouvrirMenu);
    if (btnFermerMenuRail) btnFermerMenuRail.addEventListener('click', fermerMenu);
    if (overlayMenu) overlayMenu.addEventListener('click', fermerMenu);

    document.querySelectorAll('[data-toggle-sousmenu]').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.panneau-item').classList.toggle('ouvert');
        });
    });

    document.querySelectorAll('[data-action-placeholder]').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            afficherToast('« ' + this.dataset.actionPlaceholder + ' » — fonctionnalité à venir dans cette démo.');
            fermerMenu();
        });
    });

    /* ---------- Accordéon résumé (page Absences) ---------- */
    const btnToggleResume = document.getElementById('btn-toggle-resume');
    const detailsResume = document.getElementById('details-resume');
    if (btnToggleResume && detailsResume) {
        btnToggleResume.addEventListener('click', function () {
            this.classList.toggle('ouvert');
            detailsResume.classList.toggle('visible');
        });
    }

    /* ---------- Modale filtre ---------- */
    const modaleFiltre = document.getElementById('modale-filtre');
    const btnOuvrirFiltre = document.getElementById('btn-ouvrir-filtre');
    const btnFermerFiltre = document.getElementById('btn-fermer-filtre');
    const formFiltre = document.getElementById('form-filtre');
    const btnReinitialiserFiltre = document.getElementById('btn-reinitialiser-filtre');

    if (btnOuvrirFiltre && modaleFiltre) {
        btnOuvrirFiltre.addEventListener('click', () => modaleFiltre.classList.add('visible'));
    }
    if (btnFermerFiltre && modaleFiltre) {
        btnFermerFiltre.addEventListener('click', () => modaleFiltre.classList.remove('visible'));
    }
    if (modaleFiltre) {
        modaleFiltre.addEventListener('click', (e) => { if (e.target === modaleFiltre) modaleFiltre.classList.remove('visible'); });
    }
    if (btnReinitialiserFiltre && formFiltre) {
        btnReinitialiserFiltre.addEventListener('click', () => { formFiltre.reset(); afficherToast('Filtres réinitialisés.'); });
    }
    if (formFiltre) {
        formFiltre.addEventListener('submit', function (e) {
            e.preventDefault();
            afficherToast('Filtres appliqués — aucun résultat pour ces critères.');
            modaleFiltre.classList.remove('visible');
        });
    }

    /* ---------- Formulaire de connexion (non fonctionnel) ---------- */
    const formLogin = document.getElementById('form-login');
    if (formLogin) {
        formLogin.addEventListener('submit', function (e) {
            e.preventDefault();
            afficherToast("La connexion n'est pas encore disponible dans cette démo.");
        });
    }
    const lienMdpOublie = document.getElementById('lien-mdp-oublie');
    if (lienMdpOublie) {
        lienMdpOublie.addEventListener('click', function (e) {
            e.preventDefault();
            afficherToast("Fonctionnalité à venir dans cette démo.");
        });
    }

    /* ---------- Emploi du temps : sélecteur de jour ---------- */
    const modaleJour = document.getElementById('modale-jour');
    const btnOuvrirSelecteur = document.getElementById('btn-ouvrir-selecteur');
    const btnFermerModaleJour = document.getElementById('btn-fermer-modale-jour');
    const btnFermerModaleJourBas = document.getElementById('btn-fermer-modale-jour-bas');
    const grilleCalendrier = document.getElementById('grille-calendrier');
    const libelleMois = document.getElementById('libelle-mois');
    const btnMoisPrecedent = document.getElementById('mois-precedent');
    const btnMoisSuivant = document.getElementById('mois-suivant');

    if (modaleJour && grilleCalendrier && typeof DATE_AFFICHEE !== 'undefined') {
        const NOMS_MOIS = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        const NOMS_JOURS = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
        const dateAffichee = new Date(DATE_AFFICHEE + 'T00:00:00');
        const aujourdhui = new Date(AUJOURDHUI + 'T00:00:00');
        let moisCourant = dateAffichee.getMonth();
        let anneeCourante = dateAffichee.getFullYear();

        function formatDate(y, m, d) {
            return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
        }

        function construireCalendrier() {
            grilleCalendrier.innerHTML = '';
            libelleMois.textContent = NOMS_MOIS[moisCourant] + ' ' + anneeCourante;

            NOMS_JOURS.forEach(j => {
                const el = document.createElement('div');
                el.className = 'entete-jour';
                el.textContent = j;
                grilleCalendrier.appendChild(el);
            });

            const premierJourMois = new Date(anneeCourante, moisCourant, 1);
            // Lundi = 0 ... Dimanche = 6
            let decalage = premierJourMois.getDay() - 1;
            if (decalage < 0) decalage = 6;

            const nbJoursMoisCourant = new Date(anneeCourante, moisCourant + 1, 0).getDate();
            const nbJoursMoisPrecedent = new Date(anneeCourante, moisCourant, 0).getDate();

            const totalCasesAffichees = Math.ceil((decalage + nbJoursMoisCourant) / 7) * 7;

            for (let i = 0; i < totalCasesAffichees; i++) {
                const numeroCase = i - decalage + 1;
                const btnJour = document.createElement('button');
                btnJour.type = 'button';
                btnJour.className = 'jour-case';

                let jourReel, moisReel, anneeReelle;
                if (numeroCase < 1) {
                    jourReel = nbJoursMoisPrecedent + numeroCase;
                    moisReel = moisCourant - 1;
                    anneeReelle = anneeCourante;
                    if (moisReel < 0) { moisReel = 11; anneeReelle--; }
                    btnJour.classList.add('hors-mois');
                } else if (numeroCase > nbJoursMoisCourant) {
                    jourReel = numeroCase - nbJoursMoisCourant;
                    moisReel = moisCourant + 1;
                    anneeReelle = anneeCourante;
                    if (moisReel > 11) { moisReel = 0; anneeReelle++; }
                    btnJour.classList.add('hors-mois');
                } else {
                    jourReel = numeroCase;
                    moisReel = moisCourant;
                    anneeReelle = anneeCourante;
                }

                const dateCase = formatDate(anneeReelle, moisReel, jourReel);
                btnJour.textContent = jourReel;
                btnJour.dataset.date = dateCase;

                if (dateCase === AUJOURDHUI) btnJour.classList.add('aujourdhui');
                if (dateCase === DATE_AFFICHEE) btnJour.classList.add('selectionne');

                // Pas de cours le week-end dans cette démo -> case désactivée si hors-mois n'est pas nécessaire de bloquer
                btnJour.addEventListener('click', () => {
                    window.location.href = 'emploi_du_temps.php?date=' + dateCase;
                });

                grilleCalendrier.appendChild(btnJour);
            }
        }

        construireCalendrier();

        if (btnMoisPrecedent) btnMoisPrecedent.addEventListener('click', () => {
            moisCourant--;
            if (moisCourant < 0) { moisCourant = 11; anneeCourante--; }
            construireCalendrier();
        });
        if (btnMoisSuivant) btnMoisSuivant.addEventListener('click', () => {
            moisCourant++;
            if (moisCourant > 11) { moisCourant = 0; anneeCourante++; }
            construireCalendrier();
        });
    }

    function ouvrirModaleJour() { modaleJour && modaleJour.classList.add('visible'); }
    function fermerModaleJour() { modaleJour && modaleJour.classList.remove('visible'); }
    if (btnOuvrirSelecteur) btnOuvrirSelecteur.addEventListener('click', ouvrirModaleJour);
    if (btnFermerModaleJour) btnFermerModaleJour.addEventListener('click', fermerModaleJour);
    if (btnFermerModaleJourBas) btnFermerModaleJourBas.addEventListener('click', fermerModaleJour);
    if (modaleJour) modaleJour.addEventListener('click', (e) => { if (e.target === modaleJour) fermerModaleJour(); });

    /* ---------- Export iCal (fonctionnel, généré côté client) ---------- */
    const btnIcal = document.getElementById('btn-telecharger-ical');
    if (btnIcal) {
        btnIcal.addEventListener('click', function () {
            if (typeof DONNEES_EMPLOI_DU_TEMPS === 'undefined') {
                afficherToast('Aucune donnée à exporter.');
                return;
            }
            const lignes = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Portail Etudiant Demo//FR'];
            Object.keys(DONNEES_EMPLOI_DU_TEMPS).forEach(date => {
                const jour = date.replace(/-/g, '');
                DONNEES_EMPLOI_DU_TEMPS[date].forEach(c => {
                    const debut = c.debut.replace('h', '') + '00';
                    const fin = c.fin ? c.fin.replace('h', '') + '00' : debut;
                    lignes.push('BEGIN:VEVENT');
                    lignes.push('DTSTART:' + jour + 'T' + debut.padEnd(6, '0'));
                    lignes.push('DTEND:' + jour + 'T' + fin.padEnd(6, '0'));
                    lignes.push('SUMMARY:' + c.titre);
                    lignes.push('LOCATION:' + (c.salle || ''));
                    lignes.push('DESCRIPTION:' + c.prof);
                    lignes.push('END:VEVENT');
                });
            });
            lignes.push('END:VCALENDAR');

            const blob = new Blob([lignes.join('\r\n')], { type: 'text/calendar' });
            const url = URL.createObjectURL(blob);
            const lienTelechargement = document.createElement('a');
            lienTelechargement.href = url;
            lienTelechargement.download = 'emploi_du_temps.ics';
            document.body.appendChild(lienTelechargement);
            lienTelechargement.click();
            document.body.removeChild(lienTelechargement);
            URL.revokeObjectURL(url);
            afficherToast('Fichier .ics téléchargé.');
        });
    }

    /* ---------- Export PDF (impression) ---------- */
    const btnPdf = document.getElementById('btn-imprimer-pdf');
    if (btnPdf) {
        btnPdf.addEventListener('click', function () {
            window.print();
        });
    }

});
