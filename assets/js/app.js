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
    if (sessionStorage.getItem('sans-transition-page')) {
        appFrame?.classList.add('sans-transition');
        sessionStorage.removeItem('sans-transition-page');
    }
    const openMenu = () => appFrame?.classList.add('menu-ouvert');
    const closeMenu = () => appFrame?.classList.remove('menu-ouvert');
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || link.target === '_blank' || link.hasAttribute('download') || link.dataset.actionPlaceholder !== undefined) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (link.closest('.nav-jour')) {
            sessionStorage.setItem('sans-transition-page', '1');
            return;
        }
        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin || destination.pathname === window.location.pathname && destination.search === window.location.search) return;
        event.preventDefault();
        appFrame?.classList.add('page-sortie');
        window.setTimeout(() => { window.location.href = link.href; }, 280);
    });
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

        for (const key of ['data', 'result', 'events', 'items', 'courses', 'lessons', 'subjects', 'matieres', 'matières', 'planning', 'schedule', 'grades', 'notes', 'supports', 'documents', 'resources', 'files']) {
            if (Array.isArray(payload[key])) return payload[key];
            if (payload[key] && typeof payload[key] === 'object') {
                const nestedItems = unwrap(payload[key]);
                if (nestedItems.length) return nestedItems;
            }
        }

        const objectValues = Object.values(payload);
        const allArrays = objectValues.filter(Array.isArray);
        if (allArrays.length) return allArrays.flat();

        const flattenedNested = objectValues
            .map(value => unwrap(value))
            .filter(items => items.length);
        if (flattenedNested.length) return flattenedNested.flat();

        if (objectValues.length && objectValues.every(value => value && typeof value === 'object' && !Array.isArray(value))) return objectValues;
        return [];
    };

    function updateStudent(profile) {
        if (profile?.offline) return;
        const user = profile?.data || profile?.result || profile || {};
        const findProfileValue = keys => nested(user, keys);
        const firstName = findProfileValue(['firstname', 'firstName', 'givenName', 'given_name', 'prenom', 'forename']);
        const profileName = typeof user.name === 'string' ? user.name.trim() : '';
        const lastName = findProfileValue(['lastname', 'lastName', 'familyName', 'family_name', 'surname', 'surName', 'last_name', 'nom', 'nomFamille', 'nom_famille']) || (profileName && profileName.toLowerCase() !== String(firstName || '').toLowerCase() ? profileName : '');
        const displayName = findProfileValue(['fullName', 'full_name', 'displayName', 'display_name']);
        const name = [firstName, lastName].filter(Boolean).join(' ') || displayName || user.name || user.username || 'Étudiant MyGES';
        const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase() || 'MG';
        document.querySelectorAll('.top-bar-name, .panneau-entete .nom').forEach(element => element.textContent = name);
        document.querySelectorAll('.top-bar .avatar').forEach(element => element.textContent = initials);
        if (name !== 'Étudiant MyGES' && !profile?.offline) window.mygesStorage.saveStudent({ name, initials });
    }

    async function loadProfile() {
        const cachedStudent = window.mygesStorage.readStudent();
        if (cachedStudent?.name) updateStudent(cachedStudent);
        try { updateStudent(await window.mygesApi.profile()); }
        catch (error) {
            if (error.status === 401) toast('Session serveur expirée. Les données enregistrées restent disponibles.');
        }
    }

    const hasUsableGrade = value => {
        if (Array.isArray(value)) return value.some(hasUsableGrade);
        if (!value || typeof value !== 'object') return false;
        const gradeValue = value.value ?? value.grade ?? value.note ?? value.score;
        if (gradeValue !== undefined && gradeValue !== null && gradeValue !== '') {
            return Number.isFinite(Number(String(gradeValue).replace(',', '.')));
        }
        return ['evaluations', 'assessments', 'notes', 'grades'].some(key => hasUsableGrade(value[key]));
    };

    const hasUsableGrades = items => Array.isArray(items) && items.some(hasUsableGrade);

    async function loadResource(resource, render) {
        const selectedDate = typeof DATE_AFFICHEE !== 'undefined' ? DATE_AFFICHEE : new Date().toISOString().slice(0, 10);
        const weekStart = resource === 'planning' ? weekStartKey(selectedDate) : '';
        const cached = resource === 'planning' ? window.mygesStorage.readPlanningWeek(weekStart) : window.mygesStorage.readResource(resource);
        if (cached?.value?.length) render(cached.value);
        try {
            const fetchFresh = async attempts => {
                const value = unwrap(await window.mygesApi[resource](resource === 'planning' ? selectedDate : undefined));
                if (resource === 'planning' && value.length === 0 && attempts > 0) {
                    await new Promise(resolve => setTimeout(resolve, 700));
                    return fetchFresh(attempts - 1);
                }
                return value;
            };
            const value = await fetchFresh(2);
            if (!value.length && cached?.value?.length) return;
            if (resource === 'grades' && cached?.value?.length && hasUsableGrades(cached.value) && !hasUsableGrades(value)) {
                render(cached.value);
                return;
            }
            if (resource === 'planning') window.mygesStorage.savePlanningWeek(weekStart, itemsForWeek(value, weekStart));
            else window.mygesStorage.saveResource(resource, value);
            render(value);
        } catch (error) {
            if (cached?.value?.length) return;
            if (error.status === 401 && resource !== 'grades') { toast('La session MyGES doit être resynchronisée.'); return; }
            if (error.status === 401 && resource === 'grades' && cached?.value?.length) return;
            if (error.status === 401 && resource === 'grades') {
                const content = document.querySelector('.notes-content');
                if (content) content.innerHTML = '<div class="etat-vide"><p>Impossible de synchroniser les notes pour le moment.</p><p>Ta session locale est conservée, réessaie dans quelques instants.</p></div>';
                return;
            }
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
        const ordered = [...visible].sort((first, second) => (eventMoment(first)?.getTime() || 0) - (eventMoment(second)?.getTime() || 0));
        const rows = ordered.reduce((html, item, index) => {
            const previous = ordered[index - 1];
            const previousEnd = previous && eventMoment(previous, true);
            const currentStart = eventMoment(item);
            if (previousEnd && currentStart && currentStart > previousEnd) {
                const pauseTime = previousEnd.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }).replace(':', 'h');
                html += `<div class="creneau creneau-pause"><div class="creneau-heure"><span>${escapeHtml(pauseTime)}</span></div><div class="creneau-barre"></div><div class="creneau-corps"><div class="titre">Pas de cours</div></div></div>`;
            }
            const entry = normalize(item);
            const [startTime, endTime] = entry.time.split(/\s+-\s+/);
            const displayedEnd = !ordered[index + 1] && endTime ? `<span>${escapeHtml(endTime)}</span>` : '';
            return html + `<div class="creneau"><div class="creneau-heure"><span>${escapeHtml(startTime)}</span>${displayedEnd}</div><div class="creneau-barre" style="background:${escapeHtml(courseColor(item, index))}"></div><div class="creneau-corps"><div class="titre">${escapeHtml(entry.title)}</div><div class="meta">${escapeHtml(entry.type)}${entry.teacher ? `<br>${escapeHtml(entry.teacher)}` : ''}<br><strong>${escapeHtml(entry.room)}</strong></div></div></div>`;
        }, '');
        const schedule = content.querySelector('.nav-jour')?.nextElementSibling;
        content.querySelectorAll('.creneau, .creneau-vide, .planning-empty').forEach(element => element.remove());
        schedule?.insertAdjacentHTML('afterend', rows || '<div class="etat-vide planning-empty"><p>Pas de cours ce jour</p><img class="etat-vide-image" src="assets/img/image.png" alt="Aucun cours ce jour"></div>');
        window.DONNEES_EMPLOI_DU_TEMPS = items.reduce((all, item) => { const key = dateKey(item); if (key) (all[key] ||= []).push(item); return all; }, {});
        setupCalendar(items);
    }

    const supportTitle = item => text(item.title || item.name || item.label || item.filename || item.fileName || item.file_name || item.document, 'Support de cours');
    const supportSubject = item => text(item.subject || item.course || item.courseName || item.course_name || item.matter || item.matiere || item.matière || item.module || item.category, 'Cours');
    const supportType = item => text(item.type || item.mimeType || item.mime_type || item.extension, 'Document');
    const supportDate = item => text(item.date || item.createdAt || item.created_at || item.updatedAt || item.updated_at, '');
    const supportUrl = item => {
        const findPrivateUrl = value => {
            if (typeof value === 'string') {
                if (value.includes('ges-dl.kordis.fr')) return value;
                if (value.startsWith('/private/')) return `https://ges-dl.kordis.fr${value}`;
                return '';
            }
            if (Array.isArray(value)) {
                for (const entry of value) { const found = findPrivateUrl(entry); if (found) return found; }
                return '';
            }
            if (value && typeof value === 'object') {
                for (const entry of Object.values(value)) { const found = findPrivateUrl(entry); if (found) return found; }
            }
            return '';
        };
        return findPrivateUrl(item) || '';
    };
    const renderSupports = items => {
        window.supportsData = items;
        const list = document.getElementById('supports-list');
        const count = document.getElementById('supports-count');
        const search = document.getElementById('supports-search-input');
        if (!list) return;
        const query = (search?.value || '').trim().toLocaleLowerCase('fr');
        const visible = items.filter(item => [supportTitle(item), supportSubject(item), supportType(item)].join(' ').toLocaleLowerCase('fr').includes(query));
        if (count) count.textContent = `${visible.length} support${visible.length > 1 ? 's' : ''}`;
        if (!visible.length) {
            list.innerHTML = `<div class="etat-vide supports-empty"><p>${query ? 'Aucun support ne correspond à votre recherche.' : 'Aucun support de cours disponible.'}</p></div>`;
            return;
        }
        list.innerHTML = visible.map(item => {
            const title = escapeHtml(supportTitle(item));
            const subject = escapeHtml(supportSubject(item));
            const type = escapeHtml(supportType(item));
            const date = supportDate(item);
            const url = supportUrl(item);
            const dateLabel = date ? ` · ${escapeHtml(date)}` : '';
            const downloadIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 21h14"/></svg>';
            const action = url
                ? `<a class="support-download" href="${escapeHtml(url)}" download title="Télécharger" aria-label="Télécharger ${title}">${downloadIcon}</a>`
                : `<a class="support-download" href="https://myges.fr/student/courses-files" target="_blank" rel="noopener" title="Télécharger" aria-label="Télécharger les supports de cours">${downloadIcon}</a>`;
            return `<article class="support-card"><div class="support-card-icon">${escapeHtml(type.slice(0, 4).toUpperCase())}</div><div class="support-card-body"><h2>${title}</h2><p>${subject}${dateLabel}</p></div>${action}</article>`;
        }).join('');
    };

    let absenceItems = [];
    let absenceFilter = { start: '', end: '', type: '' };

    function renderHome(items) {
        const section = document.getElementById('home-planning') || document.querySelector('.section-accueil');
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

    const absenceDateKey = item => dateKey(item) || (() => {
        const value = text(item.date || item.day, '');
        const match = value.match(/(\d{1,2})[/-](\d{1,2})[/-](\d{4})/);
        return match ? `${match[3]}-${match[2].padStart(2, '0')}-${match[1].padStart(2, '0')}` : '';
    })();

    function renderHomeAbsences(items) {
        const section = document.getElementById('home-absences');
        if (!section) return;
        const recent = items.filter(item => !item.periodOnly).sort((first, second) => (absenceDateKey(second) || '').localeCompare(absenceDateKey(first) || '')).slice(0, 3);
        section.innerHTML = `<div class="home-section-heading"><h2 class="section-titre">Mes absences</h2><a href="absences.php" class="lien-voir-tout">Voir tout <span aria-hidden="true">→</span></a></div>${recent.length ? `<div class="absence-list home-absence-list">${recent.map(item => { const status = item.justified ? 'Justifiée' : 'À justifier'; return `<article class="absence-card"><div class="home-absence-main"><h3>${escapeHtml(courseName(item))}</h3><p class="meta">${escapeHtml(text(item.date || item.day, 'Date inconnue'))} · ${escapeHtml(text(item.type || item.status || status))}</p></div><span class="home-absence-status ${item.justified ? 'is-justified' : 'is-pending'}">${status}</span></article>`; }).join('')}</div>` : '<p class="etat-vide-mini">Aucune absence signalée</p>'}`;
    }

    function renderHomeAverages(items) {
        const section = document.getElementById('home-averages');
        if (!section) return;
        if (!items.length) { section.innerHTML = '<div class="home-section-heading"><h2 class="section-titre">Mes moyennes actuelles</h2><a href="notes.php" class="lien-voir-tout">Voir tout <span aria-hidden="true">→</span></a></div><p class="etat-vide-mini">Aucune moyenne disponible</p>'; return; }
        const periodLabel = item => text(item.period || item.semester || item.term || item.schoolYear, '') || 'Semestre actuel';
        const periods = [...new Map(items.map(item => [text(item.periodKey, '') || periodLabel(item), periodLabel(item)]))];
        const currentKey = periods[0]?.[0];
        const currentItems = items.filter(item => (text(item.periodKey, '') || periodLabel(item)) === currentKey);
        const valueOf = grade => Number(grade && typeof grade === 'object' ? grade.value ?? grade.grade ?? grade.note ?? grade.score : grade);
        const blockOf = item => {
            const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
            let saved = {};
            try { saved = JSON.parse(localStorage.getItem(`myges-note-blocks-${currentKey}`) || '{}'); } catch {}
            return text(saved[subject] || item.block || item.bloc || item.blockName || item.block_name || item.unit || item.ue, '');
        };
        const groups = new Map();
        currentItems.forEach(item => {
            const block = blockOf(item);
            if (!block || /^(0|zero)$/i.test(block)) return;
            const raw = item.evaluations || item.assessments || item.notes || item.grades;
            const values = (Array.isArray(raw) && raw.length ? raw : [item]).map(valueOf).filter(Number.isFinite);
            if (!values.length) return;
            if (!groups.has(block)) groups.set(block, []);
            groups.get(block).push(...values);
        });
        const blocks = [...groups.entries()].slice(0, 4);
        section.innerHTML = `<div class="home-section-heading"><h2 class="section-titre">Mes moyennes actuelles</h2><a href="notes.php" class="lien-voir-tout">Voir tout <span aria-hidden="true">→</span></a></div><p class="home-average-period">${escapeHtml(periods.find(([key]) => key === currentKey)?.[1] || 'Semestre actuel')}</p>${blocks.length ? `<div class="home-average-grid">${blocks.map(([block, values]) => { const average = values.reduce((sum, value) => sum + value, 0) / values.length; return `<article class="home-average-card"><span>${escapeHtml(block)}</span><strong>${average.toFixed(1)}<small>/20</small></strong></article>`; }).join('')}</div>` : '<p class="etat-vide-mini">Classe tes matières par bloc dans la page Notes pour afficher les moyennes.</p>'}`;
    }

    function renderAbsences(items) {
        const summary = document.querySelector('.carte-resume');
        if (!summary) return;
        const content = document.querySelector('.absence-content');
        if (!content) return;
        const periodKey = item => text(item.periodKey, '') || text(item.period || item.semester || item.schoolYear, '') || 'current';
        const periodLabel = item => text(item.period || item.semester || item.schoolYear, '') || 'Semestre actuel';
        const periods = [...new Map(items.map(item => [periodKey(item), periodLabel(item)]))];
        const savedPeriod = content.dataset.selectedPeriod || localStorage.getItem('myges-absence-period') || '';
        const selectedPeriod = periods.some(([key]) => String(key) === savedPeriod) ? savedPeriod : String(periods[0]?.[0] || 'current');
        absenceItems = items;
        const selectedItems = items.filter(item => periodKey(item) === selectedPeriod && !item.periodOnly).filter(item => {
            const key = absenceDateKey(item);
            const type = text(item.type || item.status || (item.justified ? 'justifiee' : 'non_justifiee'), '').toLowerCase();
            return (!absenceFilter.start || key >= absenceFilter.start) && (!absenceFilter.end || key <= absenceFilter.end) && (!absenceFilter.type || (absenceFilter.type === 'justifiee' ? item.justified : absenceFilter.type === 'retard' ? type.includes('retard') : !item.justified));
        });
        const justified = selectedItems.filter(item => item.justified || String(item.status || item.justified).toLowerCase().includes('just')).length;
        summary.querySelector('.titre-nombre').textContent = `Absences ${selectedItems.length}`;
        summary.querySelector('.sous-titre').textContent = `À justifier : ${selectedItems.length - justified}`;
        const details = document.getElementById('details-resume');
        if (details) details.innerHTML = `<p>Récapitulatif du semestre sélectionné :</p><ul><li>Absences justifiées : ${justified}</li><li>Absences non justifiées : ${selectedItems.length - justified}</li><li>Retards : ${selectedItems.filter(item => text(item.type || item.status, '').toLowerCase().includes('retard')).length}</li></ul>`;
        const select = document.getElementById('select-absence-period');
        if (select) {
            select.innerHTML = periods.map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(key) === selectedPeriod ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('');
            select.onchange = event => {
                content.dataset.selectedPeriod = event.target.value;
                localStorage.setItem('myges-absence-period', event.target.value);
                renderAbsences(items);
            };
        }
        const filterButton = document.getElementById('btn-ouvrir-filtre');
        filterButton?.classList.toggle('is-active', Boolean(absenceFilter.start || absenceFilter.end || absenceFilter.type));
        content.innerHTML = selectedItems.length
            ? `<div class="absence-list">${selectedItems.map(item => { const status = item.justified ? 'Justifiée' : 'À justifier'; const type = text(item.type || item.status, 'Absence'); return `<article class="absence-card"><div class="absence-date"><strong>${escapeHtml(text(item.date || item.day, 'Date inconnue'))}</strong><span>${escapeHtml(type)}</span></div><div class="absence-card-main"><h3>${escapeHtml(courseName(item))}</h3></div><span class="absence-status ${item.justified ? 'is-justified' : 'is-pending'}">${status}</span></article>`; }).join('')}</div>`
            : '<p>Aucune absence signalée pour ce semestre</p><img class="etat-vide-image" src="assets/img/image.png" alt="Aucun résultat">';
    }

    const gradeYear = item => {
        const years = [];
        const collect = value => {
            if (value === null || value === undefined) return;
            if (Array.isArray(value)) {
                value.forEach(collect);
                return;
            }
            if (typeof value === 'object') {
                Object.keys(value).forEach(key => {
                    const keyMatches = [...String(key).matchAll(/20\d{2}/g)].map(match => Number(match[0]));
                    if (keyMatches.length) years.push(...keyMatches);
                    const normalizedKey = String(key).match(/20\d{2}/g);
                    if (normalizedKey) years.push(...normalizedKey.map(Number));
                    collect(value[key]);
                });
                return;
            }
            if (typeof value !== 'string') return;
            const matches = [...value.matchAll(/20\d{2}/g)].map(match => Number(match[0]));
            if (matches.length) years.push(...matches);
            const slashMatches = [...value.matchAll(/(20\d{2})[-/](20\d{2})/g)].flatMap(match => [Number(match[1]), Number(match[2])]);
            if (slashMatches.length) years.push(...slashMatches);
        };

        collect(item);
        if (!years.length) return null;
        return Math.max(...years);
    };

    const availableGradeYears = items => {
        const years = [...new Set(items.map(item => gradeYear(item)).filter(value => Number.isInteger(value)))].sort((a, b) => b - a);
        return years.length ? years : [new Date().getFullYear()];
    };

    function renderGrades(items) {
        const content = document.querySelector('.notes-content');
        if (!content) return;
        if (!items.length) { content.innerHTML = '<div class="etat-vide"><p>Aucune note disponible pour le moment</p><img class="etat-vide-image" src="assets/img/image.png" alt="Aucun résultat"></div>'; return; }

        const periodKey = item => text(item.periodKey, '') || text(item.period || item.semester || item.term || item.schoolYear, '') || String(gradeYear(item) ?? 'Période actuelle');
        const periodLabel = item => text(item.period || item.semester || item.term || item.schoolYear, '') || 'Période actuelle';
        const periods = [...new Map(items.map(item => [periodKey(item), periodLabel(item)])).entries()];
        const savedPeriod = content.dataset.selectedYear ? String(content.dataset.selectedYear) : '';
        const selectedYear = periods.some(([key]) => String(key) === savedPeriod) ? savedPeriod : String(periods[0]?.[0] || 'Période actuelle');
        const filteredItems = periods.length > 1
            ? items.filter(item => periodKey(item) === selectedYear)
            : items;
        const parsePeriodType = label => {
            if (!label) return null;
            if (/trimestre/i.test(label)) return 'trimestre';
            if (/semestre/i.test(label) || /\bS\d+\b/i.test(label)) return 'semestre';
            return null;
        };
        const parsePeriodNumber = label => {
            if (!label) return null;
            const match = String(label).match(/(?:semestre|trimestre|S|T)\s*(\d+)/i) || String(label).match(/\b(\d+)\b/);
            if (!match) return null;
            const value = Number(match[1]);
            return Number.isFinite(value) ? value : null;
        };
        const parseAcademicYear = label => {
            if (!label) return null;
            const matches = [...String(label).matchAll(/20\d{2}/g)].map(match => Number(match[0]));
            return matches.length ? matches[0] : null;
        };
        const currentLabel = periods.find(([key]) => String(key) === selectedYear)?.[1] || '';
        const currentType = parsePeriodType(currentLabel);
        const currentYear = parseAcademicYear(currentLabel);
        const expectedPeriodCount = currentType === 'semestre' ? 2 : (currentType === 'trimestre' ? 3 : 0);
        const annualGroups = new Map();
        periods.filter(([, label]) => parsePeriodType(label) === currentType).forEach(([key, label]) => {
            const groupKey = `${currentType}|${parseAcademicYear(label) ?? 'sans-annee'}`;
            if (!annualGroups.has(groupKey)) annualGroups.set(groupKey, []);
            annualGroups.get(groupKey).push([key, label]);
        });
        const completeAnnualGroups = [...annualGroups.values()]
            .map(group => [...group].sort(([, firstLabel], [, secondLabel]) => parsePeriodNumber(firstLabel) - parsePeriodNumber(secondLabel)))
            .filter(group => expectedPeriodCount > 0 && group.length === expectedPeriodCount && group.every(([, label], index) => parsePeriodNumber(label) === index + 1))
            .sort((first, second) => (parseAcademicYear(second[0][1]) ?? 0) - (parseAcademicYear(first[0][1]) ?? 0));
        const selectedAnnualGroup = currentYear !== null
            ? completeAnnualGroups.find(group => parseAcademicYear(group[0][1]) === currentYear) || []
            : completeAnnualGroups[0] || [];
        const annualPeriods = selectedAnnualGroup.sort(([, firstLabel], [, secondLabel]) => parsePeriodNumber(firstLabel) - parsePeriodNumber(secondLabel));
        const completeAnnualPeriods = annualPeriods.length === expectedPeriodCount;
        const periodBlockAverage = periodKeyValue => {
            const periodItems = items.filter(item => periodKey(item) === periodKeyValue);
            const periodBlocks = savedBlocksForPeriod(periodKeyValue);
            const assignedBlockAverages = [...new Map(
                periodItems
                    .map(item => {
                        const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
                        const blockName = normalizeBlockName(periodBlocks[subject] ?? blockLabel(item, null));
                        if (isIgnoredBlock(blockName) || blockName === '') return null;
                        return [blockName, []];
                    })
                    .filter(Boolean)
            )].map(([blockName]) => {
                const blockSubjects = periodItems.reduce((groups, item) => {
                    const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
                    const blockName = normalizeBlockName(periodBlocks[subject] ?? blockLabel(item, null));
                    if (normalizeBlockName(blockName) !== blockName) return groups;
                    if (blockName !== blockName) return groups;
                    const rawEvaluations = item.evaluations || item.assessments || item.notes || item.grades;
                    const nestedGrades = Array.isArray(rawEvaluations) && rawEvaluations.length ? rawEvaluations : [item];
                    const notes = nestedGrades.map((grade, index) => {
                        const value = grade && typeof grade === 'object' ? grade.value ?? grade.grade ?? grade.note ?? grade.score : grade;
                        const label = grade && typeof grade === 'object'
                            ? text(grade.label || grade.assessment || grade.evaluation || grade.exam || grade.type || grade.name, `Évaluation ${index + 1}`)
                            : `Évaluation ${index + 1}`;
                        const gradeSource = grade && typeof grade === 'object' ? grade : {};
                        const number = Number(value);
                        return { number, weight: numericWeight(gradeSource, item), isPartial: /partiel|examen|exam|final/.test(`${label} ${subject}`.toLowerCase()) };
                    }).filter(note => Number.isFinite(note.number));
                    if (!notes.length) return groups;
                    return [...groups, { subject, notes, weight: 1, block: blockName }];
                }, []);
                return blockSubjects.filter(subjectNotes => subjectNotes.block === blockName && subjectNotes.notes.length).map(subjectNotes => subjectNotes.notes);
            }).filter(blockSubjects => blockSubjects.length).map(blockSubjects => weightedSubjectAverage(blockSubjects));
            const validBlockAverages = assignedBlockAverages.filter(Number.isFinite);
            if (!validBlockAverages.length) return null;
            return validBlockAverages.reduce((sum, value) => sum + value, 0) / validBlockAverages.length;
        };
        const cumulativeAverageForSelectedPeriod = () => {
            const currentType = parsePeriodType(currentLabel);
            const currentNumber = parsePeriodNumber(currentLabel);
            const currentYear = parseAcademicYear(currentLabel);
            if (!currentType || !Number.isFinite(currentNumber)) return overallAverage;
            const relevantPeriods = periods.filter(([key, label]) => {
                const labelType = parsePeriodType(label);
                const labelNumber = parsePeriodNumber(label);
                const labelYear = parseAcademicYear(label);
                if (labelType !== currentType || !Number.isFinite(labelNumber)) return false;
                if (currentYear !== null && labelYear !== null && labelYear !== currentYear) return false;
                return labelNumber <= currentNumber;
            });
            const averages = relevantPeriods
                .map(([key]) => periodBlockAverage(key))
                .filter(Number.isFinite);
            if (!averages.length) return overallAverage;
            return averages.reduce((sum, value) => sum + value, 0) / averages.length;
        };
        const annualAverageFromBlocks = () => {
            if (!completeAnnualPeriods) return null;
            const averages = annualPeriods.map(([key]) => periodBlockAverage(key)).filter(Number.isFinite);
            return averages.length === expectedPeriodCount
                ? averages.reduce((sum, value) => sum + value, 0) / averages.length
                : null;
        };

        const yearSelector = periods.length ? `
            <div class="notes-controls">
                <label class="notes-label" for="select-note-year">Année / période</label>
                <select id="select-note-year" class="notes-select" aria-label="Choisir l’année des notes">
                    ${periods.map(([key, label]) => `<option value="${escapeHtml(key)}" ${String(key) === selectedYear ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                </select>
            </div>
        ` : '';

        const firstValue = (source, keys) => {
            if (!source || typeof source !== 'object') return '';
            for (const key of keys) {
                if (source[key] !== undefined && source[key] !== null && source[key] !== '') return source[key];
            }
            return '';
        };
        const blockLabel = (item, grade) => text(firstValue(grade, ['block', 'bloc', 'blockName', 'blocName', 'block_name', 'unit', 'unitName', 'ue', 'ueName', 'teachingUnit', 'teaching_unit', 'category']) || firstValue(item, ['block', 'bloc', 'blockName', 'blocName', 'block_name', 'unit', 'unitName', 'ue', 'ueName', 'teachingUnit', 'teaching_unit', 'category']), '');
        const numericWeight = (source, fallback = {}) => {
            const value = Number(firstValue(source, ['coefficient', 'coeff', 'weight', 'ponderation', 'credit', 'credits']));
            const fallbackValue = Number(firstValue(fallback, ['coefficient', 'coeff', 'weight', 'ponderation', 'credit', 'credits']));
            return Number.isFinite(value) && value > 0 ? value : (Number.isFinite(fallbackValue) && fallbackValue > 0 ? fallbackValue : 1);
        };
        const savedBlocks = (() => {
            try { return JSON.parse(localStorage.getItem(`myges-note-blocks-${selectedYear}`) || '{}'); } catch { return {}; }
        })();
        const savedBlocksForPeriod = periodKeyValue => {
            if (String(periodKeyValue) === String(selectedYear)) return savedBlocks;
            try { return JSON.parse(localStorage.getItem(`myges-note-blocks-${periodKeyValue}`) || '{}'); } catch { return {}; }
        };
        const saveBlock = (subject, block) => {
            if (block) savedBlocks[subject] = block;
            else delete savedBlocks[subject];
            localStorage.setItem(`myges-note-blocks-${selectedYear}`, JSON.stringify(savedBlocks));
        };
        const subjectScore = notes => {
            const scored = notes.filter(note => Number.isFinite(note.number));
            return scored.length ? scored.reduce((sum, note) => sum + note.number, 0) / scored.length : null;
        };
        const subjectBreakdown = notes => {
            const continuous = notes.filter(note => !note.isPartial && Number.isFinite(note.number));
            const exams = notes.filter(note => note.isPartial && Number.isFinite(note.number));
            const continuousAverage = continuous.length ? continuous.reduce((sum, note) => sum + note.number, 0) / continuous.length : null;
            const examAverage = exams.length ? exams.reduce((sum, note) => sum + note.number, 0) / exams.length : null;
            return { continuousAverage, examAverage };
        };
        const formatAverage = value => Number.isFinite(value) ? value.toFixed(1) : '—';
        const pickMessage = (messages, seed = '') => {
            const value = String(seed).split('').reduce((total, character) => total + character.charCodeAt(0), 0);
            return messages[value % messages.length];
        };
        const normalizeBlockName = value => String(value ?? '').trim();
        const isIgnoredBlock = value => {
            const blockName = normalizeBlockName(value);
            return blockName === '' || blockName === '0' || blockName.toLowerCase() === 'zero';
        };
        const averageByPeriod = periodItems => {
            const periodSubjects = [...new Map(periodItems.map(item => {
                const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
                const rawEvaluations = item.evaluations || item.assessments || item.notes || item.grades;
                const nestedGrades = Array.isArray(rawEvaluations) && rawEvaluations.length ? rawEvaluations : [item];
                return [subject, { grades: nestedGrades, item }];
            })).entries()].map(([subject, rawGrades]) => {
                const notes = rawGrades.grades.map((grade, index) => {
                    const value = grade && typeof grade === 'object' ? grade.value ?? grade.grade ?? grade.note ?? grade.score : grade;
                    const label = grade && typeof grade === 'object' ? text(grade.label || grade.assessment || grade.evaluation || grade.exam || grade.type || grade.name, value === null || value === undefined || value === '' ? 'Aucune évaluation' : `Évaluation ${index + 1}`) : `Évaluation ${index + 1}`;
                    const gradeSource = grade && typeof grade === 'object' ? grade : {};
                    return { subject, value, label, block: '', number: Number(value), weight: numericWeight(gradeSource, rawGrades.item), isPartial: /partiel|examen|exam|final/.test(String(label).toLowerCase()) };
                }).filter(note => Number.isFinite(note.number));
                return notes;
            }).filter(notes => notes.length);
            if (!periodSubjects.length) return null;
            return weightedSubjectAverage(periodSubjects);
        };
        const computeAnnualAverage = allItems => {
            const periodKeys = [...new Set(allItems.map(item => periodKey(item)).filter(Boolean))];
            if (!periodKeys.length) return overallAverage;
            const filteredItems = allItems.filter(item => {
                const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
                const blockName = normalizeBlockName(savedBlocks[subject] ?? blockLabel(item, null));
                return !isIgnoredBlock(blockName);
            });
            const periodAverages = periodKeys
                .map(key => averageByPeriod(filteredItems.filter(item => periodKey(item) === key)))
                .filter(Number.isFinite);
            if (!periodAverages.length) return overallAverage;
            const semesterCount = filteredItems.filter(item => /semestre/i.test(String(periodLabel(item)))).length
                ? filteredItems.filter(item => /semestre/i.test(String(periodLabel(item)))).length / Math.max(1, periodAverages.length)
                : 0;
            const trimesterCount = filteredItems.filter(item => /trimestre/i.test(String(periodLabel(item)))).length
                ? filteredItems.filter(item => /trimestre/i.test(String(periodLabel(item)))).length / Math.max(1, periodAverages.length)
                : 0;
            if (semesterCount > 0 && periodAverages.length >= 2) return periodAverages.reduce((sum, value) => sum + value, 0) / periodAverages.length;
            if (trimesterCount > 0 && periodAverages.length >= 3) return periodAverages.reduce((sum, value) => sum + value, 0) / periodAverages.length;
            return overallAverage;
        };
        const buildJuryStatus = annualAverage => {
            const average = Number.isFinite(annualAverage) ? annualAverage : 0;
            if (average >= 10) {
                return {
                    tone: 'is-pass',
                    tag: 'Passe',
                    title: 'Tu passes l’année',
                    summary: pickMessage(['Tu as la moyenne générale.', 'Ton année est validée.', 'La moyenne annuelle permet le passage.'], average),
                    detail: 'La décision du jury est favorable.'
                };
            }
            if (average < 8) {
                return {
                    tone: 'is-danger',
                    tag: 'Redouble',
                    title: 'Tu redoubles',
                    summary: pickMessage(['La moyenne annuelle est inférieure à 8.', 'La moyenne de l’année est trop basse pour valider le passage.', 'Avec cette moyenne annuelle, le passage n’est pas possible.'], average),
                    detail: 'Le jury considère l’année comme non validée.'
                };
            }
            if (average >= 8) {
                return {
                    tone: 'is-limit',
                    tag: 'Rattrapage',
                    title: 'Tu vas en rattrapage',
                    summary: pickMessage(['La moyenne annuelle se situe dans la zone de rattrapage.', 'Le passage n’est pas encore validé, mais le rattrapage reste possible.', 'La moyenne annuelle permet encore l’accès au rattrapage.'], average),
                    detail: 'Ta moyenne annuelle permet encore l’accès au rattrapage.'
                };
            }
            return {
                tone: 'is-limit',
                tag: 'Rattrapage',
                title: 'Tu vas en rattrapage',
                summary: 'Tu es dans la zone de rattrapage.',
                detail: 'La moyenne est trop basse pour passer, mais pas assez basse pour redoubler.'
            };
        };
        const averageMessage = value => {
            if (!Number.isFinite(value)) return 'Les petites notes arrivent bientôt, patience et douceur jusque-là.';
            const messages = [
                [8, ['Petit pas par petit pas, tu avances déjà très bien.', 'Le semestre joue les montagnes russes, mais tu tiens le guidon.', 'Une petite pause, une grande respiration, puis on repart doucement.', 'Chaque point gagné est une mini victoire à collectionner.', 'Tu n’as pas besoin d’être parfait, juste de continuer à avancer.', 'Les notes difficiles ne racontent qu’un chapitre, pas toute ton histoire.', 'Ton courage travaille en coulisses, même quand la moyenne boude un peu.', 'On garde le cap : les progrès aiment les efforts réguliers.', 'Un nuage passe toujours, surtout avec un peu de persévérance.', 'Tu peux être fier de toi rien que pour avoir continué.']],
                [10, ['Les bases sont là, et elles sont prêtes à devenir encore plus solides.', 'Quelques matières font leur timide, mais rien n’est joué.', 'Tu es tout près du mieux : un petit coup de pouce et ça repart.', 'La moyenne te fait un clin d’œil, elle attend juste quelques points.', 'On resserre les lacets et on continue tranquillement.', 'Chaque prochaine note peut devenir une jolie remontée.', 'Tu as déjà construit une bonne partie du chemin.', 'Un peu de régularité et les résultats vont fleurir.', 'Les difficultés sont invitées à progresser avec toi.', 'Tu avances, même quand le tableau ne le montre pas encore assez.']],
                [12, ['Un joli équilibre se dessine, continue comme ça.', 'Ta moyenne pousse bien, comme une petite plante studieuse.', 'Les efforts commencent à se voir, et c’est très chouette.', 'Tu tiens un rythme doux et efficace.', 'Le semestre prend une belle direction.', 'Chaque note ajoute une petite étoile à ton parcours.', 'Tu peux te féliciter : la régularité paie.', 'Le travail discret fait doucement de grandes choses.', 'Tu es sur une bonne lancée, garde cette énergie.', 'Ton semestre avance avec de jolies couleurs.']],
                [14, ['Un semestre solide et plein de belles petites réussites.', 'Tu peux être content : ton travail commence à vraiment briller.', 'La moyenne est bien installée, comme un chat au soleil.', 'Tu avances avec sérieux et une belle constance.', 'Tes efforts forment une très jolie collection de réussites.', 'Le semestre te va bien, continue sur cette lancée.', 'Tu peux prendre un instant pour admirer le chemin parcouru.', 'C’est du travail propre, régulier et très encourageant.', 'Les bonnes habitudes portent leurs fruits avec élégance.', 'Tu construis une moyenne qui a fière allure.']],
                [16, ['Très joli rythme : tes résultats ont le sourire.', 'Tu peux être fier, ton travail est vraiment régulier.', 'La moyenne brille fort aujourd’hui, et c’est mérité.', 'Tu avances avec une belle maîtrise et beaucoup de constance.', 'Les efforts sont bien visibles, bravo pour cette énergie.', 'Ton semestre ressemble à une petite réussite bien ficelée.', 'Tu as trouvé un super rythme, garde-le précieusement.', 'Les notes dansent joliment dans la bonne direction.', 'C’est solide, élégant et très encourageant.', 'Tu peux savourer cette belle dynamique.']],
                [18, ['Quel niveau : tes résultats font presque des confettis.', 'Un semestre magnifique, porté par une superbe régularité.', 'Tu peux être très fier, cette moyenne est éclatante.', 'Les notes sont au rendez-vous et elles ont clairement le sourire.', 'C’est une vraie collection de belles réussites.', 'Ton travail brille comme une petite constellation.', 'Tu maintiens un niveau impressionnant avec beaucoup de sérieux.', 'Le semestre est superbement maîtrisé, bravo à toi.', 'Une performance toute douce et franchement remarquable.', 'Tu peux célébrer cette très belle réussite.']],
                [Number.POSITIVE_INFINITY, ['Performance exceptionnelle : tu fais briller le tableau.', 'Un niveau remarquable, avec une régularité de champion.', 'Tes résultats sont magnifiques, quelle belle énergie.', 'Tu as transformé le travail en véritable petit feu d’artifice.', 'C’est impressionnant et entièrement mérité.', 'Ton semestre est une jolie démonstration de constance.', 'Les notes sont splendides, tu peux être vraiment fier.', 'Un grand bravo pour cette performance lumineuse.', 'Tu avances avec une maîtrise absolument remarquable.', 'Le tableau des notes n’a jamais été aussi content.']],
            ];
            const entry = messages.find(([limit]) => value < limit);
            return pickMessage(entry[1], value.toFixed(1));
        };
        const subjectSignal = (value, seed = '') => {
            if (!Number.isFinite(value)) return { className: 'is-pending', label: 'En attente', comment: 'La petite note se fait désirer, mais elle finira bien par arriver.' };
            if (value < 5) { return { className: 'is-critical', label: 'Priorité', comment: pickMessage(['Cette matière mérite un gros câlin et un peu de temps.', 'On la prend doucement par la main pour remonter ensemble.', 'Pas de panique : un petit plan d’attaque peut tout changer.', 'Cette note est basse, mais ton potentiel ne l’est pas.', 'On respire, on découpe le problème, et on avance petit à petit.', 'Chaque nouvelle note peut écrire une suite beaucoup plus jolie.', 'La matière fait sa difficile, mais tu peux lui montrer qui commande.', 'Un peu d’aide, quelques exercices et la remontée commence.', 'Ce n’est qu’un point de départ, jamais une étiquette.', 'On transforme cette alerte en nouvelle victoire.'], seed) }; }
            if (value < 8) { return { className: 'is-alert', label: 'À renforcer', comment: pickMessage(['Un peu de douceur et quelques révisions feront bon ménage.', 'Cette matière demande de l’attention, pas de la culpabilité.', 'On lui offre quelques exercices et beaucoup de confiance.', 'La remontée est à portée de main, vraiment.', 'Un petit rendez-vous régulier avec le cours devrait aider.', 'Tu peux apprivoiser cette matière à ton rythme.', 'Les progrès se cachent parfois juste derrière une bonne méthode.', 'Un coup de pouce ici, et la moyenne reprendra des couleurs.', 'Cette note n’est pas une fatalité, juste un petit signal.', 'On avance tranquillement, une notion après l’autre.'], seed) }; }
            if (value < 10) { return { className: 'is-watch', label: 'À surveiller', comment: pickMessage(['Un petit regard attentif et tout devrait bien se passer.', 'La matière frôle la moyenne, elle mérite un peu d’encouragement.', 'Quelques points supplémentaires et elle sera toute contente.', 'On garde un œil dessus, sans pression inutile.', 'Une petite révision ciblée pourrait faire des merveilles.', 'Tu es proche du bon équilibre, continue doucement.', 'La moyenne hésite encore, mais elle peut vite basculer du bon côté.', 'Un peu de régularité et cette matière va respirer.', 'Tu n’es vraiment pas loin, courage pour la dernière marche.', 'Un petit effort ici peut avoir un joli effet.'], seed) }; }
            if (value >= 18) { return { className: 'is-excellent', label: 'Excellent', comment: pickMessage(['Cette matière a clairement sorti ses confettis.', 'Une note magnifique, tu peux être très fier.', 'La matière rayonne, et c’est largement mérité.', 'Quel joli sans-faute dans l’énergie et la régularité.', 'Cette moyenne mérite une petite danse de victoire.', 'Un résultat splendide, bravo pour ce beau travail.', 'La matière te fait un grand sourire depuis le haut du tableau.', 'C’est brillant, propre et vraiment impressionnant.', 'Une très belle réussite à garder précieusement.', 'Les étoiles sont alignées, bravo à toi.'], seed) }; }
            if (value >= 16) { return { className: 'is-strong', label: 'Très bon', comment: pickMessage(['Une très jolie moyenne, bravo pour cette régularité.', 'Cette matière est entre de bonnes mains.', 'Un résultat solide qui mérite un grand sourire.', 'Tu peux être fier de cette belle réussite.', 'La matière avance avec beaucoup d’élégance.', 'Très beau travail, la constance paie vraiment.', 'Une moyenne qui respire la maîtrise et le sérieux.', 'Cette matière te fait honneur, continue comme ça.', 'Un joli petit sommet déjà atteint.', 'La réussite est bien installée ici.'], seed) }; }
            return null;
        };
        const weightedSubjectAverage = subjectGroups => {
            const scored = subjectGroups.map(notes => subjectScore(notes)).filter(Number.isFinite);
            if (!scored.length) return null;
            return scored.reduce((sum, value) => sum + value, 0) / scored.length;
        };
        const annualBlockData = () => {
            if (!completeAnnualPeriods) return [];
            const subjectsByBlock = new Map();
            annualPeriods.forEach(([periodKeyValue]) => {
                const periodBlocks = savedBlocksForPeriod(periodKeyValue);
                items.filter(item => periodKey(item) === periodKeyValue).forEach(item => {
                    const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
                    const block = normalizeBlockName(periodBlocks[subject] ?? blockLabel(item, null));
                    if (isIgnoredBlock(block)) return;
                    const rawEvaluations = item.evaluations || item.assessments || item.notes || item.grades;
                    const nestedGrades = Array.isArray(rawEvaluations) && rawEvaluations.length ? rawEvaluations : [item];
                    const notes = nestedGrades.map((grade, index) => {
                        const value = grade && typeof grade === 'object' ? grade.value ?? grade.grade ?? grade.note ?? grade.score : grade;
                        const label = grade && typeof grade === 'object'
                            ? text(grade.label || grade.assessment || grade.evaluation || grade.exam || grade.type || grade.name, `Évaluation ${index + 1}`)
                            : `Évaluation ${index + 1}`;
                        const gradeSource = grade && typeof grade === 'object' ? grade : {};
                        return { number: Number(value), weight: numericWeight(gradeSource, item), isPartial: /partiel|examen|exam|final/.test(`${label} ${subject}`.toLowerCase()) };
                    }).filter(note => Number.isFinite(note.number));
                    if (!notes.length) return;
                    const key = `${block}\u0000${subject}`;
                    if (!subjectsByBlock.has(key)) subjectsByBlock.set(key, { block, subject, notes: [] });
                    subjectsByBlock.get(key).notes.push(...notes);
                });
            });
            const blocks = new Map();
            subjectsByBlock.forEach(subjectData => {
                if (!blocks.has(subjectData.block)) blocks.set(subjectData.block, []);
                blocks.get(subjectData.block).push(subjectData.notes);
            });
            return [...blocks.entries()];
        };
        const evaluations = filteredItems.flatMap(item => {
            const subject = text(item.subject || item.course || item.course_name || item.courseName || item.name || item.title, 'Matière');
            const rawEvaluations = item.evaluations || item.assessments || item.notes || item.grades;
            const nestedGrades = Array.isArray(rawEvaluations) && rawEvaluations.length ? rawEvaluations : [item];
            return nestedGrades.map((grade, index) => {
                const value = grade && typeof grade === 'object' ? grade.value ?? grade.grade ?? grade.note ?? grade.score : grade;
                const label = grade && typeof grade === 'object' ? text(grade.label || grade.assessment || grade.evaluation || grade.exam || grade.type || grade.name, value === null || value === undefined || value === '' ? 'Aucune évaluation' : `Évaluation ${index + 1}`) : `Évaluation ${index + 1}`;
                const details = `${label} ${subject}`.toLowerCase();
                const gradeSource = grade && typeof grade === 'object' ? grade : {};
                return { subject, value, label, block: blockLabel(item, grade), number: Number(value), weight: numericWeight(gradeSource, item), isPartial: /partiel|examen|exam|final/.test(details) };
            });
        });
        const subjects = [...new Map(evaluations.map(item => {
            const subjectNotes = evaluations.filter(note => note.subject === item.subject);
            const savedBlock = savedBlocks[item.subject];
            subjectNotes.forEach(note => { note.block = savedBlock || note.block || ''; });
            return [`${subjectNotes[0].block}\u0000${item.subject}`, subjectNotes];
        })).values()];
        const blockNumber = block => {
            const match = String(block).match(/\d+/);
            return match ? Number(match[0]) : Number.POSITIVE_INFINITY;
        };
        const validSubjects = subjects.filter(notes => !isIgnoredBlock(normalizeBlockName(notes[0].block)));
        const blocks = [...new Map(subjects.filter(notes => !isIgnoredBlock(normalizeBlockName(notes[0].block)) && normalizeBlockName(notes[0].block) !== '').map(notes => [normalizeBlockName(notes[0].block), subjects.filter(subjectNotes => normalizeBlockName(subjectNotes[0].block) === normalizeBlockName(notes[0].block) && !isIgnoredBlock(normalizeBlockName(subjectNotes[0].block)))])).entries()]
            .sort(([first], [second]) => blockNumber(first) - blockNumber(second) || String(first).localeCompare(String(second), 'fr'));
        const overallAverage = weightedSubjectAverage(validSubjects);
        const displayAverage = averageByPeriod(filteredItems) ?? overallAverage;
        const annualBlocks = annualBlockData();
        const annualBlockAverages = annualBlocks.map(([label, subjects]) => ({
            label,
            subjects,
            average: weightedSubjectAverage(subjects),
            scores: subjects.map(subjectNotes => subjectScore(subjectNotes)).filter(Number.isFinite)
        }));
        const annualPeriodAverages = completeAnnualPeriods
            ? annualPeriods.map(([periodKeyValue]) => averageByPeriod(items.filter(item => periodKey(item) === periodKeyValue)))
            : [];
        const annualAverage = annualPeriodAverages.length === expectedPeriodCount && annualPeriodAverages.every(Number.isFinite)
            ? annualPeriodAverages.reduce((sum, value) => sum + value, 0) / annualPeriodAverages.length
            : null;
        const juryStatus = buildJuryStatus(annualAverage);
        const unassigned = subjects.filter(notes => !notes[0].block);
        const shouldOpenAssignments = unassigned.length > 0 && content.dataset.assignmentDismissed !== 'true';
        const classifiedSubjects = subjects.filter(notes => !isIgnoredBlock(normalizeBlockName(notes[0].block)) && normalizeBlockName(notes[0].block) !== '');
        const allAssigned = subjects.length > 0 && subjects.every(notes => !isIgnoredBlock(normalizeBlockName(notes[0].block)) && normalizeBlockName(notes[0].block) !== '');
        const allAnnualSubjectsAssigned = completeAnnualPeriods && annualPeriods.every(([periodKeyValue]) => {
            const periodItems = items.filter(item => periodKey(item) === periodKeyValue);
            return periodItems.length > 0 && Number.isFinite(averageByPeriod(periodItems));
        });
        const assignmentPanel = `<div class="notes-assignment-modal${shouldOpenAssignments ? ' is-open' : ''}" id="notes-assignment-modal" aria-hidden="${shouldOpenAssignments ? 'false' : 'true'}"><div class="notes-assignment-backdrop" data-close-assignments></div><section class="notes-assignments" role="dialog" aria-modal="true" aria-labelledby="notes-assignment-title"><div class="notes-assignments-head"><div><p class="notes-kicker">Organisation</p><h2 id="notes-assignment-title">Classer les matières</h2></div><button class="notes-assignment-close" type="button" data-close-assignments title="Fermer" aria-label="Fermer le classement">&times;</button></div><p class="notes-assignment-intro">Donne un nom de bloc à chaque matière. La moyenne du bloc est affichée pour organiser tes résultats.</p><div class="notes-assignment-list">${subjects.map(notes => {
            const subject = notes[0].subject;
            return `<label class="notes-assignment"><span>${escapeHtml(subject)}</span><input class="matiere-block-input" type="text" value="${escapeHtml(savedBlocks[subject] || '')}" data-subject="${escapeHtml(subject)}" placeholder="Nom du bloc"></label>`;
        }).join('')}</div></section></div><button class="notes-assignment-fab" type="button" data-open-assignments title="Classer les matières" aria-label="Ouvrir le classement des matières"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7.5 12 4l8 3.5-8 3.5-8-3.5Zm0 4.5 8 3.5 8-3.5M4 16.5l8 3.5 8-3.5"/></svg></button><div class="notes-assignment-modal" id="notes-rules-modal" aria-hidden="true"><div class="notes-assignment-backdrop" data-close-rules></div><section class="notes-assignments notes-rules" role="dialog" aria-modal="true" aria-labelledby="notes-rules-title"><div class="notes-assignments-head"><div><p class="notes-kicker">Règles de décision</p><h2 id="notes-rules-title">Validation de l’année</h2></div><button class="notes-assignment-close" type="button" data-close-rules title="Fermer" aria-label="Fermer les règles">&times;</button></div><div class="notes-rules-list"><article><h3>Passage</h3><p>Moyenne annuelle supérieure ou égale à 10, tous les blocs validés.</p><ul><li>Moyenne annuelle du bloc supérieure ou égale à 10.</li><li>Aucune moyenne de matière égale à 0.</li><li>Moins de deux matières du même bloc avec une moyenne inférieure ou égale à 6.</li></ul></article><article><h3>Rattrapage</h3><p>Moyenne annuelle supérieure ou égale à 8, avec au moins une condition de rattrapage :</p><ul><li>Au moins un bloc a une moyenne annuelle inférieure à 10.</li><li>Une ou plusieurs matières ont une moyenne annuelle égale à 0.</li><li>Au moins deux matières d’un même bloc ont une moyenne annuelle inférieure ou égale à 6.</li></ul></article><article><h3>Redoublement / exclusion</h3><p>Moyenne annuelle inférieure à 8.</p></article></div></section></div><button class="notes-info-fab" type="button" data-open-rules title="Voir les règles de décision" aria-label="Voir les règles de décision"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 10.5v6M12 7.5h.01"/></svg></button>`;
        const juryBanner = allAnnualSubjectsAssigned && Number.isFinite(annualAverage) ? `<section class="notes-jury-banner ${juryStatus.tone}"><div class="notes-jury-topline">Moyenne annuelle</div><div class="notes-jury-header"><div><h2>${escapeHtml(formatAverage(annualAverage))} / 20</h2><p>${escapeHtml(juryStatus.title)}</p></div><span class="notes-jury-tag">${escapeHtml(juryStatus.tag)}</span></div><p class="notes-jury-detail">${escapeHtml(juryStatus.summary)} ${escapeHtml(juryStatus.detail)}</p></section>` : '';
        content.innerHTML = `${yearSelector}${juryBanner}<section class="notes-overview"><p class="notes-kicker">${parsePeriodType(currentLabel) === 'trimestre' ? 'Trimestre en cours' : 'Semestre en cours'}</p><strong>${escapeHtml(formatAverage(displayAverage))} <span>/ 20</span></strong><p class="notes-overview-message">${escapeHtml(averageMessage(displayAverage))}</p></section>${assignmentPanel}<div class="notes-blocks">${blocks.map(([block, blockSubjects]) => {
            const blockAverage = weightedSubjectAverage(blockSubjects);
            return `<section class="notes-block"><div class="notes-block-head"><div><p class="notes-kicker">Bloc de matières</p><h2>${escapeHtml(block)}</h2></div><div class="notes-block-average"><span>Moyenne du bloc</span><strong>${escapeHtml(formatAverage(blockAverage))}<small>/20</small></strong></div></div><div class="notes-list">${blockSubjects.map(notes => {
                const subjectAverage = subjectScore(notes);
                const subject = notes[0].subject;
                const signal = subjectSignal(subjectAverage, subject);
                return `<article class="matiere-card${signal ? ` ${signal.className}` : ''}"><div class="matiere-head"><div class="matiere-title"><h3>${escapeHtml(subject)}</h3>${signal ? `<span class="matiere-signal ${signal.className}">${escapeHtml(signal.label)}</span><p class="matiere-comment">${escapeHtml(signal.comment)}</p>` : ''}</div><div class="matiere-score"><span>Moyenne matière</span><strong>${escapeHtml(formatAverage(subjectAverage))}<small>/20</small></strong></div></div><div class="evaluations">${notes.map(note => `<div class="evaluation-row${note.value === null || note.value === undefined || note.value === '' ? ' is-empty' : ''}"><span class="evaluation-label">${escapeHtml(note.label)}${note.isPartial ? '<em>Partiel</em>' : ''}</span><strong>${escapeHtml(note.value ?? '—')}</strong></div>`).join('')}</div></article>`;
            }).join('')}</div></section>`;
        }).join('')}${classifiedSubjects.length === 0 ? '' : ''}</div>`;

        const select = content.querySelector('#select-note-year');
        select?.addEventListener('change', event => {
            const year = event.target.value;
            content.dataset.selectedYear = year;
            renderGrades(items);
        });
        content.querySelectorAll('.matiere-block-input').forEach(input => {
            input.addEventListener('change', event => {
                const modal = content.querySelector('.notes-assignments');
                const scrollTop = modal?.scrollTop || 0;
                saveBlock(event.target.dataset.subject, event.target.value.trim());
                renderGrades(items);
                window.requestAnimationFrame(() => {
                    const refreshedModal = content.querySelector('.notes-assignments');
                    if (refreshedModal) refreshedModal.scrollTop = scrollTop;
                });
            });
            input.addEventListener('keydown', event => {
                if (event.key === 'Enter') { event.preventDefault(); event.target.blur(); }
            });
        });
        const modal = content.querySelector('#notes-assignment-modal');
        const closeAssignments = () => {
            content.dataset.assignmentDismissed = 'true';
            modal?.classList.remove('is-open');
            modal?.setAttribute('aria-hidden', 'true');
        };
        const openAssignments = () => {
            content.dataset.assignmentDismissed = 'false';
            modal?.classList.add('is-open');
            modal?.setAttribute('aria-hidden', 'false');
            modal?.querySelector('input')?.focus();
        };
        content.querySelectorAll('[data-close-assignments]').forEach(element => element.addEventListener('click', closeAssignments));
        content.querySelector('[data-open-assignments]')?.addEventListener('click', openAssignments);
        const rulesModal = content.querySelector('#notes-rules-modal');
        const closeRules = () => {
            rulesModal?.classList.remove('is-open');
            rulesModal?.setAttribute('aria-hidden', 'true');
        };
        const openRules = () => {
            rulesModal?.classList.add('is-open');
            rulesModal?.setAttribute('aria-hidden', 'false');
        };
        content.querySelectorAll('[data-close-rules]').forEach(element => element.addEventListener('click', closeRules));
        content.querySelector('[data-open-rules]')?.addEventListener('click', openRules);
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
                const value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
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

    function exportPdf() {
        const { jsPDF } = window.jspdf || {};
        if (!jsPDF) {
            window.print();
            return;
        }
        const date = typeof DATE_AFFICHEE !== 'undefined' ? DATE_AFFICHEE : new Date().toISOString().slice(0, 10);
        const events = (window.DONNEES_EMPLOI_DU_TEMPS && window.DONNEES_EMPLOI_DU_TEMPS[date]) || [];
        const doc = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = 36;
        const lineHeight = 18;
        const maxLines = Math.max(1, Math.floor((pageHeight - 120) / lineHeight));
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(18);
        doc.text('Emploi du temps', margin, 46);
        doc.setFontSize(11);
        doc.text(`Jour : ${date}`, margin, 66);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);

        let y = 86;
        if (!events.length) {
            doc.text('Aucun cours prévu pour cette journée.', margin, y);
        } else {
            const formatted = events.map((item, index) => {
                const entry = normalize(item);
                const start = entry.time.split(/\s+-\s+/)[0] || 'Horaire';
                const end = entry.time.split(/\s+-\s+/)[1] || '';
                const room = entry.room || 'Salle à confirmer';
                const teacher = entry.teacher || 'Intervenant à confirmer';
                return [
                    `${index + 1}. ${entry.title}`,
                    `${start}${end ? ` - ${end}` : ''} • ${room}`,
                    `${entry.type || 'Cours'} • ${teacher}`
                ];
            }).flat();

            for (let index = 0; index < formatted.length; index += 1) {
                const line = formatted[index];
                const wrapped = doc.splitTextToSize(line, pageWidth - margin * 2);
                for (const part of wrapped) {
                    if (y > pageHeight - margin) {
                        doc.addPage();
                        y = margin;
                    }
                    doc.text(part, margin, y);
                    y += lineHeight;
                }
                if (y > pageHeight - margin && index < formatted.length - 1) {
                    doc.addPage();
                    y = margin;
                }
            }
        }

        doc.save(`emploi_du_temps_${date}.pdf`);
    }

    document.getElementById('btn-telecharger-ical')?.addEventListener('click', exportIcal);
    document.getElementById('btn-imprimer-pdf')?.addEventListener('click', exportPdf);
    document.getElementById('btn-ouvrir-selecteur')?.addEventListener('click', () => document.getElementById('modale-jour')?.classList.add('visible'));
    document.querySelectorAll('#btn-fermer-modale-jour, #btn-fermer-modale-jour-bas').forEach(button => button.addEventListener('click', () => document.getElementById('modale-jour')?.classList.remove('visible')));
    document.getElementById('btn-toggle-resume')?.addEventListener('click', event => {
        const details = document.getElementById('details-resume');
        details?.classList.toggle('visible');
        event.currentTarget.classList.toggle('ouvert');
    });
    const absenceModal = document.getElementById('modale-filtre');
    document.getElementById('btn-ouvrir-filtre')?.addEventListener('click', () => absenceModal?.classList.add('visible'));
    document.getElementById('btn-fermer-filtre')?.addEventListener('click', () => absenceModal?.classList.remove('visible'));
    document.getElementById('form-filtre')?.addEventListener('submit', event => {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        absenceFilter = { start: String(form.get('date_debut') || ''), end: String(form.get('date_fin') || ''), type: String(form.get('type') || '') };
        absenceModal?.classList.remove('visible');
        renderAbsences(absenceItems);
    });
    document.getElementById('btn-reinitialiser-filtre')?.addEventListener('click', () => {
        document.getElementById('form-filtre')?.reset();
        absenceFilter = { start: '', end: '', type: '' };
        renderAbsences(absenceItems);
    });

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
    const passwordField = passwordInput?.closest('.password-field');
    passwordToggle?.addEventListener('click', () => {
        const isVisible = passwordInput.type === 'text';
        passwordField?.classList.remove('mot-de-passe-affiche', 'mot-de-passe-cache');
        passwordInput.type = isVisible ? 'password' : 'text';
        void passwordInput.offsetWidth;
        passwordField?.classList.add(isVisible ? 'mot-de-passe-cache' : 'mot-de-passe-affiche');
        passwordToggle.classList.toggle('is-visible', !isVisible);
        passwordToggle.setAttribute('aria-pressed', String(!isVisible));
        passwordToggle.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
    });
    const credentialsInvite = document.getElementById('invite-identifiants');
    const useCredentials = document.getElementById('utiliser-identifiants');
    const declineCredentials = document.getElementById('refuser-identifiants');
    let savedCredential = null;
    const closeCredentialsInvite = () => {
        credentialsInvite?.classList.remove('visible');
        credentialsInvite?.setAttribute('aria-hidden', 'true');
        if (credentialsInvite) credentialsInvite.hidden = true;
        savedCredential = null;
    };
    const findSavedCredential = async () => {
        if (!login || !window.PasswordCredential || !navigator.credentials?.get) return;
        try {
            const credential = await navigator.credentials.get({ password: true, mediation: 'silent' });
            if (!credential || credential.type !== 'password' || !credential.password) return;
            savedCredential = credential;
            if (credentialsInvite) {
                credentialsInvite.hidden = false;
                credentialsInvite.classList.add('visible');
                credentialsInvite.setAttribute('aria-hidden', 'false');
                useCredentials?.focus();
            }
        } catch {}
    };
    useCredentials?.addEventListener('click', () => {
        if (savedCredential) {
            login.identifiant.value = savedCredential.id;
            passwordInput.value = savedCredential.password;
            login.classList.add('identifiants-remplis');
            setTimeout(() => login.classList.remove('identifiants-remplis'), 700);
        }
        closeCredentialsInvite();
    });
    declineCredentials?.addEventListener('click', closeCredentialsInvite);
    credentialsInvite?.addEventListener('click', event => { if (event.target === credentialsInvite) closeCredentialsInvite(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && credentialsInvite?.classList.contains('visible')) closeCredentialsInvite(); });
    findSavedCredential();
    if (login) login.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = login.querySelector('.bouton-connexion');
        const error = document.getElementById('login-error');
        const loginPage = document.querySelector('.page-login');
        const startedAt = performance.now();
        loginPage?.classList.remove('connexion-echec', 'connexion-en-cours');
        loginPage?.classList.add('connexion-verification');
        submit.disabled = true;
        submit.textContent = 'Vérification...';
        if (error) error.hidden = true;
        try {
            const loginPayload = await window.mygesApi.login({ username: login.identifiant.value, password: login.mot_de_passe.value });
            window.mygesStorage.markSession();
            if (loginPayload?.student?.name) updateStudent(loginPayload.student);
            try {
                updateStudent(await window.mygesApi.profile());
            } catch (profileError) {
                if (profileError.status === 401) throw new Error('La session serveur n’a pas pu être confirmée. Veuillez réessayer.');
            }
            if (document.getElementById('souvenir')?.checked && window.PasswordCredential && navigator.credentials?.store) {
                navigator.credentials.store(new PasswordCredential({ id: login.identifiant.value, password: login.mot_de_passe.value })).catch(() => {});
            }
            loginPage?.classList.remove('connexion-verification');
            loginPage?.classList.add('connexion-en-cours', 'connexion-reussie');
            const remaining = 1700 - (performance.now() - startedAt);
            if (remaining > 0) await new Promise(resolve => setTimeout(resolve, remaining));
            window.location.href = 'index.php';
        } catch (requestError) {
            loginPage?.classList.remove('connexion-verification');
            loginPage?.classList.add('connexion-echec');
            if (error) { error.textContent = requestError.message; error.hidden = false; }
            await new Promise(resolve => setTimeout(resolve, 650));
        } finally {
            submit.disabled = false;
            submit.textContent = 'Se connecter';
        }
    });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js?v=2', { scope: './', updateViaCache: 'none' }).catch(() => {});
    }

    const isLogin = Boolean(login);
    if (!isLogin && !window.mygesStorage.hasSession()) { window.location.href = 'login.php'; return; }
    if (!isLogin) {
        loadProfile();
        if (document.querySelector('.page-login')) return;
        if (document.querySelector('.section-accueil')) loadResource('planning', renderHome);
        if (document.getElementById('home-absences')) loadResource('absences', renderHomeAbsences);
        if (document.getElementById('home-averages')) loadResource('grades', renderHomeAverages);
        if (document.querySelector('.nav-jour')) loadResource('planning', renderPlanning);
        if (document.querySelector('.carte-resume')) loadResource('absences', renderAbsences);
        if (document.querySelector('.notes-content')) loadResource('grades', renderGrades);
        if (document.querySelector('.supports-content') && document.querySelector('#supports-list')) {
            loadResource('supports', renderSupports);
            document.getElementById('supports-search-input')?.addEventListener('input', () => {
                const supports = window.supportsData || [];
                renderSupports(supports);
            });
        }
        window.addEventListener('pageshow', event => {
            if (event.persisted && document.querySelector('.nav-jour')) loadResource('planning', renderPlanning);
            if (event.persisted && document.querySelector('.section-accueil')) loadResource('planning', renderHome);
        });
    }
    const logout = async () => { await window.mygesApi.logout().catch(() => {}); window.mygesStorage.clearSession(); window.location.href = 'login.php'; };
    document.getElementById('btn-confirmer-deconnexion')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        const animation = document.getElementById('animation-deconnexion');
        const startedAt = performance.now();
        button.disabled = true;
        appFrame?.classList.add('deconnexion-en-cours');
        animation?.setAttribute('aria-hidden', 'false');
        await window.mygesApi.logout().catch(() => {});
        const remaining = 750 - (performance.now() - startedAt);
        if (remaining > 0) await new Promise(resolve => setTimeout(resolve, remaining));
        window.mygesStorage.clearSession();
        window.location.href = 'login.php';
    });
    document.getElementById('btn-deconnexion')?.addEventListener('click', openLogoutModal);
});
