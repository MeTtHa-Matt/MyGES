import { api } from './api.js';
import { clearSession, hasSession, markSession, readSnapshot, saveSnapshot } from './storage.js';
import { renderAbsences, renderEventDetails, renderGrades, renderPlanning, renderProfile } from './ui.js';

const views = { planning: renderPlanning, grades: renderGrades, absences: renderAbsences, profile: () => renderProfile() };
const state = { activeView: 'planning', data: {} };
const loginView = document.querySelector('#login-view');
const dashboardView = document.querySelector('#dashboard-view');
const contentView = document.querySelector('#content-view');
const bottomNav = document.querySelector('#bottom-nav');
const banner = document.querySelector('#connection-banner');
const syncLabel = document.querySelector('#last-sync');

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

function renderActiveView() {
  contentView.innerHTML = views[state.activeView](state.data[state.activeView] || []);
  document.querySelectorAll('.nav-item').forEach((item) => item.classList.toggle('is-active', item.dataset.view === state.activeView));
}

async function loadView(viewName, { allowCache = true } = {}) {
  if (viewName === 'profile') {
    renderActiveView();
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
    document.querySelector('#student-name').textContent = result.student?.name || 'Mon campus';
    showDashboard();
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

contentView.addEventListener('click', (event) => {
  if (event.target.closest('#profile-logout')) {
    document.querySelector('#logout-button').click();
    return;
  }
  const card = event.target.closest('[data-event-index]');
  if (!card || state.activeView !== 'planning') return;
  const item = state.data.planning?.[Number(card.dataset.eventIndex)];
  if (!item) return;
  document.querySelector('#event-details').innerHTML = renderEventDetails(item);
  document.querySelector('#event-dialog').showModal();
});

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
  const serviceWorkerUrl = new URL('../sw.js', import.meta.url);
  navigator.serviceWorker.register(serviceWorkerUrl).catch(() => {});
}
if (hasSession()) { showDashboard(); loadView('planning'); } else showLogin();
