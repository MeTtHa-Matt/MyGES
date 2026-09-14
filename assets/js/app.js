document.addEventListener('DOMContentLoaded', () => {
    const toastEl = document.getElementById('toast');
    let toastTimer;
    const toast = message => {
        if (!toastEl) return;
        toastEl.textContent = message;
        toastEl.classList.add('visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('visible'), 2600);
    };

    const appFrame = document.querySelector('.app-frame');
    const openMenu = () => appFrame?.classList.add('menu-ouvert');
    const closeMenu = () => appFrame?.classList.remove('menu-ouvert');
    document.getElementById('btn-ouvrir-menu')?.addEventListener('click', openMenu);
    document.getElementById('btn-fermer-menu-rail')?.addEventListener('click', closeMenu);
    document.getElementById('overlay-menu')?.addEventListener('click', closeMenu);
    document.querySelectorAll('[data-toggle-sousmenu]').forEach(button => button.addEventListener('click', () => button.closest('.panneau-item')?.classList.toggle('ouvert')));
    document.querySelectorAll('[data-action-placeholder]').forEach(element => element.addEventListener('click', event => {
        event.preventDefault();
        toast('Cette page n’existe pas encore.');
        closeMenu();
    }));

    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));
    const text = (value, fallback = '') => {
        if (value === null || value === undefined || value === '') return fallback;
        if (typeof value === 'string' || typeof value === 'number') return String(value);
        if (Array.isArray(value)) return value.map(entry => text(entry)).filter(Boolean).join(', ') || fallback;
        return value.name || value.label || value.title || value.value || value.code || fallback;
    };
    const nested = (value, keys, depth = 0) => {
        if (!value || typeof value !== 'object' || depth > 4) return '';
        for (const key of keys) if (value[key] !== undefined && value[key] !== null && value[key] !== '') return value[key];
        for (const child of Object.values(value)) { const found = nested(child, keys, depth + 1); if (found !== '') return found; }
        return '';
    };
    const dateValue = item => {
        const value = nested(item, ['date', 'day', 'startDate', 'start_date', 'startAt', 'start_at', 'startDateTime', 'start_datetime', 'dateStart', 'date_debut', 'begin', 'start']);
        return value && typeof value === 'object' ? value.date || value.datetime || value.dateTime || value.start || value.value || '' : value;
    };
    const dateKey = item => {
        const value = dateValue(item);
        if (typeof value === 'string') {
            const dateOnly = value.match(/\b\d{4}-\d{2}-\d{2}\b/);
            if (dateOnly) return dateOnly[0];
            const frenchDate = value.match(/\b\d{1,2}[/-]\d{1,2}[/-]\d{4}\b/);
            if (frenchDate) {
                const [day, month, year] = frenchDate[0].split(/[/-]/);
                return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
            }
        }
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
    };
    const weekStartKey = dateString => {
        const date = new Date(`${dateString}T12:00:00`);
        if (Number.isNaN(date.getTime())) return dateString;
        const mondayOffset = (date.getDay() + 6) % 7;
        date.setDate(date.getDate() - mondayOffset);
        return date.toISOString().slice(0, 10);
    };
    const itemsForWeek = (items, weekStart) => {
        const start = new Date(`${weekStart}T00:00:00`);
        const end = new Date(start);
        end.setDate(end.getDate() + 7);
        return items.filter(item => {
            const key = dateKey(item);
            if (!key) return false;
            const date = new Date(`${key}T12:00:00`);
            return date >= start && date < end;
        });
    };
    const courseName = item => text(item.course || item.courseName || item.subject || item.activity || item.title || item.name, 'Cours');
    const time = item => {
        const value = nested(item, ['startTime', 'start_time', 'startAt', 'start_at', 'startDateTime', 'start_datetime', 'startDate', 'start_date', 'dateStart', 'date_debut', 'beginTime', 'begin_time', 'begin', 'from', 'start', 'time', 'hourStart', 'startHour', 'start_hour', 'heureDebut', 'heure_debut']);
        const end = nested(item, ['endTime', 'end_time', 'endAt', 'end_at', 'endDateTime', 'end_datetime', 'endDate', 'end_date', 'dateEnd', 'date_fin', 'finishTime', 'finish_time', 'ending', 'to', 'end', 'hourEnd', 'endHour', 'end_hour', 'heureFin', 'heure_fin']);
        const format = part => {
            if (!part) return 'Horaire à confirmer';
            if (typeof part === 'object') part = part.time || part.value || part.date || '';
            if (typeof part === 'number' || /^\d{10,13}$/.test(String(part))) return new Date(Number(part) < 100000000000 ? Number(part) * 1000 : Number(part)).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            const match = String(part).match(/\b(?:[01]?\d|2[0-3])[:h][0-5]\d\b/);
            return match ? match[0].replace(':', 'h') : text(part, 'Horaire à confirmer');
        };
        return end ? `${format(value)} - ${format(end)}` : format(value);
    };
    const room = item => {
        const roomValue = nested(item, ['room', 'rooms', 'roomName', 'room_name', 'roomNumber', 'room_number', 'classroom', 'classrooms', 'classroomName', 'salle', 'salles', 'salleName', 'local', 'location', 'place']);
        const buildingValue = nested(item, ['building', 'buildings', 'buildingName', 'building_name', 'buildingLabel', 'batiment', 'batiments', 'batimentName', 'site', 'campus']);
        const roomName = text(roomValue);
        const buildingName = text(buildingValue);
        if (roomName && buildingName && roomName !== buildingName) return `${roomName} · ${buildingName}`;
        return roomName || buildingName || 'Salle à confirmer';
    };
    const courseColors = ['#E85555', '#E0B84C', '#4C8FE0', '#8E5CE0', '#4CA6E0'];
    const courseColor = (item, index) => item.color || item.colour || courseColors[index % courseColors.length];
    const normalize = item => ({ title: courseName(item), time: time(item), room: room(item), teacher: text(item.teacher || item.teacherName || item.professor || item.instructor), type: text(item.type || item.courseType || item.kind), date: dateKey(item) });
    const eventMoment = (item, end = false) => {
        const value = nested(item, end
            ? ['endTime', 'end_time', 'endAt', 'end_at', 'endDateTime', 'end_datetime', 'endDate', 'end_date', 'dateEnd', 'date_fin', 'finishTime', 'finish_time', 'ending', 'to', 'end', 'hourEnd', 'endHour', 'end_hour', 'heureFin', 'heure_fin']
            : ['startTime', 'start_time', 'startAt', 'start_at', 'startDateTime', 'start_datetime', 'startDate', 'start_date', 'dateStart', 'date_debut', 'beginTime', 'begin_time', 'begin', 'from', 'start', 'time', 'hourStart', 'startHour', 'start_hour', 'heureDebut', 'heure_debut']);
        const rawValue = value && typeof value === 'object' ? value.time || value.value || value.date || value.datetime || '' : value;
        const day = dateKey(item);
        if (!day && !rawValue) return null;
        if (typeof rawValue === 'number' || /^\d{10,13}$/.test(String(rawValue))) return new Date(Number(rawValue) < 100000000000 ? Number(rawValue) * 1000 : Number(rawValue));
        const directDate = new Date(rawValue);
        if (!Number.isNaN(directDate.getTime()) && /T|\d{1,2}[:h]\d{2}/.test(String(rawValue))) return directDate;
        const clock = String(rawValue || '').match(/\b([01]?\d|2[0-3])[:h]([0-5]\d)\b/);
        if (day && clock) return new Date(`${day}T${String(clock[1]).padStart(2, '0')}:${clock[2]}:00`);
        return day ? new Date(`${day}T23:59:59`) : null;
    };
    const unwrap = payload => {
        if (Array.isArray(payload)) return payload;
        if (!payload || typeof payload !== 'object') return [];
        for (const key of ['data', 'result', 'events', 'items', 'courses', 'lessons', 'subjects', 'matieres', 'matières', 'planning', 'schedule']) {
            if (Array.isArray(payload[key])) return payload[key];
            if (payload[key] && typeof payload[key] === 'object') {
                const nestedItems = unwrap(payload[key]);
                if (nestedItems.length) return nestedItems;
            }
        }
        const objectValues = Object.values(payload);
        if (objectValues.length && objectValues.every(value => value && typeof value === 'object' && !Array.isArray(value))) return objectValues;
        return [];
    };

    function updateStudent(profile) {
        const user = profile?.data || profile?.result || profile || {};
        const findProfileValue = keys => nested(user, keys);
        const firstName = findProfileValue(['firstname', 'firstName', 'givenName', 'given_name', 'prenom', 'forename']);
        const profileName = typeof user.name === 'string' ? user.name.trim() : '';
        const lastName = findProfileValue(['lastname', 'lastName', 'familyName', 'family_name', 'surname', 'surName', 'last_name', 'nom', 'nomFamille', 'nom_famille']) || (profileName && profileName.toLowerCase() !== String(firstName || '').toLowerCase() ? profileName : '');
        const displayName = findProfileValue(['fullName', 'full_name', 'displayName', 'display_name']);
        const name = [firstName, lastName].filter(Boolean).join(' ') || displayName || user.name || user.username || 'Étudiant MyGES';
        const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase() || 'MG';
        document.querySelectorAll('.top-bar-name, .panneau-entete .nom').forEach(element => element.textContent = name);
        document.querySelectorAll('.avatar').forEach(element => element.textContent = initials);
    }

    async function loadProfile() {
        try { updateStudent(await window.mygesApi.profile()); }
        catch (error) { if (error.status === 401) { window.mygesStorage.clearSession(); window.location.href = 'login.php'; } }
    }

    async function loadResource(resource, render) {
        const selectedDate = typeof DATE_AFFICHEE !== 'undefined' ? DATE_AFFICHEE : new Date().toISOString().slice(0, 10);
        const weekStart = resource === 'planning' ? weekStartKey(selectedDate) : '';
        const cached = resource === 'planning' ? window.mygesStorage.readPlanningWeek(weekStart) : null;
        if (cached?.value?.length) render(cached.value);
        try {
            const fetchFresh = async attempts => {
                const value = unwrap(await window.mygesApi[resource]());
                if (resource === 'planning' && value.length === 0 && attempts > 0) {
                    await new Promise(resolve => setTimeout(resolve, 700));
                    return fetchFresh(attempts - 1);
                }
                return value;
            };
            const value = await fetchFresh(2);
            if (resource === 'planning') window.mygesStorage.savePlanningWeek(weekStart, itemsForWeek(value, weekStart));
            render(value);
        } catch (error) {
            if (error.status === 401) { window.mygesStorage.clearSession(); window.location.href = 'login.php'; return; }
            if (cached?.value?.length) return;
            toast(error.message);
        }
    }

    function renderPlanning(items) {
        const content = document.querySelector('.contenu');
        if (!content) return;
        const selected = typeof DATE_AFFICHEE !== 'undefined' ? DATE_AFFICHEE : new Date().toISOString().slice(0, 10);
        const datedItems = items.filter(item => dateKey(item));
        const visible = datedItems.length
            ? datedItems.filter(item => dateKey(item) === selected)
            : [];
        const rows = visible.map((item, index) => {
            const entry = normalize(item);
            return `<div class="creneau"><div class="creneau-heure">${escapeHtml(entry.time)}</div><div class="creneau-barre" style="background:${escapeHtml(courseColor(item, index))}"></div><div class="creneau-corps"><div class="titre">${escapeHtml(entry.title)}</div><div class="meta">${escapeHtml(entry.type)}${entry.teacher ? `<br>${escapeHtml(entry.teacher)}` : ''}<br>${escapeHtml(entry.room)}</div></div></div>`;
        }).join('');
        const schedule = content.querySelector('.nav-jour')?.nextElementSibling;
        content.querySelectorAll('.creneau, .creneau-vide, .planning-empty').forEach(element => element.remove());
        schedule?.insertAdjacentHTML('afterend', rows || '<div class="etat-vide planning-empty"><p>Pas de cours ce jour</p><img class="etat-vide-image" src="assets/img/image.png" alt="Aucun cours ce jour"></div>');
        window.DONNEES_EMPLOI_DU_TEMPS = items.reduce((all, item) => { const key = dateKey(item); if (key) (all[key] ||= []).push(item); return all; }, {});
        setupCalendar(items);
    }

    function renderHome(items) {
        const section = document.querySelector('.section-accueil');
        if (!section) return;
        const now = new Date();
        const upcoming = items.filter(item => {
            const finish = eventMoment(item, true);
            return !finish || finish >= now;
        });
        const next = upcoming.sort((a, b) => (eventMoment(a)?.getTime() || Number.MAX_SAFE_INTEGER) - (eventMoment(b)?.getTime() || Number.MAX_SAFE_INTEGER)).slice(0, 3);
        section.innerHTML = `<h2 class="section-titre">3 prochains cours</h2>${next.length ? next.map((item, index) => { const entry = normalize(item); return `<div class="cours-item"><div class="cours-horaires">${escapeHtml(entry.time)}</div><div class="cours-barre" style="background:${escapeHtml(courseColor(item, index))}"></div><div class="cours-details"><div class="titre">${escapeHtml(entry.title)}</div><div class="meta">${escapeHtml(entry.type || 'Cours')}<br>${escapeHtml(entry.teacher || 'Intervenant à confirmer')}<br>${escapeHtml(entry.room)}</div></div></div>`; }).join('') : '<p class="etat-vide-mini">Aucun cours à venir.</p>'}`;
        window.DONNEES_EMPLOI_DU_TEMPS = items.reduce((all, item) => { const key = dateKey(item); if (key) (all[key] ||= []).push(item); return all; }, {});
    }

    function renderAbsences(items) {
        const summary = document.querySelector('.carte-resume');
        if (!summary) return;
        const justified = items.filter(item => item.justified || String(item.status).toLowerCase().includes('just')).length;
        summary.querySelector('.titre-nombre').textContent = `Absences ${items.length}`;
        summary.querySelector('.sous-titre').textContent = `À justifier : ${items.length - justified}`;
        const empty = document.querySelector('.etat-vide');
        if (empty && items.length) empty.innerHTML = `<div class="absence-list">${items.map(item => `<article class="absence-card"><h3>${escapeHtml(courseName(item))}</h3><p class="meta">${escapeHtml(text(item.date || item.day, 'Date inconnue'))} · ${escapeHtml(text(item.status || (item.justified ? 'Justifiée' : 'À justifier')))}</p></article>`).join('')}</div>`;
        if (items.length === 0 && empty) empty.querySelector('p').textContent = 'Aucune absence signalée';
    }

    function renderGrades(items) {
        const content = document.querySelector('.notes-content');
        if (!content) return;
        if (!items.length) { content.innerHTML = '<div class="etat-vide"><p>Aucune note disponible pour le moment</p><img class="etat-vide-image" src="assets/img/image.png" alt="Aucun résultat"></div>'; return; }
        const evaluations = items.flatMap(item => {
            const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
            const rawEvaluations = item.evaluations || item.assessments || item.notes || item.grades;
            const nestedGrades = Array.isArray(rawEvaluations) && rawEvaluations.length ? rawEvaluations : [item];
            return nestedGrades.map((grade, index) => {
                const value = grade && typeof grade === 'object' ? grade.value ?? grade.grade ?? grade.note ?? grade.score : grade;
                const label = grade && typeof grade === 'object' ? text(grade.label || grade.assessment || grade.evaluation || grade.exam || grade.type || grade.name, value === null || value === undefined || value === '' ? 'Aucune évaluation' : `Évaluation ${index + 1}`) : `Évaluation ${index + 1}`;
                const details = `${label} ${subject}`.toLowerCase();
                return { subject, value, label, isPartial: /partiel|examen|exam|final/.test(details) };
            });
        });
        const values = evaluations.map(item => Number(item.value)).filter(Number.isFinite);
        const average = values.length ? (values.reduce((sum, value) => sum + value, 0) / values.length).toFixed(1) : '—';
        const subjects = [...new Map(evaluations.map(item => [item.subject, evaluations.filter(note => note.subject === item.subject)])).entries()];
        content.innerHTML = `<section class="notes-overview"><p class="notes-kicker">Semestre en cours</p><strong>${escapeHtml(average)} <span>/ 20</span></strong><p>Moyenne générale provisoire · ${subjects.length} matière${subjects.length > 1 ? 's' : ''}</p></section><div class="notes-list">${subjects.map(([subject, notes]) => {
            const subjectValues = notes.map(note => Number(note.value)).filter(Number.isFinite);
            const subjectAverage = subjectValues.length ? (subjectValues.reduce((sum, value) => sum + value, 0) / subjectValues.length).toFixed(1) : '—';
            return `<article class="matiere-card"><div class="matiere-head"><h2>${escapeHtml(subject)}</h2><strong>${escapeHtml(subjectAverage)}<small>/20</small></strong></div><div class="evaluations">${notes.map(note => `<div class="evaluation-row${note.value === null || note.value === undefined || note.value === '' ? ' is-empty' : ''}"><span class="evaluation-label">${escapeHtml(note.label)}${note.isPartial ? '<em>Partiel</em>' : ''}</span><strong>${escapeHtml(note.value ?? '—')}</strong></div>`).join('')}</div></article>`;
        }).join('')}</div>`;
    }

    function setupCalendar(items) {
        const grid = document.getElementById('grille-calendrier');
        if (!grid || typeof DATE_AFFICHEE === 'undefined') return;
        const monthLabel = document.getElementById('libelle-mois');
        let month = new Date(`${DATE_AFFICHEE}T00:00:00`).getMonth();
        let year = new Date(`${DATE_AFFICHEE}T00:00:00`).getFullYear();
        const draw = () => {
            grid.innerHTML = '';
            monthLabel.textContent = new Date(year, month, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
            ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].forEach(day => { const label = document.createElement('div'); label.className = 'entete-jour'; label.textContent = day; grid.appendChild(label); });
            const offset = (new Date(year, month, 1).getDay() + 6) % 7;
            const total = Math.ceil((offset + new Date(year, month + 1, 0).getDate()) / 7) * 7;
            for (let index = 0; index < total; index++) {
                const date = new Date(year, month, index - offset + 1);
                const value = date.toISOString().slice(0, 10);
                const button = document.createElement('button'); button.type = 'button'; button.className = 'jour-case'; button.textContent = date.getDate(); button.dataset.date = value;
                if (value.slice(0, 7) !== `${year}-${String(month + 1).padStart(2, '0')}`) button.classList.add('hors-mois');
                if (value === DATE_AFFICHEE) button.classList.add('selectionne');
                if (items.some(item => dateKey(item) === value)) button.classList.add('aujourdhui');
                button.addEventListener('click', () => { window.location.href = `emploi_du_temps.php?date=${value}`; });
                grid.appendChild(button);
            }
        };
        draw();
        document.getElementById('mois-precedent')?.addEventListener('click', () => { month--; if (month < 0) { month = 11; year--; } draw(); });
        document.getElementById('mois-suivant')?.addEventListener('click', () => { month++; if (month > 11) { month = 0; year++; } draw(); });
    }

    function exportIcal() {
        const data = window.DONNEES_EMPLOI_DU_TEMPS || {};
        const lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//MyGES//FR'];
        Object.entries(data).forEach(([date, events]) => events.forEach(item => { const entry = normalize(item); lines.push('BEGIN:VEVENT', `DTSTART;VALUE=DATE:${date.replace(/-/g, '')}`, `SUMMARY:${entry.title}`, `LOCATION:${entry.room}`, `DESCRIPTION:${entry.teacher}`, 'END:VEVENT'); }));
        lines.push('END:VCALENDAR');
        const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([lines.join('\r\n')], { type: 'text/calendar' })); link.download = 'emploi_du_temps.ics'; link.click();
    }

    document.getElementById('btn-telecharger-ical')?.addEventListener('click', exportIcal);
    document.getElementById('btn-imprimer-pdf')?.addEventListener('click', () => window.print());
    document.getElementById('btn-ouvrir-selecteur')?.addEventListener('click', () => document.getElementById('modale-jour')?.classList.add('visible'));
    document.querySelectorAll('#btn-fermer-modale-jour, #btn-fermer-modale-jour-bas').forEach(button => button.addEventListener('click', () => document.getElementById('modale-jour')?.classList.remove('visible')));

    const logoutModal = document.getElementById('modale-deconnexion');
    const closeLogoutModal = () => {
        logoutModal?.classList.remove('visible');
        logoutModal?.setAttribute('aria-hidden', 'true');
    };
    const openLogoutModal = () => {
        logoutModal?.classList.add('visible');
        logoutModal?.setAttribute('aria-hidden', 'false');
    };
    document.getElementById('btn-profil')?.addEventListener('click', openLogoutModal);
    document.getElementById('btn-fermer-deconnexion')?.addEventListener('click', closeLogoutModal);
    document.getElementById('btn-annuler-deconnexion')?.addEventListener('click', closeLogoutModal);
    logoutModal?.addEventListener('click', event => { if (event.target === logoutModal) closeLogoutModal(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && logoutModal?.classList.contains('visible')) closeLogoutModal(); });

    const login = document.getElementById('form-login');
    const passwordInput = document.getElementById('mot-de-passe');
    const passwordToggle = document.getElementById('toggle-password');
    passwordToggle?.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'text';
        passwordInput.type = isVisible ? 'password' : 'text';
        passwordToggle.classList.toggle('is-visible', !isVisible);
        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
        passwordToggle.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    });
    if (login) login.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = login.querySelector('button');
        const error = document.getElementById('login-error');
        submit.disabled = true;
        try {
            await window.mygesApi.login({ username: login.identifiant.value, password: login.mot_de_passe.value });
            window.mygesStorage.markSession();
            window.location.href = 'index.php';
        } catch (requestError) {
            if (error) { error.textContent = requestError.message; error.hidden = false; }
        } finally { submit.disabled = false; }
    });

    const isLogin = Boolean(login);
    if (!isLogin && !window.mygesStorage.hasSession()) { window.location.href = 'login.php'; return; }
    if (!isLogin) {
        loadProfile();
        if (document.querySelector('.page-login')) return;
        if (document.querySelector('.section-accueil')) loadResource('planning', renderHome);
        if (document.querySelector('.nav-jour')) loadResource('planning', renderPlanning);
        if (document.querySelector('.carte-resume')) loadResource('absences', renderAbsences);
        if (document.querySelector('.notes-content')) loadResource('grades', renderGrades);
        window.addEventListener('pageshow', event => {
            if (event.persisted && document.querySelector('.nav-jour')) loadResource('planning', renderPlanning);
            if (event.persisted && document.querySelector('.section-accueil')) loadResource('planning', renderHome);
        });
    }
    const logout = async () => { await window.mygesApi.logout().catch(() => {}); window.mygesStorage.clearSession(); window.location.href = 'login.php'; };
    document.getElementById('btn-confirmer-deconnexion')?.addEventListener('click', logout);
    document.getElementById('btn-deconnexion')?.addEventListener('click', openLogoutModal);
});
