# MyGES Campus

Portail étudiant mobile-first pour consulter les informations MyGES/ESGI depuis une interface web installable comme PWA.

L’application permet notamment de consulter :

- l’emploi du temps et les prochains cours ;
- les notes et devoirs ;
- les absences et l’assiduité ;
- les supports de cours et les documents ;
- le profil étudiant ;
- l’emploi du temps exportable au format `.ical`.

Les données sont récupérées auprès de l’API MyGES via un proxy PHP. Le jeton d’accès reste côté serveur, dans la session PHP, et n’est pas exposé au navigateur.

## Aperçu

- Interface en français, pensée pour mobile ;
- installation possible depuis un navigateur compatible PWA ;
- cache local de l’emploi du temps ;
- gestion des sessions, déconnexion et expiration de session ;
- requêtes API avec délai d’expiration et messages d’erreur utilisateur ;
- fallback pour plusieurs formats de réponses MyGES.

## Prérequis

- PHP 8.2 ou supérieur ;
- extension PHP `curl` ;
- sessions PHP activées ;
- accès aux endpoints MyGES/Skolae fournis par l’établissement ;
- HTTPS en production.

Aucun gestionnaire de paquets ni étape de compilation n’est nécessaire pour l’interface principale.

## Installation locale

1. Clonez le dépôt et placez-vous dans son dossier :

   ```bash
   git clone <url-du-depot>
   cd MyGES
   ```

2. Copiez le modèle de configuration :

   ```bash
   cp .env.example .env
   ```

3. Vérifiez les URL et les chemins d’API dans `.env`. Utilisez une valeur longue et aléatoire pour `SESSION_SECRET`.

4. Lancez le serveur PHP de développement :

   ```bash
   php -S 127.0.0.1:8080 -t .
   ```

5. Ouvrez [http://127.0.0.1:8080/login.php](http://127.0.0.1:8080/login.php).

Le Service Worker est utilisable sur `localhost` et en HTTPS. Il ne fonctionnera pas correctement depuis un fichier ouvert directement avec `file://`.

## Configuration

Les variables suivantes sont définies dans `.env` :

```dotenv
MYGES_AUTH_BASE_URL=https://authentication.kordis.fr/oauth
MYGES_API_BASE_URL=https://api.kordis.fr
MYGES_PLANNING_PATH=/me/{year}/agenda
MYGES_GRADES_PATH=/me/{year}/grades
MYGES_ABSENCES_PATH=/me/{year}/absences
MYGES_SUPPORTS_PATH=/me/{year}/courses
MYGES_DOWNLOAD_BASE_URL=https://ges-dl.kordis.fr
MYGES_CLIENT_ID=skolae-app
SESSION_SECRET=une-valeur-longue-et-aleatoire
```

Les chemins exacts et les identifiants client doivent être confirmés par l’intégrateur MyGES/Skolae. Les valeurs présentes dans `.env.example` sont des valeurs de départ, pas des identifiants garantis pour tous les établissements.

Ne versionnez jamais `.env`, un mot de passe, un jeton d’accès ou une clé secrète. En production, préférez des variables d’environnement injectées par PHP-FPM ou par le serveur d’exécution.

## Architecture

```text
.
├── *.php                  # Pages de l’interface étudiante
├── api/index.php          # Authentification, session et proxy MyGES
├── includes/              # Données partagées, en-tête, menu et icônes
├── assets/css/style.css   # Styles de l’interface
├── assets/js/api.js       # Client HTTP côté navigateur
├── assets/js/app.js       # Navigation et logique de l’application
├── assets/js/storage.js   # Cache local de l’emploi du temps
├── assets/js/cache-reset.js
├── manifest.json          # Métadonnées PWA
└── back end/              # Variante PWA autonome documentée séparément
```

Le navigateur appelle uniquement `api/index.php`. Les routes disponibles sont `login`, `logout`, `profile`, `planning`, `grades`, `absences` et `supports`.

## Déploiement

Pour un déploiement réel :

1. servez le projet avec Apache ou Nginx et PHP-FPM ;
2. placez `.env` hors du DocumentRoot ou rendez-le inaccessible publiquement ;
3. activez HTTPS et les cookies sécurisés ;
4. vérifiez que `api/index.php` est interprété par PHP ;
5. bloquez l’accès aux fichiers cachés et aux fichiers de configuration ;
6. utilisez un répertoire de sessions PHP persistant et non public ;
7. testez la connexion, l’expiration de session, le cache hors ligne et la déconnexion.

Exemple de serveur de développement uniquement :

```bash
php -S 0.0.0.0:8080 -t .
```

Le serveur intégré de PHP ne doit pas être utilisé comme serveur de production.

## Vérifications rapides

Vérifier la syntaxe PHP de l’API :

```bash
php -l api/index.php
```

Tester que l’API refuse une requête non authentifiée :

```bash
curl -i 'http://127.0.0.1:8080/api/index.php?resource=profile'
```

La réponse attendue est une erreur HTTP `401` tant qu’aucune session MyGES n’est ouverte.

## Sécurité et limites

- Le projet ne contourne pas l’authentification MyGES : il relaie une authentification autorisée vers le fournisseur configuré.
- Les endpoints MyGES sont externes et peuvent modifier leur contrat ou leurs réponses.
- Ne journalisez jamais les mots de passe, les cookies de session ou les jetons.
- Le cache local peut contenir des informations étudiantes : prévoyez une déconnexion et un effacement des données sur les appareils partagés.
- Utilisez uniquement ce projet avec l’autorisation de l’établissement et du fournisseur de l’API.

## Variante `back end/`

Le dossier [`back end/`](back%20end/) contient une seconde interface PWA, avec son propre `README.md`, son propre proxy et une organisation `src/` distincte. La documentation de ce dossier décrit son installation et son déploiement spécifiques.

## Licence

Aucune licence open source n’est actuellement déclarée dans ce dépôt. Ajoutez un fichier `LICENSE` avant toute redistribution publique.