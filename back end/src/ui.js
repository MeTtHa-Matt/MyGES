const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));

function text(value, fallback = '') {
  if (value === null || value === undefined || value === '') return fallback;
  if (typeof value === 'string' || typeof value === 'number') return String(value);
  if (Array.isArray(value)) return value.map((entry) => text(entry)).filter(Boolean).join(', ') || fallback;
  return value.name || value.label || value.displayName || value.shortName || value.number || value.identifier || value.title || value.value || value.code || fallback;
}

function nestedValue(value, keys, depth = 0) {
  if (!value || depth > 4 || typeof value !== 'object') return '';
  for (const key of keys) {
    if (value[key] !== undefined && value[key] !== null && value[key] !== '') return value[key];
  }
  for (const child of Object.values(value)) {
    const result = nestedValue(child, keys, depth + 1);
    if (result !== '') return result;
  }
  return '';
}

function allValues(value, depth = 0) {
  if (depth > 5 || value === null || value === undefined) return [];
  if (typeof value !== 'object') return [value];
  return Object.values(value).flatMap((child) => allValues(child, depth + 1));
}

function clockParts(value) {
  if (typeof value !== 'string') return [];
  const matches = value.match(/\b(?:[01]?\d|2[0-3])(?:[:h][0-5]\d)?\b/g) || [];
  return matches.filter((part) => part.includes(':') || part.includes('h'));
}

function dateValue(item) {
  const value = nestedValue(item, ['date', 'day', 'startDate', 'start_date', 'startAt', 'start_at', 'startDateTime', 'start_datetime', 'dateStart', 'date_debut', 'begin', 'start']);
  if (!value || typeof value !== 'object') return value || '';
  return value.date || value.datetime || value.dateTime || value.start || value.value || '';
}
function dateLabel(item) {
  const value = dateValue(item);
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? text(value, 'Date à confirmer') : date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
}
function timeLabel(item) {
  const temporalValue = (value) => {
    if (!value || typeof value !== 'object' || Array.isArray(value)) return value;
    if (value.hour !== undefined || value.hours !== undefined) {
      const hour = Number(value.hour ?? value.hours);
      const minute = Number(value.minute ?? value.minutes ?? 0);
      if (Number.isFinite(hour) && Number.isFinite(minute)) return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    }
    if (value.seconds !== undefined || value.epoch !== undefined || value.timestamp !== undefined) return value.seconds ?? value.epoch ?? value.timestamp;
    return value.time || value.value || value.dateTime || value.datetime || value.at || value.date || value.start || value.end || '';
  };
  let start = temporalValue(nestedValue(item, ['startTime', 'start_time', 'startAt', 'start_at', 'startDateTime', 'start_datetime', 'startDate', 'start_date', 'dateStart', 'date_debut', 'beginTime', 'begin_time', 'beginning', 'beginningTime', 'fromTime', 'from_time', 'begin', 'from', 'start', 'time', 'hourStart', 'startHour', 'start_hour', 'heureDebut', 'heure_debut']));
  let end = temporalValue(nestedValue(item, ['endTime', 'end_time', 'endAt', 'end_at', 'endDateTime', 'end_datetime', 'endDate', 'end_date', 'dateEnd', 'date_fin', 'finishTime', 'finish_time', 'ending', 'endingTime', 'toTime', 'to_time', 'finish', 'to', 'end', 'hourEnd', 'endHour', 'end_hour', 'heureFin', 'heure_fin']));
  if (!start || !end) {
    const clocks = allValues(item).flatMap(clockParts);
    if (!start && clocks[0]) start = clocks[0];
    if (!end && clocks[1]) end = clocks[1];
  }
  const format = (value) => {
    if (typeof value === 'number' || (typeof value === 'string' && /^\d{10,13}$/.test(value))) {
      const date = new Date(Number(value) < 100000000000 ? Number(value) * 1000 : Number(value));
      return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    }
    if (typeof value === 'string' && /^\d{1,2}h\d{0,2}$/.test(value)) return value.replace('h', 'h').replace(/h$/, 'h00');
    const date = new Date(value);
    if (!Number.isNaN(date.getTime()) && (String(value).includes('T') || String(value).includes(':'))) return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    return text(value, 'Horaire à confirmer');
  };
  if (!start && !end) console.warn('[MyGES] Horaire absent dans le cours. Champs reçus :', Object.keys(item));
  return end ? `${format(start)} - ${format(end)}` : format(start);
}
function roomLabel(item) {
  const room = nestedValue(item, ['room', 'rooms', 'roomName', 'room_name', 'roomNumber', 'room_number', 'classroom', 'classrooms', 'classroomName', 'salle', 'salles', 'salleName', 'local', 'location', 'place']);
  const building = nestedValue(item, ['building', 'buildings', 'buildingName', 'building_name', 'buildingLabel', 'batiment', 'batiments', 'batimentName', 'site', 'campus']);
  const roomName = text(room);
  const buildingName = text(building);
  if (roomName && buildingName && roomName !== buildingName) return `${roomName} · ${buildingName}`;
  return roomName || buildingName || 'Salle à confirmer';
}
function courseLabel(item) { return text(item.course || item.courseName || item.course_name || item.subject || item.activity || item.title || item.name || item.label, 'Cours'); }
function normalized(item) {
  return { item, title: courseLabel(item), date: dateLabel(item), time: timeLabel(item), room: roomLabel(item), teacher: text(item.teacher || item.teacherName || item.professor || item.instructor), group: text(item.group || item.className || item.class_name), type: text(item.type || item.courseType || item.kind), description: text(item.description || item.content || item.notes) };
}

