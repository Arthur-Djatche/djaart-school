# PROGRESS — DJAART SCHOOL

## Phase 0 — Initialisation du projet
- Statut : **terminée**

### Réalisé
- Dépôt GitHub créé (`Arthur-Djatche/djaart-school`, public), git local initialisé, push quotidien en place.
- Backend `djaart-school-backend/` : Laravel 12, Sanctum (auth SPA par cookie de session), Spatie Laravel-Permission (rôles), barryvdh/laravel-dompdf (prêt pour les phases de génération PDF).
  - `.env` configuré pour MySQL (`djaart_school`), CORS restreint au frontend, `bootstrap/app.php` avec `statefulApi()`.
  - Structure de dossiers posée : `Controllers/Api/{Auth,Parametrage,Finance,Inscription,Pedagogie,Documents}`, `Services/`, `Policies/`, `Requests/`, `Resources/`.
  - Auth minimale : `AuthController` (login/logout/me) + `LoginRequest`, réponses API standardisées (`data`/`message`/`errors`) via le trait `ApiResponse`, throttling sur `/login`.
  - Rôles de base créés (`super_admin`, `admin_etablissement`, `secretaire`, `comptable`, `enseignant`, `apprenant`) via `RoleSeeder`.
  - Utilisateur de test : `admin@djaart.school` / `password` (rôle `super_admin`) via `UserSeeder`.
  - Tests Feature (`AuthTest`, PHPUnit) : login valide/invalide, `/me` protégé, `/me` authentifié — 4/4 OK.
- Frontend `djaart-school-frontend/` : React + Vite + Tailwind v4, charte DJAART SCHOOL appliquée (logo + palette).
  - Structure : `api/`, `components/{ui,layout}`, `features/{auth,dashboard,parametrage,inscriptions,finance,pedagogie,documents}`, `hooks/`, `context/`, `routes/`, `utils/`.
  - `AuthContext` + `ToastContext`, `axiosClient` (cookies de session + XSRF), page de connexion, `DashboardLayout` (Topbar/Sidebar vide), routes protégées.
- Vérification bout-en-bout effectuée dans un vrai navigateur (Playwright) : connexion → dashboard de bienvenue → déconnexion → retour login, sans erreur bloquante.

### Hypothèses / écarts documentés par rapport au texte littéral du cahier des charges
- **Laravel 12** au lieu de "11.x" — version stable actuelle au moment du développement (juillet 2026), validé avec l'utilisateur.
- **Tailwind v4** au lieu de v3/`tailwind.config.js` — palette de marque déclarée via `@theme` dans `src/index.css`, pas de fichier `tailwind.config.js`. Validé avec l'utilisateur.
- **spatie/laravel-permission v6.x** (au lieu du dernier v8) — contrainte technique : l'environnement XAMPP local tourne en PHP 8.2.12, or spatie/laravel-permission v7 et v8 exigent PHP 8.3+. La v6.25 supporte Laravel 8→13 et PHP 8.0+, donc pleinement compatible avec Laravel 12 + PHP 8.2. Si PHP est mis à niveau vers 8.3+ dans XAMPP, une montée de version du package sera possible.
- **Authentification Sanctum en mode SPA (cookie/session)**, conforme à la mention `SANCTUM_STATEFUL_DOMAINS`/`SESSION_DOMAIN` de la section 3 du cahier des charges.
- Frontend et backend doivent être servis via le même nom d'hôte (`localhost`, pas `127.0.0.1`) pour que les cookies de session/CSRF Sanctum soient acceptés par le navigateur — `VITE_API_URL=http://localhost:8000` et `APP_URL=http://localhost:8000`.
- Extensions PHP `gd` et `zip` activées dans `C:\xampp\php\php.ini` (nécessaires pour dompdf/QR codes et la fiabilité de Composer).

### Comptes de démonstration
| Rôle | Email | Mot de passe |
|---|---|---|
| Super Admin | admin@djaart.school | password |

## Phases suivantes
Voir `DJAART_SCHOOL_CLAUDE_CODE_BUILD_PLAN.md` section 6 pour le détail des phases 1 à 11. Prochaine étape : **Phase 1 — Authentification & gestion des utilisateurs/rôles** (CRUD comptes, garde de routes par rôle, sidebar dynamique par rôle).
