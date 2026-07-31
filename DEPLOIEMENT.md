# Déploiement DJAART SCHOOL sur LWS

Hébergement mutualisé LWS, deux sous-domaines de `djaart.site` :

| Sous-domaine | Rôle | Dossier |
|---|---|---|
| `api-school.djaart.site` | Backend Laravel (API) | `/public_html/api-school` |
| `school.djaart.site` | Frontend React (build statique) | `/public_html/school` |

Base de données déjà créée et importée manuellement par vos soins (`c2816701c_djaart_school`) — aucune migration à rejouer.

## Les 2 archives prêtes à l'emploi

Générées sur `C:\Users\DIGITAL MARKET\Desktop\djaart-school-deploiement\` :

- **`api-school.zip`** → à dézipper directement dans `/public_html/api-school` (le contenu du zip, pas le zip lui-même).
- **`school.zip`** → à dézipper directement dans `/public_html/school`.

Rien d'autre à faire côté build : `composer install --no-dev`, le build frontend (`npm run build`) et le `.env` de production (avec vos identifiants de base de données et l'e-mail `contact@djaart.site`) sont déjà inclus. Pas de commande à lancer sur le serveur.

### Contenu de `api-school.zip`

```
index.php        ← point d'entrée, adapté pour pointer vers djaart-app/
.htaccess        ← règles de réécriture Laravel (routage propre)
favicon.ico
robots.txt
djaart-app/       ← toute l'application Laravel (app, vendor, storage, .env de prod...)
  .htaccess       ← bloque tout accès web direct à ce dossier (défense en profondeur)
```

Le dossier `djaart-app/` n'est **jamais** censé être atteint directement par un navigateur — seul `index.php` à la racine y fait référence en PHP (`__DIR__.'/djaart-app/...'`). C'est ce qui protège `.env`, le code source et la structure de la base d'un accès public, même si votre panneau LWS ne permet pas de choisir un document-root personnalisé.

### Contenu de `school.zip`

Le build React (HTML/CSS/JS, manifest PWA, service worker, icônes) + un `.htaccess` qui redirige toute route inconnue vers `index.html` (nécessaire pour une SPA : sans lui, recharger une page comme `/dashboard` renverrait une erreur 404).

## Avant de dézipper : les prérequis LWS

Dans votre espace client LWS, vérifiez :
- Le sous-domaine `api-school.djaart.site` pointe bien vers `/public_html/api-school`, et `school.djaart.site` vers `/public_html/school`.
- **Certificat SSL activé sur les deux sous-domaines** (Let's Encrypt gratuit, généralement en un clic) — **indispensable**, l'authentification par cookie de session (Sanctum) exige HTTPS en production. Sans lui, la connexion échouera silencieusement.
- Les permissions d'écriture sur `api-school/djaart-app/storage` et `api-school/djaart-app/bootstrap/cache` après upload (l'hébergement mutualisé LWS fait généralement tourner PHP sous votre propre utilisateur, donc les permissions issues d'un dézippage standard suffisent — à vérifier seulement si vous obtenez une erreur 500 après déploiement).

## Étape restante : le lien de stockage public

`php artisan storage:link` (qui permet aux logos, signatures et photos de profil téléversés d'être servis publiquement) n'a **pas** pu être exécuté depuis cet environnement — il nécessite un accès au serveur LWS lui-même.

- **Si votre plan LWS inclut l'accès SSH** : connectez-vous et lancez `php artisan storage:link` depuis `api-school/djaart-app/`. Vous pouvez aussi y créer votre premier compte `super_admin` réel via `php artisan tinker` :
  ```php
  $u = App\Models\User::create(['name' => 'Votre nom', 'email' => 'vous@djaart.site', 'password' => 'un-mot-de-passe-fort']);
  $u->assignRole('super_admin');
  ```
  (si vous n'avez pas déjà un compte `super_admin` dans la base importée).
- **Sans SSH** : créez manuellement, via le gestionnaire de fichiers LWS, un lien symbolique de `public_html/api-school/storage` vers `public_html/api-school/djaart-app/storage/app/public` — si l'outil ne le permet pas, les logos/signatures/photos téléversés resteront inaccessibles publiquement jusqu'à ce que ce lien existe (le reste de l'application fonctionne normalement sans lui).

## Vérification après déploiement

1. `https://school.djaart.site` doit afficher la page de connexion (charte DJAART SCHOOL, pas une erreur).
2. Connectez-vous avec un compte existant de la base importée.
3. Dans les outils de développement du navigateur (onglet Réseau), vérifiez que les appels vers `https://api-school.djaart.site/api/...` renvoient bien `200` — pas d'erreur CORS ni `419` (jeton CSRF expiré/domaine mal configuré).
4. Testez l'envoi d'un e-mail réel (ex. créer un utilisateur) pour confirmer que `contact@djaart.site` fonctionne aussi depuis le serveur LWS — vérifié depuis l'environnement de développement, mais un serveur mutualisé peut avoir des restrictions réseau différentes.
5. Vérifiez le bouton flottant « Installer l'app » sur mobile (Chrome/Android) — nécessite le HTTPS actif pour fonctionner (les PWA exigent un contexte sécurisé).

## À chaque mise à jour du code

Pas de commande serveur à relancer (pas de migrations à rejouer, pas de cache à reconstruire — le `.env` de production ne contient pas de cache figé). Simplement :

1. Frontend : `npm run build` → dézipper le nouveau contenu de `dist/` (+ le `.htaccess` de la SPA, à conserver) par-dessus `/public_html/school`.
2. Backend : re-générer une archive à jour (mêmes étapes que celle-ci : `composer install --no-dev --optimize-autoloader`, conserver le `.env` de production déjà en place plutôt que de l'écraser) et dézipper `djaart-app/` par-dessus — en gardant le `.env` existant sur le serveur si aucune nouvelle variable n'a été ajoutée, ou en le fusionnant manuellement sinon.
3. Si une nouvelle migration existe : à rejouer via SSH (`php artisan migrate --force`) ou manuellement via phpMyAdmin sans SSH.