function shortDate(value) {
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '' : date.toISOString().slice(0, 10);
}

export function renderHome(items = []) {
  const next = items.slice(0, 3);
  return `<section class="home-section"><div class="section-heading"><h3>Les 3 prochains cours</h3><button class="see-all" data-view="planning" type="button">Tout voir ↗</button></div>${next.length ? `<div class="home-courses">${next.map((item, index) => { const entry = normalized(item); return `<button class="home-course accent-${index % 3}" data-event-index="${index}" type="button"><span class="home-course-time">${escapeHtml(entry.time)}</span><span><strong>${escapeHtml(entry.title)}</strong><small>${escapeHtml(entry.type || 'Cours')} · ${escapeHtml(entry.teacher || 'Intervenant à confirmer')}</small><small>${escapeHtml(entry.room)}</small></span></button>`; }).join('')}</div>` : '<div class="empty-state">Aucun cours aujourd’hui, profitez de votre journée ☕</div>'}</section><section class="home-section compact-section"><div class="section-heading"><h3>Devoirs et rendus</h3><button class="see-all" data-coming="Devoirs et rendus" type="button">Tout voir ↗</button></div><p>Aucun devoir pour les 7 prochains jours.</p></section><section class="home-section compact-section"><div class="section-heading"><h3>Assiduité</h3><button class="see-all" data-view="absences" type="button">Tout voir ↗</button></div><p>Pas de nouvel événement.</p></section><button class="ical-fab" data-export="ical" type="button">.ical</button>`;
}

export function renderPlanning(items = [], selectedDate = '') {
  if (!items.length) return '<div class="empty-state">Aucun cours prévu pour cette période.</div>';
  const dates = [...new Set(items.map((item) => shortDate(dateValue(item))).filter(Boolean))];
  const activeDate = selectedDate || dates[0] || '';
  const visibleItems = activeDate ? items.filter((item) => shortDate(dateValue(item)) === activeDate) : items;
  const displayItems = visibleItems.length ? visibleItems : items;
  const active = new Date(`${activeDate}T12:00:00`);
  const dateText = activeDate && !Number.isNaN(active.getTime()) ? active.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' }) : 'Aujourd’hui';
  const week = activeDate && !Number.isNaN(active.getTime()) ? Math.ceil((((active - new Date(active.getFullYear(), 0, 1)) / 86400000) + new Date(active.getFullYear(), 0, 1).getDay() + 1) / 7) : '';
  if (!displayItems.length) return '<div class="empty-state">Aucun cours aujourd’hui, profitez de votre journée ☕</div>';
  const groups = new Map();
  displayItems.forEach((item, index) => {
    const entry = normalized(item);
    entry.index = index;
    if (!groups.has(entry.date)) groups.set(entry.date, []);
    groups.get(entry.date).push(entry);
  });
  return `<div class="date-picker"><button data-day-shift="-1" type="button" aria-label="Jour précédent">‹</button><button id="date-picker-button" type="button"><strong>${escapeHtml(dateText)}</strong><small>${week ? `Semaine ${week}` : 'Sélectionner un jour'}</small></button><button data-day-shift="1" type="button" aria-label="Jour suivant">›</button></div><div class="schedule-list">${[...groups].map(([date, entries], dayIndex) => `<section class="day-group" id="day-${dayIndex}"><h4>${escapeHtml(date)}</h4>${entries.map((entry) => `<button class="schedule-card" data-event-index="${entry.index}" type="button"><span class="schedule-time">${escapeHtml(entry.time)}</span><span class="schedule-main"><strong>${escapeHtml(entry.title)}</strong><span class="meta">${escapeHtml(entry.room)}</span></span><span class="schedule-chevron" aria-hidden="true">›</span></button>`).join('')}</section>`).join('')}</div><div class="planning-actions"><button data-export="ical" type="button">.ical</button><button data-export="pdf" type="button">PDF ⤓</button></div>`;
}

