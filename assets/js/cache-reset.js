(() => {
    const resetMarker = 'myges-cache-reset-v5';
    if (localStorage.getItem(resetMarker) === 'done') return;
    const authenticated = localStorage.getItem('myges-authenticated');
    localStorage.clear();
    if (authenticated === 'true') localStorage.setItem('myges-authenticated', authenticated);
    localStorage.setItem(resetMarker, 'done');
    sessionStorage.clear();
    if ('caches' in window) caches.keys().then(keys => Promise.all(keys.map(key => caches.delete(key)))).catch(() => {});
    if ('serviceWorker' in navigator) navigator.serviceWorker.getRegistrations().then(registrations => registrations.forEach(registration => registration.unregister())).catch(() => {});
})();
