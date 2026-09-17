<?php
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/icones.php';
$titre_page = 'Notifications et messages';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#ff555a">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-touch-icon" content="assets/img/favicon.jpeg">
<link rel="icon" href="assets/img/favicon.jpeg" type="image/jpeg">
<link rel="manifest" href="manifest.json">
<title>Notifications et messages — Portail Étudiant</title>
<link rel="stylesheet" href="assets/css/style.css?v=20260917-09">
</head>
<body>
<div class="app-frame">
    <?php require __DIR__ . '/includes/entete.php'; ?>

    <main class="contenu notifications-page">
        <div class="notifications-heading">
            <div>
                <p class="notifications-kicker">Communication</p>
                <h1>Notifications et messages</h1>
            </div>
            <span class="notifications-count" id="notifications-count">Chargement…</span>
        </div>

        <section class="notifications-section" aria-labelledby="alerts-title">
            <div class="notifications-section-heading">
                <div>
                    <p class="notifications-kicker">À surveiller</p>
                    <h2 id="alerts-title">Alertes importantes</h2>
                </div>
            </div>
            <div class="notification-alerts" id="notification-alerts" aria-live="polite">
                <p class="etat-vide-mini">Vérification des notes, documents et planning…</p>
            </div>
        </section>

        <section class="notifications-section" aria-labelledby="messages-title">
            <div class="notifications-section-heading">
                <div>
                    <p class="notifications-kicker">MyGES</p>
                    <h2 id="messages-title">Messages personnels et administratifs</h2>
                </div>
                <a class="notifications-source-link" href="https://myges.fr/common/user-message" target="_blank" rel="noreferrer">MyGES</a>
            </div>
            <div class="message-list" id="message-list" aria-live="polite">
                <p class="etat-vide-mini">Chargement des messages…</p>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/includes/menu_lateral.php'; ?>
    <div class="toast" id="toast"></div>
</div>
<script src="assets/js/cache-reset.js?v=20260917-03"></script>
<script src="assets/js/api.js?v=20260917-04"></script>
<script src="assets/js/storage.js?v=20260917-03"></script>
<script src="assets/js/app.js?v=20260917-03"></script>
<script>
(function () {
    const seenMessagesKey = 'myges-messages-seen-v1';
    const snapshotsKey = 'myges-notification-snapshots-v1';
    const messageList = document.getElementById('message-list');
    const alertList = document.getElementById('notification-alerts');
    const count = document.getElementById('notifications-count');

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[character]));

    const readJson = (key, fallback) => {
        try { return JSON.parse(localStorage.getItem(key) || JSON.stringify(fallback)); } catch { return fallback; }
    };

    const writeJson = (key, value) => localStorage.setItem(key, JSON.stringify(value));

    const fingerprint = value => {
        const source = JSON.stringify(value ?? null);
        let hash = 2166136261;
        for (let index = 0; index < source.length; index += 1) {
            hash ^= source.charCodeAt(index);
            hash = Math.imul(hash, 16777619);
        }
        return (hash >>> 0).toString(16);
    };

    const renderMessages = messages => {
        const seen = readJson(seenMessagesKey, []);
        const unread = messages.filter(message => !seen.includes(message.id));
        count.textContent = unread.length ? `${unread.length} nouveau${unread.length > 1 ? 'x' : ''}` : 'À jour';
        if (!messages.length) {
            messageList.innerHTML = '<p class="etat-vide-mini">Aucun message MyGES disponible.</p>';
            return;
        }
        messageList.innerHTML = messages.map(message => `
            <article class="message-card ${seen.includes(message.id) ? '' : 'is-unread'}">
                <div class="message-card-top">
                    <span class="message-badge">${seen.includes(message.id) ? 'Message' : 'Nouveau'}</span>
                    <time>${escapeHtml(message.sentAt || 'Date inconnue')}</time>
                </div>
                <h3>${escapeHtml(message.subject || 'Message MyGES')}</h3>
                <p class="message-author">De <strong>${escapeHtml(message.author || 'MyGES')}</strong></p>
                <div class="message-actions">
                    <a class="message-action-primary" href="${escapeHtml(message.url || 'https://myges.fr/common/user-message')}" target="_blank" rel="noreferrer">Lire sur MyGES</a>
                    <button type="button" class="message-action-secondary" data-message-id="${escapeHtml(message.id)}">Marquer comme lu</button>
                </div>
            </article>
        `).join('');
        messageList.querySelectorAll('[data-message-id]').forEach(button => {
            button.addEventListener('click', () => {
                const nextSeen = readJson(seenMessagesKey, []);
                if (!nextSeen.includes(button.dataset.messageId)) nextSeen.push(button.dataset.messageId);
                writeJson(seenMessagesKey, nextSeen);
                renderMessages(messages);
            });
        });
    };

    const renderAlerts = results => {
        const snapshots = readJson(snapshotsKey, {});
        const alerts = [];
        const definitions = [
            { key: 'grades', label: 'Notes', href: 'notes.php', icon: 'Résultats' },
            { key: 'documents', label: 'Documents', href: 'documents.php', icon: 'Administration' },
            { key: 'planning', label: 'Emploi du temps', href: 'emploi_du_temps.php', icon: 'Planning' }
        ];
        definitions.forEach(definition => {
            const result = results[definition.key];
            if (result.status !== 'fulfilled') return;
            const nextFingerprint = fingerprint(result.value);
            if (snapshots[definition.key] && snapshots[definition.key] !== nextFingerprint) {
                alerts.push({ ...definition, text: `Une modification a été détectée dans ${definition.label.toLowerCase()}.` });
            }
            snapshots[definition.key] = nextFingerprint;
        });
        writeJson(snapshotsKey, snapshots);
        if (!alerts.length) {
            alertList.innerHTML = '<p class="etat-vide-mini">Aucune nouvelle alerte détectée.</p>';
            return;
        }
        alertList.innerHTML = alerts.map(alert => `
            <a class="notification-alert" href="${alert.href}">
                <span class="notification-alert-icon">${escapeHtml(alert.icon)}</span>
                <span><strong>${escapeHtml(alert.label)}</strong><small>${escapeHtml(alert.text)}</small></span>
            </a>
        `).join('');
    };

    (async () => {
        try {
            const [messagesResult, gradesResult, documentsResult, planningResult] = await Promise.allSettled([
                window.mygesApi.messages(),
                window.mygesApi.grades(),
                window.mygesApi.documents(),
                window.mygesApi.planning()
            ]);
            if (messagesResult.status === 'fulfilled') {
                renderMessages(Array.isArray(messagesResult.value.messages) ? messagesResult.value.messages : []);
            } else {
                messageList.innerHTML = '<p class="etat-vide-mini">Les messages MyGES sont indisponibles pour le moment.</p>';
                count.textContent = 'Indisponible';
            }
            renderAlerts({ grades: gradesResult, documents: documentsResult, planning: planningResult });
        } catch (error) {
            messageList.innerHTML = `<p class="etat-vide-mini">${escapeHtml(error.message || 'Les notifications sont indisponibles.')}</p>`;
            count.textContent = 'Indisponible';
            alertList.innerHTML = '<p class="etat-vide-mini">Les alertes sont indisponibles pour le moment.</p>';
        }
    })();
})();
</script>
</body>
</html>
