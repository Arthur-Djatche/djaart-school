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

## Phase 1 — Authentification & gestion des utilisateurs/rôles
- Statut : **terminée**

### Réalisé
- Backend :
  - Table `etablissements` (minimale : nom, type, sigle) créée en avance de la Phase 2, uniquement comme ancrage FK multi-tenant — voir hypothèse ci-dessous.
  - `etablissement_id` nullable ajouté sur `users` (super_admin sans établissement, les autres rôles rattachés à un établissement).
  - `EtablissementScope` (Global Scope) + trait `BelongsToEtablissement`, réutilisables pour les futurs modèles métier (Phase 2+).
  - `UserController` (index paginé+recherche, store, update, destroy), `UserPolicy` (isolation par établissement, un `admin_etablissement` ne peut ni créer ni promouvoir un `super_admin`), `StoreUserRequest`/`UpdateUserRequest`, `UserResource`.
  - Middlewares Spatie (`role`, `permission`, `role_or_permission`) enregistrés dans `bootstrap/app.php`.
  - Mot de passe oublié : `PasswordResetController` (Laravel natif `Password` facade), URL de réinitialisation redirigée vers le frontend via `ResetPassword::createUrlUsing` dans `AppServiceProvider`.
  - Seeders étendus : établissement de démo "Lycée Démo DJAART" + un utilisateur par rôle restant.
  - Tests Feature : `UserManagementTest` (CRUD, isolation multi-établissement, restrictions de rôle), `PasswordResetTest` — 14/14 tests OK au total (suite complète).
- Frontend :
  - `config/navigation.js` (menu par rôle) + `Sidebar` dynamique, `ProtectedRoute` avec prop `roles` (redirection `/dashboard` si rôle non autorisé).
  - Composants réutilisables `Table` (recherche/tri/pagination) et `Modal`.
  - Pages `ForgotPasswordPage`/`ResetPasswordPage`, module `features/users` (liste + création/édition/suppression via modal).
- Vérification bout-en-bout (Playwright) : connexion successive avec les 6 comptes de démo, menu et accès à `/users` conformes au rôle pour chacun ; CRUD utilisateur complet (créer/modifier/supprimer) ; flux mot de passe oublié → réinitialisation → connexion avec le nouveau mot de passe.

### Bugs corrigés pendant cette phase (à retenir pour la suite)
- **Ne jamais appliquer un Global Scope qui appelle `Auth::user()`/`auth()->check()` sur le modèle `User` lui-même** : résoudre l'utilisateur authentifié interroge ce modèle, ce qui réapplique le scope et provoque une récursion infinie (plantage silencieux du serveur PHP, réponse 500 vide, sans log). `EtablissementScope` reste utilisable sur les futurs modèles métier (Classe, Inscription, Paiement...), mais **pas** sur `User` — le filtrage par établissement y est fait explicitement dans les contrôleurs.
- Middleware Spatie `role:` : plusieurs rôles se séparent avec `|` (ex. `role:super_admin|admin_etablissement`), **pas** avec une virgule (une virgule est interprétée comme un paramètre "guard" additionnel).
- `AuthContext` ne doit pas appeler `/api/me` sur les pages publiques (login, mot de passe oublié, reset) : cet appel touche la session et peut entrer en concurrence avec la requête de connexion sur la même session (pilote `database`), invalidant le jeton CSRF.
- Cohérence de dépaquetage des réponses Axios dans `authApi.js` : toutes les fonctions (`login`, `fetchMe`, ...) doivent unwrap `response.data` de la même façon avant de renvoyer l'enveloppe `{data, message, errors}` à l'appelant — une incohérence ici avait cassé la re-hydratation de session après un rechargement de page.
- Laravel 11+ : la classe `Controller` de base n'inclut plus le trait `AuthorizesRequests` par défaut — nécessaire pour `$this->authorize(...)`.

### Hypothèses / écarts documentés
- Modèle `Etablissement` créé en avance de la Phase 2 (voir ci-dessus), sous forme minimale ; la Phase 2 l'enrichira et ajoutera son écran de gestion CRUD dédié.

### Comptes de démonstration
| Rôle | Email | Mot de passe | Établissement |
|---|---|---|---|
| Super Admin | admin@djaart.school | password | — |
| Admin Établissement | admin_etablissement@djaart.school | password | Lycée Démo DJAART |
| Secrétaire | secretaire@djaart.school | password | Lycée Démo DJAART |
| Comptable | comptable@djaart.school | password | Lycée Démo DJAART |
| Enseignant | enseignant@djaart.school | password | Lycée Démo DJAART |
| Apprenant | apprenant@djaart.school | password | Lycée Démo DJAART |

