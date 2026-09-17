<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Profil étudiant';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#ff555a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="icon" href="assets/img/favicon.jpeg" type="image/jpeg">
<link rel="apple-touch-icon" href="assets/img/favicon.jpeg">
<link rel="manifest" href="manifest.json">
<title>Profil étudiant — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260916-03">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu profile-page">
        <section class="profile-sheet" aria-live="polite">
            <div class="profile-hero">
                <div class="avatar" id="profile-avatar">MG</div>
                <div>
                    <p class="eyebrow">Espace personnel</p>
                    <h2 id="profile-name">Chargement…</h2>
                </div>
            </div>

            <div class="profile-grid">
                <article class="profile-card">
                    <h3>Identité et parcours</h3>
                    <dl class="profile-facts">
                        <div>
                            <dt>École</dt>
                            <dd id="profile-school">—</dd>
                        </div>
                        <div>
                            <dt>Campus</dt>
                            <dd id="profile-campus">—</dd>
                        </div>
                        <div>
                            <dt>Formation</dt>
                            <dd id="profile-formation">—</dd>
                        </div>
                        <div>
                            <dt>Classe</dt>
                            <dd id="profile-classe">—</dd>
                        </div>
                        <div>
                            <dt>Année scolaire</dt>
                            <dd id="profile-annee">—</dd>
                        </div>
                    </dl>
                </article>

                <article class="profile-card">
                    <h3>Coordonnées utiles</h3>
                    <dl class="profile-facts">
                        <div>
                            <dt>Adresse</dt>
                            <dd id="profile-adresse">—</dd>
                        </div>
                        <div>
                            <dt>Ville</dt>
                            <dd id="profile-ville">—</dd>
                        </div>
                        <div>
                            <dt>Téléphone</dt>
                            <dd id="profile-telephone">—</dd>
                        </div>
                        <div>
                            <dt>E-mail</dt>
                            <dd id="profile-email">—</dd>
                        </div>
                    </dl>
                </article>

                <article class="profile-card">
                    <h3>Services associés</h3>
                    <div class="profile-links" id="profile-links">
                        <a href="emploi_du_temps.php">Planning</a>
                        <a href="notes.php">Notes</a>
                        <a href="absences.php">Absences</a>
                        <a href="documents.php">Documents</a>
                        <a href="supports.php">Supports</a>
                    </div>
                </article>

                <article class="profile-card">
                    <h3>Session et synchronisation</h3>
                    <dl class="profile-facts">
                        <div>
                            <dt>Identifiant étudiant</dt>
                            <dd id="profile-student-id">—</dd>
                        </div>
                        <div>
                            <dt>Dernière synchronisation</dt>
                            <dd id="profile-last-sync">—</dd>
                        </div>
                        <div>
                            <dt>État de session</dt>
                            <dd id="profile-session-status">Connecté</dd>
                        </div>
                    </dl>
                    <p class="profile-microcopy">La session est conservée localement et les données sont actualisées depuis MyGES lors de l’ouverture des pages.</p>
                </article>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>

