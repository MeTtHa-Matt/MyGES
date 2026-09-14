const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' }[character]));

function text(value, fallback = '') {
  if (value === null || value === undefined || value === '') return fallback;
  if (typeof value === 'string' || typeof value === 'number') return String(value);
  return value.name || value.label || value.title || value.value || value.code || fallback;
}

function dateValue(item) { return item.date || item.day || item.startDate || item.start_at || item.start || ''; }
function dateLabel(item) {
  const value = dateValue(item);
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? text(value, 'Date à confirmer') : date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
}
function timeLabel(item) {
  const start = item.startTime || item.start_time || item.start || item.time;
  const end = item.endTime || item.end_time || item.end;
  const format = (value) => {
    const date = new Date(value);
    if (!Number.isNaN(date.getTime()) && (String(value).includes('T') || String(value).includes(':'))) return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    return text(value, '--:--');
  };
  return end ? `${format(start)} - ${format(end)}` : format(start);
}
function roomLabel(item) {
  const location = typeof item.location === 'object' ? item.location : null;
  const room = item.room || item.classroom || item.roomName || item.room_name || location?.room || location?.classroom || item.location;
  const building = item.building || item.buildingName || item.building_name || location?.building || location?.buildingName;
  const roomName = text(room);
  const buildingName = text(building);
  if (roomName && buildingName && roomName !== buildingName) return `${roomName} · ${buildingName}`;
  return roomName || buildingName || 'Salle à confirmer';
}
function courseLabel(item) { return text(item.course || item.courseName || item.course_name || item.subject || item.activity || item.title || item.name || item.label, 'Cours'); }
function normalized(item) {
  return { item, title: courseLabel(item), date: dateLabel(item), time: timeLabel(item), room: roomLabel(item), teacher: text(item.teacher || item.teacherName || item.professor || item.instructor), group: text(item.group || item.className || item.class_name), type: text(item.type || item.courseType || item.kind), description: text(item.description || item.content || item.notes) };
}

export function renderPlanning(items = []) {
  if (!items.length) return '<div class="empty-state">Aucun cours prévu pour cette période.</div>';
  const groups = new Map();
  items.forEach((item, index) => {
    const entry = normalized(item);
    entry.index = index;
    if (!groups.has(entry.date)) groups.set(entry.date, []);
    groups.get(entry.date).push(entry);
  });
  return `<div class="section-heading"><div><p class="eyebrow">Mon agenda</p><h3>Les prochaines séances</h3></div><span class="sync-label">${items.length} cours</span></div><div class="date-strip">${[...groups.keys()].map((date, index) => `<a href="#day-${index}" class="date-chip ${index === 0 ? 'is-current' : ''}">${escapeHtml(date)}</a>`).join('')}</div><div class="schedule-list">${[...groups].map(([date, entries], dayIndex) => `<section class="day-group" id="day-${dayIndex}"><h4>${escapeHtml(date)}</h4>${entries.map((entry) => `<button class="schedule-card" data-event-index="${entry.index}" type="button"><span class="schedule-time">${escapeHtml(entry.time)}</span><span class="schedule-main"><strong>${escapeHtml(entry.title)}</strong><span class="meta">${escapeHtml(entry.room)}</span></span><span class="schedule-chevron" aria-hidden="true">›</span></button>`).join('')}</section>`).join('')}</div>`;
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
  return '<div class="profile-view"><div class="profile-avatar">MG</div><p class="eyebrow">Espace personnel</p><h3>Mon profil</h3><p class="meta">Ta session est conservée de manière sécurisée pendant 30 jours. Tes identifiants ne sont jamais enregistrés dans le navigateur.</p><div class="profile-actions"><button class="profile-action" type="button" disabled><span>◌</span><span><strong>Mode hors-ligne</strong><small>Le cache local est actif</small></span><b>Actif</b></button><button id="profile-logout" class="profile-action is-danger" type="button"><span>↗</span><span><strong>Se déconnecter</strong><small>Supprimer la session actuelle</small></span><b>→</b></button></div></div>';
}
