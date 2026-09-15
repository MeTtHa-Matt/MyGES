import { api } from './api.js';
import { clearSession, hasSession, markSession, readSnapshot, saveSnapshot } from './storage.js';
import { renderAbsences, renderEventDetails, renderGrades, renderHome, renderPlanning, renderProfile } from './ui.js';

const views = { home: renderHome, planning: renderPlanning, grades: renderGrades, absences: renderAbsences, profile: () => renderProfile() };
const state = { activeView: 'home', data: {}, selectedDate: '' };
const loginView = document.querySelector('#login-view');
const dashboardView = document.querySelector('#dashboard-view');
const contentView = document.querySelector('#content-view');
const bottomNav = document.querySelector('#bottom-nav');
const banner = document.querySelector('#connection-banner');
const syncLabel = document.querySelector('#last-sync');
const pageTitles = { home: 'Accueil', planning: 'Emploi du temps', grades: 'Notes', absences: 'Relevé des absences et retards', profile: 'Profil' };

function showDashboard() {
  loginView.hidden = true;
  dashboardView.hidden = false;
  bottomNav.hidden = false;
  document.querySelector('#logout-button').hidden = false;
}

function showLogin() {
  loginView.hidden = false;
  dashboardView.hidden = true;
  bottomNav.hidden = true;
  document.querySelector('#logout-button').hidden = true;
}

function setBanner(message = '') {
  banner.textContent = message;
  banner.hidden = !message;
}

function updateStudentName(profile) {
  const user = profile?.result || profile?.data || profile || {};
  const name = user.name || [user.firstname || user.firstName, user.lastname || user.lastName].filter(Boolean).join(' ') || user.fullName || user.username || 'Étudiant MyGES';
  const initials = name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'MG';
  document.querySelectorAll('#header-student-name, #drawer-student-name').forEach((element) => { element.textContent = name; });
  document.querySelectorAll('#student-avatar, .drawer-head .avatar').forEach((element) => { element.textContent = initials; });
}

function renderActiveView() {
  const data = state.activeView === 'home' ? state.data.planning || [] : state.data[state.activeView] || [];
  contentView.innerHTML = state.activeView === 'planning' ? views.planning(data, state.selectedDate) : views[state.activeView](data);
  document.querySelector('#page-title').textContent = pageTitles[state.activeView];
  document.querySelector('#page-kicker').textContent = state.activeView === 'home' ? 'Mon espace étudiant' : 'MyNotes · ESGI';
  document.querySelectorAll('.nav-item').forEach((item) => item.classList.toggle('is-active', item.dataset.view === state.activeView));
}

