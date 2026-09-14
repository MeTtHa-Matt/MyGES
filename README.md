# MyGES Campus

PWA mobile-first pour consulter le planning, les notes et les absences ESGI. Le front est statique et sans build ; le proxy PHP garde le jeton MyGES en session serveur.

## Arborescence

```text
.
├── api/index.php       # Proxy PHP, session et appels MyGES
├── src/app.js          # État de l'application et navigation
├── src/api.js          # Client HTTP avec timeout
├── src/storage.js      # Cache IndexedDB
├── src/ui.js           # Rendu HTML échappé
├── index.html
├── styles.css
├── manifest.json
├── sw.js
├── icons/icon.svg
└── .env.example
```

## Pré-requis

- PHP 8.2+ avec extensions `curl` et `session`
- Apache avec `mod_rewrite` facultatif, ou Nginx
- Un accès HTTPS en production
- Les URL et le contrat exacts de l'API MyGES/Skolae fournis par l'établissement

## Configuration locale

1. Copiez `.env.example` en `.env` hors du dossier public, ou exportez ses variables dans l'environnement PHP-FPM/Apache.
2. Remplacez `MYGES_API_BASE_URL` et les quatre chemins par les endpoints autorisés par votre intégrateur.
3. Lancez un serveur de développement :

```bash
php -S 127.0.0.1:8080 -t .
```

Le navigateur doit ouvrir `http://127.0.0.1:8080`. Les Service Workers fonctionnent sur `localhost` et en HTTPS, pas sur un domaine HTTP ordinaire.

## Déploiement production

### 1. Préparer le proxy

Installez PHP-FPM, cURL et un serveur web. Placez le projet dans `/var/www/myges-campus` et donnez au processus PHP un répertoire de sessions persistant et non accessible publiquement. Ne placez jamais `.env` dans un répertoire servi par le serveur.

Avec Apache, activez `ssl`, `headers` et `proxy_fcgi`, puis faites pointer le DocumentRoot vers le projet. Avec Nginx, utilisez PHP-FPM pour `/api/index.php` et servez les fichiers statiques directement. Ajoutez une règle qui refuse les fichiers cachés et `.env`.

### 2. Définir les variables

Définissez au niveau du service PHP-FPM ou du serveur :

```text
MYGES_AUTH_BASE_URL=https://authentication.kordis.fr/oauth
MYGES_API_BASE_URL=https://api.kordis.fr
MYGES_PLANNING_PATH=/me/agenda
MYGES_GRADES_PATH=/me/{year}/grades
MYGES_ABSENCES_PATH=/me/{year}/absences
MYGES_CLIENT_ID=skolae-app
SESSION_SECRET=<valeur aléatoire longue>
Le proxy suppose une réponse de connexion JSON contenant `access_token` (ou `token`) et des réponses JSON pour les données. Pour OAuth, `MYGES_CLIENT_ID` est envoyé dans la requête et, si renseigné, `MYGES_CLIENT_SECRET` est également envoyé en authentification Basic. Ces valeurs doivent être celles fournies par l’intégrateur MyGES/Skolae : `skolae-app` est seulement une valeur par défaut de développement et peut répondre `invalid_client`. Les listes peuvent être renvoyées directement ou sous la clé `data`. Les noms de champs d’affichage sont tolérants (`title/course`, `room/location`, `value/grade`) et peuvent être adaptés dans `src/ui.js` au contrat officiel.
SESSION_SECRET=<valeur aléatoire longue>
```

Le code accepte les variables d'environnement système. Si votre hébergeur ne les transmet pas à PHP-FPM, utilisez un fichier de configuration hors DocumentRoot et adaptez `envValue`; ne committez jamais les identifiants, tokens ou secrets.

### 3. Configurer HTTPS

Créez un DNS `campus.example.fr` vers le serveur, puis obtenez un certificat Let's Encrypt :

```bash
sudo certbot --nginx -d campus.example.fr
# ou : sudo certbot --apache -d campus.example.fr
```

Redirigez tout le trafic HTTP vers HTTPS. Le cookie de session est alors `Secure`, le proxy reste same-origin et le Service Worker est autorisé par le navigateur. Renouvelez le certificat automatiquement avec le timer Certbot.

### 4. Publier le front

Copiez `index.html`, `styles.css`, `manifest.json`, `sw.js`, `icons/` et `src/` dans le DocumentRoot. Vérifiez que `/api/index.php?resource=planning` est routé vers PHP et que `/sw.js` est servi avec le type JavaScript et depuis la racine. Le Service Worker ne contrôle que son propre dossier et ses sous-chemins.

L’interface charge actuellement Tailwind via `https://cdn.tailwindcss.com` et conserve un fallback CSS local. Pour une production stricte ou un fonctionnement hors ligne dès le premier chargement, installez Tailwind avec npm, compilez les classes dans un fichier CSS local, puis remplacez la balise CDN par ce fichier compilé. Le CDN est pratique pour tester mais ne doit pas être la seule ressource de style d’une PWA hors ligne.

### 5. Vérifier en production

- Ouvrez DevTools > Application : manifeste valide, Service Worker actif, cache rempli.
- Testez une connexion avec un compte de test, puis vérifiez que le navigateur ne voit jamais `access_token`.
- Coupez le réseau après une synchronisation : les dernières données doivent rester visibles avec le bandeau cache.
- Testez identifiants erronés, timeout API, session expirée et déconnexion.
- Surveillez les logs PHP sans y écrire de mot de passe ni de jeton.

## Contrat API attendu

Le proxy utilise le flux public observé par le client open source `tchenu/myges` : appel de `/oauth/authorize` avec `client_id=skolae-app`, identifiants MyGES en Basic, puis récupération du token dans le fragment de redirection. Les données sont ensuite lues sur `api.kordis.fr`. Ce flux est non officiel et peut changer ; il ne contourne pas l’authentification. Les listes peuvent être renvoyées directement ou sous `result`/`data`.

Le projet ne contourne pas l'authentification MyGES et ne doit être utilisé qu'avec l'autorisation de l'ESGI et les endpoints documentés par son fournisseur.