<script src="assets/js/cache-reset.js?v=20260917-02"></script>
<script src="assets/js/api.js?v=20260917-02"></script>
<script src="assets/js/storage.js?v=20260917-02"></script>
<script src="assets/js/app.js?v=20260917-02"></script>
<script>
(function () {
    const values = (...items) => items.find(item => item !== undefined && item !== null && item !== '');
    const oneOf = (object, keys) => values(...keys.map(key => object?.[key]));
    const nestedValue = (source, keys) => {
        if (!source || typeof source !== 'object') return undefined;
        for (const key of keys) {
            if (Object.prototype.hasOwnProperty.call(source, key) && source[key] !== undefined && source[key] !== null && source[key] !== '') {
                return source[key];
            }
        }
        return undefined;
    };

    const formatText = value => {
        if (value === undefined || value === null || value === '') return '—';
        if (typeof value === 'string') return value.trim() || '—';
        if (typeof value === 'number') return String(value);
        if (Array.isArray(value)) return value.map(item => formatText(item)).filter(entry => entry !== '—').join(', ') || '—';
        if (typeof value === 'object') {
            const text = values(value.label, value.name, value.title, value.libelle, value.value);
            return formatText(text);
        }
        return String(value);
    };

    const formatDate = value => {
        if (!value) return '—';
        const stamp = Number(value);
        const date = Number.isFinite(stamp) ? new Date(stamp) : new Date(value);
        if (!Number.isNaN(date.getTime())) {
            return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        }
        return String(value);
    };

    const fillText = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = formatText(value);
    };

    const toAddress = data => {
        const parts = [
            data.address1,
            data.address2,
            data.zipcode,
            data.city,
            data.country,
        ].filter(Boolean);
        return parts.length ? parts.join(' ') : '—';
    };

    const getProfileSummary = async () => {
        try {
            return await window.mygesApi.profile();
        } catch (error) {
            return window.mygesStorage.readStudent?.() || {};
        }
    };

    const flattenYearNodes = value => {
        if (value === null || value === undefined) return [];
        if (Array.isArray(value)) return value.flatMap(flattenYearNodes);
        if (typeof value !== 'object') return [];
        const collection = [value];
        ['items', 'data', 'result', 'years', 'classes', 'students', 'courses', 'groups'].forEach(key => {
            if (Array.isArray(value[key])) collection.push(...value[key].flatMap(flattenYearNodes));
        });
        return collection;
    };

    const findAcademicContext = source => {
        if (!source || typeof source !== 'object') return {};
        const nodes = flattenYearNodes(source);
        const score = node => {
            const scoreMap = [
                ['school', 'school_name', 'schoolName', 'institution', 'etablissement'],
                ['campus', 'campus_name', 'campusName', 'site', 'location'],
                ['formation', 'training', 'training_name', 'trainingName', 'program', 'program_name', 'programName', 'study'],
                ['class', 'classe', 'class_name', 'className', 'promotion', 'currentClass', 'student_class'],
                ['year', 'academic_year', 'academicYear', 'school_year', 'schoolYear', 'year_label'],
            ];
            let total = 0;
            scoreMap.forEach(([primary, ...aliases]) => {
                if (oneOf(node, [primary, ...aliases])) total += 1;
            });
            return total;
        };
        const bestNode = nodes.reduce((best, node) => {
            if (!node || typeof node !== 'object') return best;
            return score(node) > score(best) ? node : best;
        }, {});
        return {
            school: oneOf(bestNode, ['school', 'school_name', 'schoolName', 'institution', 'etablissement']) || 'Réseau GES',
            campus: oneOf(bestNode, ['campus', 'campus_name', 'campusName', 'site', 'location']) || 'Campus principal',
            formation: oneOf(bestNode, ['formation', 'training', 'training_name', 'trainingName', 'program', 'program_name', 'programName', 'study']) || 'Formation non renseignée',
            className: oneOf(bestNode, ['classe', 'class', 'class_name', 'className', 'promotion', 'currentClass', 'student_class']) || 'Classe non renseignée',
            academicYear: oneOf(bestNode, ['academic_year', 'academicYear', 'year', 'school_year', 'schoolYear', 'year_label']) || '2026-2027'
        };
    };

    const renderProfile = async () => {
        const payload = await getProfileSummary();
        const data = payload?.data || payload?.result || payload || {};
        const yearsPayload = await window.mygesApi.years().catch(() => null);
        const selectedYear = Array.isArray(yearsPayload) && yearsPayload.length ? yearsPayload[0] : new Date().getFullYear();
        const classesPayload = await window.mygesApi.classes(selectedYear).catch(() => null);
        const schoolContext = findAcademicContext(classesPayload || yearsPayload || data);

        const firstName = values(
            nestedValue(data, ['firstname', 'firstName', 'givenName', 'given_name', 'prenom', 'forename']),
            data.name && String(data.name).split(' ').slice(0, 1)[0],
            'Étudiant'
        );
        const lastName = values(
            nestedValue(data, ['lastname', 'lastName', 'familyName', 'family_name', 'surname', 'surName', 'last_name', 'nom', 'nomFamille', 'nom_famille']),
            data.name && String(data.name).split(' ').slice(1).join(' '),
            ''
        );
        const name = [firstName, lastName].filter(Boolean).join(' ') || data.name || 'Étudiant MyGES';
        const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase() || 'MG';

        document.getElementById('profile-avatar').textContent = initials;
        document.getElementById('profile-name').textContent = name;

        fillText('profile-school', values(data.school, data.school_name, data.schoolName, data.institution, data.etablissement, schoolContext.school));
        fillText('profile-campus', values(data.campus, data.campus_name, data.campusName, data.site, data.location, schoolContext.campus));
        fillText('profile-formation', values(data.formation, data.training, data.program, data.study, data.program_name, data.training_name, schoolContext.formation));
        fillText('profile-classe', values(data.classe, data.class, data.class_name, data.className, data.promotion, data.currentClass, data.student_class, schoolContext.className));
        fillText('profile-annee', values(data.academic_year, data.academicYear, data.year, data.school_year, data.schoolYear, data.year_label, schoolContext.academicYear));
        fillText('profile-adresse', toAddress(data));
        fillText('profile-ville', values(data.city, `${data.zipcode || ''} ${data.city || ''}`.trim(), '—'));
        fillText('profile-telephone', values(data.telephone, data.mobile, '—'));
        fillText('profile-email', values(data.email, data.personal_mail, data.mailing, '—'));
        fillText('profile-student-id', values(data.student_id, data.studentId, data.ine, data.uid, '—'));
        fillText('profile-last-sync', formatDate(Date.now()));

        const linkList = document.getElementById('profile-links');
        if (linkList) {
            const extraLinks = [
                { href: 'emploi_du_temps.php', label: 'Planning' },
                { href: 'notes.php', label: 'Notes' },
                { href: 'absences.php', label: 'Absences' },
                { href: 'documents.php', label: 'Documents' },
                { href: 'supports.php', label: 'Supports' },
                { href: 'https://myges.fr/student/home', label: 'MyGES' }
            ];
            linkList.innerHTML = extraLinks.map(link => `<a href="${link.href}" target="${link.href.startsWith('http') ? '_blank' : '_self'}" rel="noopener noreferrer">${link.label}</a>`).join('');
        }

        const studentCache = window.mygesStorage && window.mygesStorage.readStudent ? window.mygesStorage.readStudent() : null;
        if (studentCache?.name) {
            const cachedName = studentCache.name || name;
            document.getElementById('profile-name').textContent = cachedName;
        }
    };

    document.addEventListener('DOMContentLoaded', renderProfile);
})();
</script>
</body>
</html>
