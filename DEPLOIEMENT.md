# Déploiement DJAART SCHOOL sur LWS

Hébergement mutualisé LWS, deux sous-domaines de `djaart.site` :

| Sous-domaine | Rôle | Dossier |
|---|---|---|
| `api-school.djaart.site` | Backend Laravel (API) | `/public_html/api-school` |
| `school.djaart.site` | Frontend React (build statique) | `/public_html/school` |

Ce document décrit la marche à suivre. Je n'ai pas d'accès direct à votre espace LWS (FTP/SSH/panneau) — c'est donc un guide à suivre vous-même, pas une action que j'ai effectuée.

**Avant de commencer**, dans votre espace client LWS, repérez :
- Vos identifiants **FTP/SFTP** (ou l'accès au **gestionnaire de fichiers** du panneau).
- La rubrique **bases de données MySQL** (création + phpMyAdmin).
- La rubrique **SSH** — certains plans LWS l'incluent, d'autres non. Cela change la méthode pour les étapes 3 et 6 ci-dessous (deux variantes fournies).
- La rubrique **certificat SSL** (Let's Encrypt gratuit, généralement activable en un clic par sous-domaine) — **indispensable**, l'authentification par cookie de session (Sanctum) exige HTTPS en production.

## 1. Base de données

1. Dans le panneau LWS, créez une base MySQL (ex. `djaart_school`) et un utilisateur dédié avec tous les droits dessus. Notez : hôte (souvent `localhost` en mutualisé, parfois un hôte spécifique fourni par LWS), nom de la base, utilisateur, mot de passe.
2. Gardez ces informations sous la main pour l'étape 4 (`.env` de production).

## 2. Préparer le frontend en local

```bash
cd djaart-school-frontend
```

Créez (ou adaptez) un `.env.production` à la racine du frontend :

```
VITE_API_URL=https://api-school.djaart.site
```

Puis :

```bash
npm install
npm run build
```

Cela produit un dossier `dist/` — c'est **son contenu** (pas le dossier lui-même) qu'il faut uploader.

## 3. Uploader le frontend

Via FTP/SFTP ou le gestionnaire de fichiers LWS : uploadez tout le **contenu** de `djaart-school-frontend/dist/` (le fichier `index.html`, le dossier `assets/`, etc.) directement dans `/public_html/school`.

Le frontend est une SPA (React Router) : sans configuration, recharger une page comme `/dashboard` renverrait une erreur 404 (Apache cherche un fichier `dashboard` qui n'existe pas). Créez un fichier `.htaccess` à la racine de `/public_html/school` :

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

## 4. Préparer le backend en local

```bash
cd djaart-school-backend
composer install --no-dev --optimize-autoloader
```

Créez un fichier `.env` de **production** (ne réutilisez pas celui du poste de développement — domaines, base de données et clé d'application doivent différer) :

```
APP_NAME="DJAART SCHOOL"
APP_ENV=production
APP_KEY=                      # généré à l'étape 6 (php artisan key:generate)
APP_DEBUG=false
APP_URL=https://api-school.djaart.site

DB_CONNECTION=mysql
DB_HOST=localhost              # ou l'hôte fourni par LWS
DB_PORT=3306
DB_DATABASE=<nom de la base LWS>
DB_USERNAME=<utilisateur LWS>
DB_PASSWORD=<mot de passe LWS>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true     # HTTPS obligatoire en production
SESSION_DOMAIN=.djaart.site    # le point initial partage le cookie entre les 2 sous-domaines

SANCTUM_STATEFUL_DOMAINS=school.djaart.site
FRONTEND_URL=https://school.djaart.site
CORS_ALLOWED_ORIGINS=https://school.djaart.site

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=mail.djaart.site
MAIL_PORT=465
MAIL_USERNAME=contact@djaart.site
MAIL_PASSWORD="<le mot de passe de la boîte contact@djaart.site>"
MAIL_FROM_ADDRESS="contact@djaart.site"
MAIL_FROM_NAME="${APP_NAME}"

QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

`SESSION_DOMAIN=.djaart.site` (avec le point) est ce qui permet au cookie de session posé par `api-school.djaart.site` d'être lu par les requêtes venant de `school.djaart.site` — sans lui, la connexion échouerait silencieusement en production (frontend et backend sont deux sous-domaines distincts, pas le même hôte comme en local).

## 5. Uploader le backend

**Point de sécurité important** : la racine servie par le sous-domaine (`/public_html/api-school`) ne doit **jamais** être la racine du projet Laravel — sinon `.env`, le code source et la base de données seraient potentiellement accessibles publiquement. Deux façons de faire, selon ce que permet votre panneau LWS :

- **Si LWS permet de choisir un dossier document-root personnalisé pour le sous-domaine** : uploadez tout le projet Laravel dans un dossier *hors* de `public_html` (ex. `/djaart-school-backend`), puis configurez `api-school.djaart.site` pour pointer son document-root vers `/djaart-school-backend/public`. C'est la méthode propre, à privilégier si disponible.
- **Sinon** (document-root imposé à `/public_html/api-school`) : uploadez tout le projet Laravel dans un dossier *hors* `public_html` (ex. `/djaart-laravel`), puis ne copiez que le **contenu** de `djaart-laravel/public/` dans `/public_html/api-school`. Éditez ensuite `public_html/api-school/index.php` pour faire pointer les deux `require` vers le nouvel emplacement :
  ```php
  require __DIR__.'/../djaart-laravel/vendor/autoload.php';
  $app = require_once __DIR__.'/../djaart-laravel/bootstrap/app.php';
  ```
  (adapter le chemin relatif exact selon où `/djaart-laravel` se trouve par rapport à `/public_html/api-school`).

Dans tous les cas : uploadez le dossier `vendor/` généré à l'étape 4 (ne pas oublier, il n'est pas dans le dépôt git) et le fichier `.env` de production préparé à l'étape 4.

## 6. Finaliser côté serveur

**Si votre plan LWS inclut l'accès SSH** (le plus simple) :

```bash
cd <dossier du projet Laravel>
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
php artisan config:cache
php artisan route:cache
```

Ne lancez **pas** les autres seeders (`UserSeeder`, `ParametrageSeeder`, etc.) — ce sont des données de démonstration pour le développement local, pas pour la production. Créez plutôt votre premier compte `super_admin` réel via `php artisan tinker` :

```php
$u = App\Models\User::create(['name' => 'Votre nom', 'email' => 'vous@djaart.site', 'password' => 'un-mot-de-passe-fort']);
$u->assignRole('super_admin');
```

**Si votre plan LWS n'inclut pas SSH** : générez `APP_KEY` en local (`php artisan key:generate --show`, copiez la valeur dans le `.env` de production avant l'upload) ; pour les migrations, exportez le schéma depuis votre base locale (`php artisan schema:dump` ou un export phpMyAdmin de la structure) et importez-le via le phpMyAdmin fourni par LWS — plus laborieux et à refaire à chaque évolution du schéma, donc si votre plan le permet, envisagez de passer à une offre avec SSH pour ce projet.

`php artisan storage:link` crée un lien symbolique — sans SSH, il faut soit le créer manuellement si le gestionnaire de fichiers LWS le permet, soit prévoir que les fichiers déposés (logos, signatures, photos) resteront inaccessibles publiquement tant que ce lien n'existe pas.

## 7. Vérification

1. `https://school.djaart.site` doit afficher la page de connexion.
2. Connexion avec le compte `super_admin` créé à l'étape 6.
3. Contrôlez dans les outils de développement du navigateur (onglet Réseau) que les appels vers `https://api-school.djaart.site/api/...` renvoient bien `200`, pas d'erreur CORS ni `419` (jeton CSRF).
4. Testez l'envoi d'un e-mail réel (ex. créer un utilisateur) pour confirmer que `MAIL_*` fonctionne aussi depuis le serveur LWS (l'envoi a été vérifié depuis l'environnement de développement, mais un serveur mutualisé peut avoir des restrictions réseau différentes — à reconfirmer une fois déployé).

## À chaque mise à jour du code

1. `npm run build` (frontend) → re-uploader le contenu de `dist/`.
2. Backend : uploader les fichiers modifiés + `vendor/` si `composer.json` a changé ; si de nouvelles migrations existent, les rejouer (`php artisan migrate --force` en SSH, ou réappliquer manuellement via phpMyAdmin sans SSH) ; `php artisan config:cache` après tout changement de `.env`.