async function loadView(viewName, { allowCache = true } = {}) {
  if (viewName === 'profile' || viewName === 'home') {
    renderActiveView();
    if (viewName === 'home' && !state.data.planning) await loadView('planning');
    return;
  }
  const snapshotKey = `snapshot-${viewName}`;
  try {
    const freshData = await api[viewName]();
    state.data[viewName] = Array.isArray(freshData.data) ? freshData.data : freshData;
    await saveSnapshot(snapshotKey, state.data[viewName]);
    syncLabel.textContent = `Synchronisé à ${new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
    setBanner(navigator.onLine ? '' : 'Mode hors-ligne : données en cache.');
  } catch (error) {
    if (error.status === 401) {
      clearSession();
      showLogin();
      return;
    }
    const cached = allowCache ? await readSnapshot(snapshotKey) : null;
    if (cached) {
      state.data[viewName] = cached.value;
      syncLabel.textContent = `Cache du ${new Date(cached.savedAt).toLocaleDateString('fr-FR')}`;
      setBanner('Mode hors-ligne : affichage des dernières données connues.');
    } else {
      state.data[viewName] = [];
      setBanner(error.message);
    }
  }
  renderActiveView();
}

async function loadProfile() {
  try { const profile = await api.profile(); updateStudentName(profile); } catch (error) { if (error.status === 401) { clearSession(); showLogin(); } }
}

document.querySelector('#login-form').addEventListener('submit', async (event) => {
  event.preventDefault();
  const form = new FormData(event.currentTarget);
  const errorElement = document.querySelector('#login-error');
  const submit = event.currentTarget.querySelector('button');
  submit.disabled = true;
  submit.textContent = 'Connexion…';
  errorElement.hidden = true;
  try {
    const result = await api.login({ username: form.get('username'), password: form.get('password') });
    markSession();
    updateStudentName(result.student || { username: form.get('username') });
    showDashboard();
    await loadProfile();
    await loadView('planning');
  } catch (error) {
    errorElement.textContent = error.message;
    errorElement.hidden = false;
  } finally {
    submit.disabled = false;
    submit.innerHTML = 'Ouvrir mon espace <span>→</span>';
  }
});

document.querySelectorAll('.nav-item').forEach((item) => item.addEventListener('click', async () => {
  state.activeView = item.dataset.view;
  renderActiveView();
  await loadView(state.activeView);
}));

function openDrawer() { document.querySelector('#side-drawer').classList.add('is-open'); document.querySelector('#drawer-backdrop').hidden = false; document.querySelector('#side-drawer').setAttribute('aria-hidden', 'false'); }
function closeDrawer() { document.querySelector('#side-drawer').classList.remove('is-open'); document.querySelector('#drawer-backdrop').hidden = true; document.querySelector('#side-drawer').setAttribute('aria-hidden', 'true'); }
document.querySelector('#menu-button').addEventListener('click', openDrawer);
document.querySelector('#close-menu').addEventListener('click', closeDrawer);
document.querySelector('#drawer-backdrop').addEventListener('click', closeDrawer);
document.querySelector('#home-button').addEventListener('click', async () => { state.activeView = 'home'; closeDrawer(); await loadView('home'); });
document.querySelectorAll('[data-drawer-view]').forEach((item) => item.addEventListener('click', async () => { state.activeView = item.dataset.drawerView; closeDrawer(); await loadView(state.activeView); }));

contentView.addEventListener('click', (event) => {
  const coming = event.target.closest('[data-coming]');
  if (coming) { setBanner(`${coming.dataset.coming} sera disponible lorsque cette donnée sera fournie par MyGES.`); return; }
  if (event.target.closest('#profile-logout')) {
    document.querySelector('#logout-button').click();
    return;
  }
  const viewButton = event.target.closest('[data-view]');
  if (viewButton) { state.activeView = viewButton.dataset.view; loadView(state.activeView); return; }
  const dayShift = event.target.closest('[data-day-shift]');
  if (dayShift) { const current = new Date(`${state.selectedDate || new Date().toISOString().slice(0, 10)}T12:00:00`); current.setDate(current.getDate() + Number(dayShift.dataset.dayShift)); state.selectedDate = current.toISOString().slice(0, 10); renderActiveView(); return; }
  if (event.target.closest('#date-picker-button')) { openDatePicker(); return; }
  const exportButton = event.target.closest('[data-export]');
  if (exportButton) { exportButton.dataset.export === 'pdf' ? window.print() : downloadIcal(); return; }
  const card = event.target.closest('[data-event-index]');
  if (!card || state.activeView !== 'planning') return;
  const item = state.data.planning?.[Number(card.dataset.eventIndex)];
  if (!item) return;
  document.querySelector('#event-details').innerHTML = renderEventDetails(item);
  document.querySelector('#event-dialog').showModal();
});

function openDatePicker() { const dialog = document.querySelector('#date-dialog'); document.querySelector('#date-input').value = state.selectedDate || new Date().toISOString().slice(0, 10); dialog.showModal(); }
document.querySelector('#close-date-dialog').addEventListener('click', () => document.querySelector('#date-dialog').close());
document.querySelector('#apply-date').addEventListener('click', () => { state.selectedDate = document.querySelector('#date-input').value; document.querySelector('#date-dialog').close(); renderActiveView(); });
function downloadIcal() {
  const events = (state.data.planning || []).map((item) => `BEGIN:VEVENT\nSUMMARY:${item.title || item.course || 'Cours'}\nDESCRIPTION:${item.description || ''}\nEND:VEVENT`).join('\n');
  const blob = new Blob([`BEGIN:VCALENDAR\nVERSION:2.0\n${events}\nEND:VCALENDAR`], { type: 'text/calendar' });
  const link = document.createElement('a'); link.href = URL.createObjectURL(blob); link.download = 'myges-planning.ics'; link.click(); URL.revokeObjectURL(link.href);
}

document.querySelector('#close-dialog').addEventListener('click', () => document.querySelector('#event-dialog').close());
document.querySelector('#event-dialog').addEventListener('click', (event) => {
  if (event.target === event.currentTarget) event.currentTarget.close();
});

document.querySelector('#logout-button').addEventListener('click', async () => {
  await api.logout().catch(() => {});
  clearSession();
  state.data = {};
  showLogin();
});

window.addEventListener('online', () => setBanner('Connexion rétablie.')); 
window.addEventListener('offline', () => setBanner('Mode hors-ligne : les prochaines données viendront du cache.'));

if ('serviceWorker' in navigator) {
  const serviceWorkerUrl = new URL('../sw.js?v=10', import.meta.url);
  navigator.serviceWorker.register(serviceWorkerUrl).catch(() => {});
}
if (hasSession()) { showDashboard(); loadProfile(); loadView('home'); } else showLogin();