## Phase 2 — Paramétrage académique
- Statut : **terminée**

### Réalisé
- Backend :
  - `Etablissement` enrichi (`adresse`) ; 5 nouveaux modèles (`AnneeAcademique`, `Filiere`, `Niveau`, `Classe`, `Matiere`), tous rattachés à un `etablissement_id` en dur et utilisant le trait `BelongsToEtablissement` (Global Scope) posé en Phase 1 — première utilisation réelle de cette infrastructure sur des modèles métier (sans le problème de récursion propre à `User`).
  - CRUD complet pour les 6 entités (`app/Http/Controllers/Api/Parametrage/`), avec le même pattern qu'en Phase 1 (Form Requests avec `etablissement_id` auto-assigné pour `admin_etablissement`/exigé pour `super_admin`, Policies via le trait partagé `ChecksEtablissementOwnership`, Resources dédiées).
  - Règles métier appliquées : `Niveau.type_systeme` (`classique`/`lmd`), `Classe` liée à un `Niveau` **et** une `AnneeAcademique` de le **même établissement** (validation croisée), `Filiere.code` unique par établissement (pas globalement), `AnneeAcademique.date_fin` postérieure à `date_debut`, `EtablissementPolicy` réservant `create`/`delete` au `super_admin` uniquement.
  - `ParametrageSeeder` : peuple entièrement le "Lycée Démo DJAART" existant (année, filière, 2 niveaux classiques, classes, matières) et crée **3 nouveaux établissements de démo** (primaire, universitaire LMD, centre de formation), chacun avec son propre `admin_etablissement` — couvrant les 4 types exigés par la section 5 du cahier des charges.
  - Tests Feature : `AcademicStructureTest` (auto-scoping par rôle, rejet cross-établissement, isolation des listes, unicité de code par établissement, validations métier) — 21/21 tests OK au total (suite complète).
- Frontend :
  - Module `features/parametrage/` (etablissements, annees, filieres, niveaux, classes, matieres), chaque page suivant le pattern posé par `features/users/` en Phase 1 (Table + Modal).
  - Nouveau composant `components/ui/Select.jsx` ; `Sidebar`/`config/navigation.js` étendus pour supporter un menu à sous-items ("Paramétrage").
  - Page `Niveaux` imbriquée sous une filière (`/parametrage/filieres/:filiereId/niveaux`) ; les formulaires Classe/Matière chargent dynamiquement la liste des niveaux disponibles.
- Vérification bout-en-bout (Playwright) : connecté en `admin_etablissement` (Lycée Démo DJAART), paramétrage **de zéro** d'une nouvelle filière → niveau → classe → matière ; isolation confirmée (un admin d'un autre établissement ne voit pas ces données) ; `super_admin` voit et gère les 4 établissements de démo.

### Bugs corrigés pendant cette phase
- Les migrations avec noms de table français pluralisés en préfixe (`annees_academiques`) ne correspondent pas à la pluralisation anglaise par défaut d'Eloquent (`Str::plural('AnneeAcademique')` → `annee_academiques`) : `protected $table` explicite nécessaire sur `AnneeAcademique`. Idem pour les FK générées par `foreignId()->constrained()` (deviné à partir du nom de colonne) : `->constrained('annees_academiques')` explicite requis.
- `Route::apiResource('classes', ...)` nomme le paramètre de route `{class}` (singulier anglais valide) au lieu de `{classe}` attendu par le contrôleur/Form Request — nécessite `->parameters(['classes' => 'classe'])`.
- Ordre des migrations : deux fichiers générés avec le même horodatage (`niveaux`/`classes`) peuvent s'exécuter dans le mauvais ordre alphabétique si les FK ne sont pas prises en compte — toujours vérifier/forcer l'ordre quand des dépendances de clé étrangère existent entre tables créées dans la même minute.

### Comptes de démonstration (ajoutés en Phase 2)
| Rôle | Email | Mot de passe | Établissement |
|---|---|---|---|
| Admin Établissement | admin.primaire@djaart.school | password | École Primaire Démo DJAART |
| Admin Établissement | admin.universite@djaart.school | password | Université Démo DJAART (LMD) |
| Admin Établissement | admin.centreformation@djaart.school | password | Centre de Formation Démo DJAART |

## Phases suivantes
Voir `DJAART_SCHOOL_CLAUDE_CODE_BUILD_PLAN.md` section 6 pour le détail des phases 3 à 11. Prochaine étape : **Phase 3 — Frais de scolarité et tranches** (configuration des frais par niveau/année, échéancier de tranches avec contrôle de cohérence).
