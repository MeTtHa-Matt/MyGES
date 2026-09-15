const PLANNING_CACHE_PREFIX = 'myges-planning-week-v3-';
const LEGACY_CACHE_PREFIXES = ['myges-snapshot-', 'myges-planning-cache-', 'myges-planning-week-'];

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
    readStudent() {
        try {
            return JSON.parse(localStorage.getItem('myges-student') || 'null');
        } catch {
            return null;
        }
    },
    clearSession() {
        localStorage.removeItem('myges-authenticated');
    },
    hasSession() {
        return localStorage.getItem('myges-authenticated') === 'true';
    },
    markSession() {
        localStorage.setItem('myges-authenticated', 'true');
    }
};
