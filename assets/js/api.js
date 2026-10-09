const REQUEST_TIMEOUT = 12000;
const API_URL = new URL('api/index.php', document.baseURI);
let rememberedLoginPromise = null;

function reauthenticateRememberedUser() {
    if (localStorage.getItem('myges-authenticated') !== 'true' || !navigator.credentials?.get) {
        return Promise.resolve(false);
    }
    if (!rememberedLoginPromise) {
        rememberedLoginPromise = (async () => {
            try {
                const credential = await navigator.credentials.get({ password: true, mediation: 'silent' });
                if (!credential || credential.type !== 'password' || !credential.password) return false;
                await request('login', {
                    method: 'POST',
                    body: JSON.stringify({ username: credential.id, password: credential.password })
                }, false);
                window.mygesStorage?.markSession(true);
                return true;
            } catch {
                return false;
            }
        })().finally(() => { rememberedLoginPromise = null; });
    }
    return rememberedLoginPromise;
}

async function request(resource, options = {}, retryAfterReauthentication = true) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), options.timeout ?? REQUEST_TIMEOUT);
    try {
        const url = new URL(API_URL);
        url.searchParams.set('resource', resource);
        Object.entries(options.query || {}).forEach(([key, value]) => url.searchParams.set(key, value));
        const response = await fetch(url, {
            credentials: 'include',
            ...options,
            cache: 'no-store',
            headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
            signal: controller.signal
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            if (response.status === 401 && resource !== 'login' && resource !== 'logout') {
                if (retryAfterReauthentication && await reauthenticateRememberedUser()) {
                    return request(resource, options, false);
                }
                window.dispatchEvent(new Event('myges:session-expired'));
            }
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
    years: () => request('years'),
    classes: year => request('classes', { query: year ? { year } : {} }),
    news: () => request('news', { timeout: 30000 }),
    messages: () => request('messages', { timeout: 30000 }),
    events: () => request('events', { timeout: 30000 }),
    event: id => request('event', { query: id ? { id } : {}, timeout: 30000 }),
    calendar: url => request('calendar', { query: { calendarId: url }, timeout: 30000 }),
    projects: year => request('projects', { query: year ? { year } : {}, timeout: 30000 }),
    planning: (date, scope = 'week') => request('planning', { query: date ? { date, scope } : { scope } }),
    grades: () => request('grades', { timeout: 90000 }),
    absences: () => request('absences', { timeout: 90000 }),
    supports: () => request('supports'),
    documents: () => request('documents', { timeout: 30000 }),
    logout: () => request('logout', { method: 'POST' })
};
