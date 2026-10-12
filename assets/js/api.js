const REQUEST_TIMEOUT = 12000;
const API_URL = new URL('api/index.php', document.baseURI);
let rememberedLoginPromise = null;

function setConnectionStatus(status) {
    window.dispatchEvent(new CustomEvent('myges:connection-status', { detail: { status } }));
}

function reauthenticateRememberedUser() {
    if (localStorage.getItem('myges-authenticated') !== 'true') {
        return Promise.resolve(false);
    }
    if (!rememberedLoginPromise) {
        rememberedLoginPromise = (async () => {
            let credentials = window.mygesStorage?.readRememberedCredentials();
            try {
                if (!credentials && navigator.credentials?.get) {
                    const credential = await navigator.credentials.get({ password: true, mediation: 'silent' });
                    if (credential?.type === 'password' && credential.password) {
                        credentials = { username: credential.id, password: credential.password };
                    }
                }
                if (!credentials?.username || !credentials.password) return false;
                await request('login', {
                    method: 'POST',
                    body: JSON.stringify(credentials)
                }, false);
                window.mygesStorage?.markSession(true);
                return true;
            } catch (error) {
                if (credentials && [401, 403].includes(error.status)) {
                    window.mygesStorage?.clearRememberedCredentials();
                    window.mygesStorage?.clearSession();
                }
                return false;
            }
        })().finally(() => { rememberedLoginPromise = null; });
    }
    return rememberedLoginPromise;
}

async function request(resource, options = {}, retryAfterReauthentication = true) {
    const tracksConnection = !['calendar', 'logout', 'news'].includes(resource);
    if (tracksConnection) setConnectionStatus('connecting');
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
            if (response.status === 401 && tracksConnection && resource !== 'login') {
                if (retryAfterReauthentication && await reauthenticateRememberedUser()) {
                    return request(resource, options, false);
                }
                window.dispatchEvent(new Event('myges:session-expired'));
            }
            const error = new Error([payload.error, payload.diagnostic].filter(Boolean).join(' ') || 'Le serveur MyGES est indisponible.');
            error.status = response.status;
            if (tracksConnection) setConnectionStatus('disconnected');
            throw error;
        }
        if (tracksConnection) setConnectionStatus('connected');
        return payload;
    } catch (error) {
        if (tracksConnection) setConnectionStatus('disconnected');
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
