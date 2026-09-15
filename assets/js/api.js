const REQUEST_TIMEOUT = 12000;
const API_URL = new URL('api/index.php', document.baseURI);

async function request(resource, options = {}) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), options.timeout ?? REQUEST_TIMEOUT);
    try {
        const url = new URL(API_URL);
        url.searchParams.set('resource', resource);
        Object.entries(options.query || {}).forEach(([key, value]) => url.searchParams.set(key, value));
        url.searchParams.set('_fresh', `${Date.now()}-${Math.random().toString(36).slice(2)}`);
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            cache: 'no-store',
            headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
            signal: controller.signal
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error([payload.error, payload.diagnostic].filter(Boolean).join(' ') || 'Le serveur MyGES est indisponible.');
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

window.mygesApi = {
    login: credentials => request('login', { method: 'POST', body: JSON.stringify(credentials) }),
    profile: () => request('profile'),
    planning: date => request('planning', { query: date ? { date } : {} }),
    grades: () => request('grades'),
    absences: () => request('absences'),
    supports: () => request('supports'),
    logout: () => request('logout', { method: 'POST' })
};
