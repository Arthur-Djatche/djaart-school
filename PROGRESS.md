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

## Phase 3 — Frais de scolarité et tranches
- Statut : **terminée**

### Réalisé
- Backend :
  - Modèles `FraisScolarite` (unique par couple `Niveau`+`AnneeAcademique`, conforme à l'UML) et `Tranche`, tous deux avec le trait `BelongsToEtablissement`.
  - `FraisScolariteService` (`app/Services/`) : logique de calcul/transaction extraite du contrôleur (conforme section 7 du cahier des charges) — `createWithTranches` (mode `comptant` = 1 tranche automatique, mode `tranches` = lignes fournies) et `replaceTranches` (remplacement complet, utilisées toutes deux dans une transaction DB).
  - **Validation bloquante** dans `StoreFraisScolariteRequest`/`UpdateFraisScolariteRequest` : la somme des tranches doit égaler le montant total (tolérance d'arrondi 0,01) via une règle `withValidator`/`after` ; contrainte unique `(niveau_id, annee_academique_id)` en base, rejetée proprement en cas de doublon.
  - `FraisScolaritePolicy`, `FraisScolariteResource` (inclut les tranches), `FraisScolariteController`, mêmes patterns d'isolation multi-établissement qu'en Phase 2.
  - `FraisScolariteSeeder` : configure un barème (comptant ou tranches) pour chaque niveau des 4 établissements de démo, démontrant les deux modes.
  - Tests Feature : `FraisScolariteTest` (rejet somme incohérente, mode comptant, mode tranches valide, doublon niveau/année rejeté, isolation, remplacement via update) — 27/27 tests OK au total (suite complète).
- Frontend :
  - `src/api/financeApi.js`, module `features/finance/fraisScolarite/` (`FraisScolaritePage` + `FraisScolariteFormModal`).
  - **Répartition assistée** : bouton "Répartir automatiquement" qui calcule une répartition égale du montant total sur le nombre de tranches choisi, avec échéances mensuelles proposées à partir de la date de début de l'année académique — chaque ligne reste éditable, avec un total calculé en direct et un message d'erreur visuel si la somme ne correspond pas (miroir de la validation bloquante backend).
  - Nouveau groupe de navigation "Finance" (rôles `super_admin`/`admin_etablissement`).
- Vérification bout-en-bout (Playwright) : configuration d'un barème de zéro (nouvelle filière → niveau → frais en tranches via répartition assistée) réussie ; tentative de somme incohérente correctement bloquée avec message clair, sans enregistrement.

### Hypothèses / écarts documentés
- L'alternative A2 de l'UML ("modification d'une grille déjà utilisée par une inscription → nouvelle version plutôt qu'écrasement") ne s'applique pas encore : `Inscription` n'existe pas avant la Phase 4. Pour l'instant, `update` remplace directement les tranches existantes (`FraisScolariteService::replaceTranches`). **À revoir dès la Phase 4** : dès qu'une grille sera référencée par une inscription, protéger sa modification (avertissement ou versionnement) au lieu de l'écraser.

## Phase 4 — Inscriptions
- Statut : **terminée**

### Réalisé
- Backend :
  - Modèles `Apprenant` et `Inscription` (trait `BelongsToEtablissement`). Compteur `next_matricule_sequence` ajouté sur `etablissements` pour générer des matricules uniques et séquentiels (`{SIGLE}-{5 chiffres}`) sans collision sous concurrence (transaction + `lockForUpdate()`).
  - `ApprenantService` (génération de matricule, réutilisation/création d'apprenant) et `InscriptionService` (résolution automatique de la grille de frais à partir du couple niveau/année de la classe choisie, contrôle de capacité de la classe, détection automatique `nouvelle`/`reinscription`, annulation).
  - **Premier rôle opérationnel non-admin** introduit sur des routes protégées : `secretaire` peut désormais créer des inscriptions et consulter apprenants/classes/frais de scolarité (lecture seule sur ces deux derniers, écriture toujours réservée aux admins).
  - Tests Feature : `InscriptionTest` (matricule généré et séquentiel par établissement, grille de frais rattachée automatiquement, classe pleine rejetée, classe sans grille rejetée, isolation, rôle non autorisé rejeté, annulation, réinscription détectée, réutilisation d'apprenant existant) — 36/36 tests OK au total (suite complète).
- Frontend :
  - Module `features/inscriptions/` : liste des inscriptions + formulaire en une modale (recherche d'apprenant existant **ou** création à la volée, sélection de classe, aperçu automatique de l'échéancier de frais avant validation).
  - Nouvel item de navigation "Inscriptions" (rôles `super_admin`, `admin_etablissement`, **`secretaire`**).
- Vérification bout-en-bout (Playwright), connecté en `secretaire` : création d'un nouvel apprenant, inscription dans une classe avec grille de frais, échéancier affiché avant validation, matricule généré visible dans la liste.

### Bugs corrigés pendant cette phase
- **`next_matricule_sequence` absent du `$fillable` d'`Etablissement`** : `update()` l'ignorait silencieusement (protection mass-assignment, sans erreur), donnant toujours la séquence 1 — leçon générale : après l'ajout d'une colonne destinée à être modifiée par l'application (pas seulement en lecture), toujours vérifier qu'elle est bien listée dans `$fillable`, la lecture seule fonctionnant même sans cela.
- Règle de validation Laravel `prohibited_with` inexistante dans cette version : remplacée par une vérification manuelle dans `withValidator`/`after` (mutuelle exclusivité `apprenant_id`/`apprenant`).
- Routes `/classes` et `/frais-scolarite` (lecture) étaient enfermées dans le groupe réservé aux admins, rendant le formulaire d'inscription inutilisable pour `secretaire` : séparation de l'`index` (lecture, ouvert aussi à `secretaire`) des routes d'écriture (toujours admin uniquement), avec mise à jour des policies `viewAny` correspondantes.
- Généralisation du trait `ChecksEtablissementOwnership` : il vérifiait `hasRole('admin_etablissement')` en dur, ce qui aurait bloqué `secretaire` sur les vérifications de propriété d'établissement — remplacé par une simple comparaison d'`etablissement_id` (les méthodes `viewAny`/`create` de chaque policy filtrent déjà les rôles autorisés en amont).
- `components/ui/Modal.jsx` n'avait pas de défilement interne : un formulaire long (comme celui d'inscription) pouvait pousser le bouton de validation hors de l'écran sans moyen d'y accéder — ajout de `max-h-[90vh]` + `overflow-y-auto` sur le contenu, bénéficie à toutes les modales.

### Hypothèses / écarts documentés
- Cycle de vie de l'inscription partiel : seuls `en_cours` (création) et `annulee` (annulation manuelle) sont implémentés. Les transitions `validee` (Phase 5, premier paiement), `suspendue` et `cloturee` (Phase 7, clôture d'année) seront ajoutées avec leurs déclencheurs respectifs.
- Pas de gestion de liste d'attente pour une classe pleine : simple blocage avec message d'erreur.
- Champ `photo` de l'apprenant présent en base mais sans composant de téléversement dans l'UI (prévu pour la Phase 8, carte scolaire).

## Phases suivantes
Voir `DJAART_SCHOOL_CLAUDE_CODE_BUILD_PLAN.md` section 6 pour le détail des phases 5 à 11. Prochaine étape : **Phase 5 — Paiements et reçus** (encaissement de tranche, numérotation sécurisée des reçus, génération PDF, transition de l'inscription vers `validee`).
