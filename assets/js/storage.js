const PLANNING_CACHE_PREFIX = 'myges-planning-week-v3-';
const LEGACY_CACHE_PREFIXES = ['myges-snapshot-', 'myges-planning-cache-', 'myges-planning-week-'];
const REMEMBERED_CREDENTIALS_KEY = 'myges-remembered-credentials';

for (let index = localStorage.length - 1; index >= 0; index -= 1) {
    const key = localStorage.key(index);
    if (key && !key.startsWith(PLANNING_CACHE_PREFIX) && LEGACY_CACHE_PREFIXES.some(prefix => key.startsWith(prefix))) localStorage.removeItem(key);
}

window.mygesStorage = {
    savePlanningWeek(weekStart, value) {
        localStorage.setItem(PLANNING_CACHE_PREFIX + weekStart, JSON.stringify({ value, savedAt: Date.now() }));
    },
    readPlanningWeek(weekStart) {
        try {
            return JSON.parse(localStorage.getItem(PLANNING_CACHE_PREFIX + weekStart) || 'null');
        } catch {
            return null;
        }
    },
    saveResource(resource, value) {
        localStorage.setItem(`myges-resource-${resource}`, JSON.stringify({ value, savedAt: Date.now() }));
    },
    readResource(resource) {
        try {
            return JSON.parse(localStorage.getItem(`myges-resource-${resource}`) || 'null');
        } catch {
            return null;
        }
    },
    saveStudent(profile) {
        localStorage.setItem('myges-student', JSON.stringify(profile));
    },
    saveRememberedCredentials(username, password) {
        localStorage.setItem(REMEMBERED_CREDENTIALS_KEY, JSON.stringify({ username, password }));
    },
    readRememberedCredentials() {
        try {
            const credentials = JSON.parse(localStorage.getItem(REMEMBERED_CREDENTIALS_KEY) || 'null');
            return typeof credentials?.username === 'string' && typeof credentials?.password === 'string'
                ? credentials
                : null;
        } catch {
            return null;
        }
    },
    clearRememberedCredentials() {
        localStorage.removeItem(REMEMBERED_CREDENTIALS_KEY);
    },
    readStudent() {
        try {
            return JSON.parse(localStorage.getItem('myges-student') || 'null');
        } catch {
            return null;
        }
    },
    clearSession() {
        localStorage.removeItem('myges-authenticated');
        sessionStorage.removeItem('myges-authenticated');
    },
    hasSession() {
        return localStorage.getItem('myges-authenticated') === 'true' || sessionStorage.getItem('myges-authenticated') === 'true';
    },
    markSession(remember = false) {
        if (remember) {
            localStorage.setItem('myges-authenticated', 'true');
            sessionStorage.setItem('myges-authenticated', 'true');
            return;
        }
        localStorage.removeItem('myges-authenticated');
        sessionStorage.setItem('myges-authenticated', 'true');
    }
};