export function renderEventDetails(item) {
  const entry = normalized(item);
  return `<p class="eyebrow">Détail du cours</p><h2>${escapeHtml(entry.title)}</h2><div class="detail-grid"><div><span>Horaire</span><strong>${escapeHtml(entry.time)}</strong></div><div><span>Lieu</span><strong>${escapeHtml(entry.room)}</strong></div>${entry.teacher ? `<div><span>Intervenant</span><strong>${escapeHtml(entry.teacher)}</strong></div>` : ''}${entry.group ? `<div><span>Groupe</span><strong>${escapeHtml(entry.group)}</strong></div>` : ''}${entry.type ? `<div><span>Type</span><strong>${escapeHtml(entry.type)}</strong></div>` : ''}</div>${entry.description ? `<p class="detail-description">${escapeHtml(entry.description)}</p>` : ''}`;
}

export function renderGrades(items = []) {
  if (!items.length) return '<div class="empty-state">Aucune note disponible pour le moment.</div>';
  const scored = items.map((item) => Number(item.value ?? item.grade)).filter((value) => Number.isFinite(value));
  const average = scored.length ? (scored.reduce((sum, value) => sum + value, 0) / scored.length).toFixed(1) : '—';
  return `<section class="grade-hero"><p class="eyebrow">Semestre en cours</p><div class="average-row"><strong>${escapeHtml(average)}</strong><span>/ 20</span></div><p class="meta">Moyenne provisoire · ${items.length} matières suivies</p></section><div class="section-heading"><div><p class="eyebrow">Résultats</p><h3>Mes matières</h3></div></div><div class="grade-list">${items.map((item) => `<article class="grade-card"><div><h3>${escapeHtml(text(item.subject || item.course || item.course_name || item.name, 'Matière'))}</h3><p class="meta">${escapeHtml(text(item.period || item.semester || item.trimester_name, 'En cours'))}</p></div><strong class="grade-value">${escapeHtml(item.value ?? item.grade ?? item.grades?.join(', ') ?? '—')}</strong></article>`).join('')}</div>`;
}

export function renderAbsences(items = []) {
  if (!items.length) return '<div class="empty-state">Aucune absence signalée.</div>';
  const justified = items.filter((item) => item.justified || String(item.status).toLowerCase().includes('just')).length;
  return `<div class="section-heading"><div><p class="eyebrow">Présence</p><h3>Mes absences</h3></div><span class="sync-label">${items.length} signalées</span></div><div class="stats-row"><div class="stat-card"><strong>${items.length}</strong><span>Total</span></div><div class="stat-card is-good"><strong>${justified}</strong><span>Justifiées</span></div><div class="stat-card is-alert"><strong>${items.length - justified}</strong><span>À traiter</span></div></div><div class="absence-list">${items.map((item) => `<article class="absence-card"><h3>${escapeHtml(courseLabel(item))}</h3><p class="meta">${escapeHtml(text(item.date || item.day, 'Date inconnue'))} · <span class="absence-status">${escapeHtml(text(item.status || (item.justified ? 'Justifiée' : 'À justifier')))}</span></p></article>`).join('')}</div>`;
}

export function renderProfile() {
  return '<div class="profile-view"><div class="profile-avatar">MG</div><p class="eyebrow">Espace personnel</p><h3>Mon profil</h3><p class="meta">Ta session est conservée de manière sécurisée pendant 30 jours. Tes identifiants ne sont jamais enregistrés dans le navigateur.</p><div class="profile-actions"><button class="profile-action" data-coming="Le mode hors-ligne est actif" type="button"><span>◌</span><span><strong>Mode hors-ligne</strong><small>Le cache local est actif</small></span><b>Actif</b></button><button id="profile-logout" class="profile-action is-danger" type="button"><span>↗</span><span><strong>Se déconnecter</strong><small>Supprimer la session actuelle</small></span><b>→</b></button></div></div>';
}
