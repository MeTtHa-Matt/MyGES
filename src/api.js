const REQUEST_TIMEOUT = 12000;
const API_URL = new URL('../api/index.php', import.meta.url);

async function request(path, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT);
  try {
    const url = new URL(API_URL);
    url.searchParams.set('resource', path);
    const response = await fetch(url, {
      ...options,
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      signal: controller.signal
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      const message = [payload.error, payload.diagnostic].filter(Boolean).join(' ');
      const error = new Error(message || 'Le serveur MyGES est indisponible.');
      error.status = response.status;
      throw error;
    }
    return payload;
  } catch (error) {
    if (error.name === 'AbortError') throw new Error('La connexion a dépassé le délai autorisé.');
    throw error;
  } finally {
    clearTimeout(timeout);
  }
}

export const api = {
  login: (credentials) => request('login', { method: 'POST', body: JSON.stringify(credentials) }),
  planning: () => request('planning'),
  grades: () => request('grades'),
  absences: () => request('absences'),
  logout: () => request('logout', { method: 'POST' })
};
