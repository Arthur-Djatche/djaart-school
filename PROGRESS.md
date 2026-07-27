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

## Phase 5 — Paiements et reçus
- Statut : **terminée**

### Réalisé
- Backend :
  - Modèles `Paiement` et `Recu` (trait `BelongsToEtablissement`). Compteur `next_recu_sequence` ajouté sur `etablissements`, réutilisant le pattern atomique posé en Phase 4 (`next_matricule_sequence`) pour numéroter les reçus sans collision sous concurrence.
  - `RecuService::genererPour()` : transaction + `lockForUpdate()` sur l'établissement pour le numéro de reçu, génération PDF via `barryvdh/laravel-dompdf` (`resources/views/pdf/recu.blade.php`, charte DJAART SCHOOL appliquée), stockage sur `Storage::disk('local')` sous `recus/{etablissement_id}/{numero}.pdf`.
  - `PaiementService::encaisser()` : verrouille la tranche et l'inscription concernées, calcule le solde dû (somme des paiements déjà enregistrés **pour cette inscription précise**, cf. bug architectural ci-dessous), rejette un paiement sur tranche déjà soldée ou un montant dépassant le solde dû, délègue la génération du reçu, puis fait passer l'inscription de `en_cours` à `validee` dès que la 1ère tranche est intégralement réglée.
  - **Premier rôle opérationnel `comptable`** introduit sur des routes protégées : encaissement (`POST /paiements`), historique (`GET /paiements`), téléchargement de reçu (`GET /recus/{recu}/telecharger`), et lecture de `/apprenants` (recherche) + nouvel endpoint `GET /apprenants/{apprenant}/echeancier` (échéancier détaillé par inscription, avec statut de tranche calculé à la volée : `payee`/`partielle`/`en_attente`/`en_retard`).
  - **Garde-fou financier ajouté** : `FraisScolariteService::replaceTranches()` et `FraisScolariteController::destroy()` rejettent désormais toute modification/suppression d'une grille de frais dont au moins une tranche a déjà un paiement enregistré (`ValidationException` explicite) — résout concrètement l'hypothèse documentée en Phase 3/4 (UML A2) maintenant que des paiements réels peuvent exister.
  - `PaiementSeeder` (après `InscriptionSeeder`) : encaisse la 1ère tranche d'Aïcha Traoré intégralement (démontre la transition `en_cours` → `validee` + reçu n°1), et encaisse partiellement la tranche unique de Moussa Koné (démontre le statut `partielle` + reçu n°2).
  - Tests Feature : `PaiementTest` (paiement total → reçu généré, numéroté séquentiellement par établissement, inscription validée ; paiement partiel → statut `partielle` et solde correct via l'échéancier ; montant dépassant le solde rejeté ; paiement sur tranche déjà soldée rejeté ; rôle non autorisé rejeté ; isolation multi-établissement à la création **et** au téléchargement du reçu) — 45/45 tests OK au total (suite complète).
- Frontend :
  - `src/api/paiementApi.js` (échéancier, paiements, URL de téléchargement de reçu) ; `config/navigation.js`/`AppRoutes.jsx` étendus (groupe "Finance" gagne un lien "Caisse", rôles élargis à `comptable`, filtrage par rôle désormais possible au niveau de chaque sous-lien).
  - Module `features/finance/caisse/` (`CaissePage` + `PaiementFormModal`) : recherche d'apprenant (réutilise le pattern de `InscriptionFormModal`), échéancier par inscription avec badges visuels (teal=payée, orange=partielle, gris=en attente, rouge=en retard), encaissement en modale, lien de téléchargement immédiat du reçu après paiement, historique des paiements avec lien de reçu par ligne.
- Vérification bout-en-bout (Playwright), connecté en `comptable` : recherche d'un apprenant, échéancier affiché, encaissement du solde restant d'une tranche partiellement payée (statut → `payée`), téléchargement réel du reçu PDF via un vrai clic navigateur (fichier non vide, en-tête `application/pdf` confirmé), blocage vérifié pour un montant dépassant le solde dû (message clair, aucun enregistrement).

### Bugs corrigés pendant cette phase
- **Bug architectural (auto-détecté avant tout test)** : une `Tranche` est une ligne de grille **partagée** par toutes les inscriptions d'un même niveau+année (via `FraisScolarite`), pas un enregistrement par apprenant. Une première ébauche calculait `montant_payé`/`solde`/`statut` comme des accesseurs sur `Tranche` (somme de tous les paiements liés à la tranche, tous apprenants confondus) — un paiement d'un élève aurait compté dans le solde d'un autre élève partageant la même tranche. Corrigé avant tout test : accesseurs retirés de `Tranche` (ne garde qu'une relation `paiements()` documentée comme "ne pas utiliser pour calculer un solde individuel"), ajout de `Inscription::paiements()`, et tout le calcul de statut déplacé dans `ApprenantController::echeancier()`, explicitement scopé à une inscription à la fois.
- Même bug dans la première ébauche de `PaiementService` : tentative de déduire l'inscription à partir de la tranche via une relation `FraisScolarite::inscriptions()` inexistante. Corrigé en exigeant `inscription_id` explicitement dans la charge utile du paiement (`StorePaiementRequest`), avec validation croisée que la tranche appartient bien à la grille de frais de cette inscription.
- **Bug d'infrastructure (non lié au métier, découvert pendant la vérification navigateur du téléchargement de reçu)** : le lien de téléchargement utilisait `rel="noreferrer"` (réflexe sécurité habituel sur les liens `target="_blank"`), ce qui supprime l'en-tête `Referer` — or `Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful` s'appuie sur cet en-tête pour reconnaître une requête comme provenant du frontend SPA et activer l'authentification par cookie de session. Sans lui, la requête était traitée comme non authentifiée. Corrigé en remplaçant par `rel="noopener"` (protège contre le tabnabbing sans supprimer le Referer) sur les deux liens de téléchargement de `CaissePage`.
- **Bug de robustesse découvert en creusant le bug précédent** : quelle qu'en soit la cause, toute requête `/api/*` non authentifiée et dépourvue d'en-tête `Accept: application/json` (navigation directe, lien copié/collé, session expirée puis lien de reçu réouvert) faisait planter le backend en 500 (`Route [login] not defined`) au lieu de renvoyer un 401 propre — ce backend est 100% API/SPA et n'a jamais défini de route nommée `login`. Corrigé dans `bootstrap/app.php` : `Middleware::redirectGuestsTo(fn () => null)` (empêche l'appel à `route('login')` au moment de la construction de l'exception dans le middleware `Authenticate`) + un renderable `AuthenticationException` dédié qui renvoie systématiquement un JSON 401 pour les chemins `/api/*`. Traité comme un correctif de fiabilité générale (pas spécifique aux reçus) compte tenu de la priorité absolue du projet sur la fiabilité des données financières.

### Hypothèses / écarts documentés
- **Alternative A2 de l'UML** ("montant > solde dû → système propose d'affecter le surplus à la tranche suivante") **non implémentée** : un paiement dépassant le solde restant de la tranche choisie est bloqué avec un message clair : le comptable doit saisir un montant ≤ solde dû ou encaisser la tranche suivante séparément. Répartition automatique du surplus déférée à une itération future si besoin.
- Le statut d'une tranche (`en_attente`/`partielle`/`payee`/`en_retard`) n'est **pas stocké** en base (pas de colonne `statut` sur `tranches`) : il est calculé à la volée par inscription (montant payé cumulé vs `montant`, `date_echeance` vs aujourd'hui) — évite tout risque de désynchronisation, cohérent avec le diagramme d'état de l'analyse UML.

## Correctif post-Phase 5 — Frais d'inscription et accès comptable aux inscriptions
- Statut : **terminé**

### Contexte
Retour utilisateur après la Phase 5 : le comptable doit pouvoir lui-même créer une inscription (cas d'usage : un parent se présente directement à la caisse), mais celle-ci ne doit être validée qu'après encaissement d'un montant de **frais d'inscription**, paramétrable, inclus dans la pension (pas un supplément). Ce montant remplace la règle provisoire posée en Phase 5 ("validée dès que la 1ère tranche est intégralement soldée").

### Réalisé
- Backend :
  - Nouveau champ `frais_inscription` sur `frais_scolarite` (paramétré par l'admin en même temps que `montant_total`/les tranches, requis, `<= montant_total`).
  - `PaiementService::validerInscriptionSiFraisInscriptionCouverts()` remplace l'ancienne règle basée sur la tranche n°1 : l'inscription passe à `validee` dès que le **cumul des paiements de l'apprenant sur cette inscription** atteint le montant des frais d'inscription — quelle que soit la tranche sur laquelle les paiements ont été enregistrés, même si aucune tranche n'est encore intégralement soldée.
  - Rôle `comptable` ajouté à `InscriptionPolicy` (`viewAny`/`create`) et à la route `POST /inscriptions` (le comptable peut désormais créer, lister et annuler des inscriptions, au même titre que la secrétaire).
  - Reçu PDF enrichi : ligne "Apprenant" remplacée par des lignes distinctes "Nom"/"Prénom"/"Matricule", ajout de la ligne "Année académique".
  - `FraisScolariteSeeder`/`PaiementSeeder` mis à jour pour démontrer concrètement la nouvelle règle (ex. Aïcha Traoré : paiement de 30 000 sur une tranche de 50 000, avec des frais d'inscription à 25 000 → inscription validée alors que la tranche reste "partielle").
  - Tests Feature : nouveau test dédié (`test_paiement_couvrant_les_frais_dinscription_valide_sans_solder_la_tranche`), `test_comptable_can_create_inscription`, `test_comptable_can_list_classes`, `test_comptable_can_list_frais_scolarite`, `test_frais_inscription_superieur_au_montant_total_est_rejete` — 50/50 tests OK au total (suite complète).
- Frontend :
  - `FraisScolariteFormModal`/`FraisScolaritePage` : champ et colonne "Frais d'inscription", avec message explicatif.
  - `InscriptionFormModal` : l'aperçu de l'échéancier affiche désormais le montant des frais d'inscription à encaisser pour valider l'inscription.
  - `comptable` ajouté aux rôles autorisés pour le module Inscriptions (navigation + routes protégées).
- Vérification bout-en-bout (Playwright) : connecté en `comptable`, création complète d'une inscription (apprenant + classe), aperçu affichant bien les frais d'inscription ; connecté en `admin_etablissement`, colonne "Frais d'inscription" visible dans la liste des grilles de frais.

### Bug corrigé pendant cette vérification (non détecté par la suite PHPUnit existante)
- En ouvrant le formulaire d'inscription en tant que `comptable` dans un vrai navigateur, la liste des classes restait vide malgré l'ajout du rôle à la route `GET /classes` : `ClassePolicy::viewAny()` et `FraisScolaritePolicy::viewAny()` vérifiaient encore une liste de rôles codée en dur n'incluant pas `comptable` (le contrôleur appelle `$this->authorize('viewAny', ...)` **en plus** du middleware de route — un même gap avait déjà été corrigé pour `ApprenantPolicy` en Phase 5, mais ces deux policies-ci avaient été oubliées). Corrigé en ajoutant `comptable` aux deux policies ; deux tests dédiés (`test_comptable_can_list_classes`, `test_comptable_can_list_frais_scolarite`) ajoutés pour que ce type de régression soit désormais couvert par la suite automatisée et non plus seulement détectable en navigateur.

## Phase 6 — Pédagogie : affectations et saisie des notes
- Statut : **terminée**

### Réalisé
- Backend :
  - Modèles `Sequence` et `Semestre` (paramétrés par niveau + année académique, exactement comme `FraisScolarite`), `AffectationEnseignant` (classe + matière + enseignant + année, unique par classe+matière+année), `Note` (`sequence_id`/`semestre_id` mutuellement exclusifs selon `Niveau.type_systeme`, `type_evaluation` `sequence`/`cc`/`session_normale`, `valeur` nullable + `absent`, `soumis_le` pour le verrouillage).
  - `NoteService::soumettre()` : transaction qui rejette tout lot déjà verrouillé (message clair), sinon crée/`updateOrCreate` une `Note` par apprenant et verrouille tout le lot en une fois (`soumis_le = now()`) — un seul geste "remplir puis soumettre", sans sauvegarde brouillon intermédiaire (MVP). `NoteService::deverrouiller()` : action admin dédiée qui remet `soumis_le` à `null` pour reprendre la saisie.
  - **Premier usage fonctionnel réel du rôle `enseignant`** (jusqu'ici un simple compte de démo) : peut lister ses propres affectations (`GET /affectations` auto-scopé par `enseignant_id` côté contrôleur), consulter/soumettre les notes de ses affectations uniquement (`AffectationEnseignantPolicy::voirNotes`/`soumettreNotes`, comparaison directe `enseignant_id === user->id`), lire séquences/semestres en lecture seule.
  - Champs `ponderation_cc`/`ponderation_session_normale` ajoutés à `Matiere` (somme = 100, validée), configurables dès cette phase mais **non utilisés pour un calcul** avant la Phase 7 (bulletins/relevés).
  - `PedagogieSeeder` : découpe chaque grille de frais déjà seedée (source des couples niveau+année réellement utilisés) en 3 séquences (niveaux classiques) ou 2 semestres (niveaux LMD) ; crée une affectation de démo (`enseignant@djaart.school` sur Mathématiques / 6ème A, Lycée Démo DJAART).
  - Tests Feature : `AffectationTest` (création admin, enseignant d'un autre établissement/sans le rôle rejeté, matière hors niveau de la classe rejetée, unicité classe+matière+année, enseignant ne voit que ses propres affectations, secrétaire sans accès), `SequenceTest`/`SemestreTest` (CRUD admin, unicité par niveau+année+numéro, isolation, lecture enseignant), `NoteTest` (soumission verrouille, re-soumission bloquée tant que non déverrouillée, enseignant non titulaire rejeté, déverrouillage admin puis nouvelle soumission acceptée, note "absent" sans valeur, CC et Session Normale = verrous indépendants sur le même semestre, rôle non listé rejeté) — 71/71 tests OK au total (suite complète).
- Frontend :
  - `src/api/pedagogieApi.js` ; nouveau groupe de navigation "Pédagogie" avec accès différencié par sous-lien (Affectations/Séquences/Semestres = admins, Saisie des notes = + `enseignant`), même principe que le groupe "Finance".
  - Écrans admin `AffectationsPage`/`SequencesPage`/`SemestresPage` (mêmes patterns Table/Modal que le reste du paramétrage) ; `MatiereFormModal` étendu avec les deux champs de pondération CC/SN.
  - `SaisieNotesPage` (enseignant) : sélection d'une affectation puis, selon le type de système du niveau, d'une séquence (classique) ou d'un semestre + type d'évaluation CC/SN (LMD) ; grille apprenant × note (+ case "Absent") ; une fois verrouillée, la grille repasse en lecture seule avec message explicite.
- Vérification bout-en-bout (Playwright) : connecté `admin_etablissement`, affectation et séquence de démo visibles ; connecté `enseignant@djaart.school`, saisie d'une note pour l'unique apprenant de "6ème A" → soumission → verrouillage confirmé (grille désactivée, bouton "Soumettre" disparu) ; déverrouillage admin confirmé (`soumis_le` repassé à `null` en base) ; tentative de soumission par un utilisateur non titulaire de l'affectation rejetée (403).

### Hypothèses / écarts documentés
- **Séquence**/**Semestre** configurés par niveau + année académique (comme `FraisScolarite`), pas au niveau de l'établissement entier — permet à chaque niveau d'avoir son propre découpage si besoin, cohérent avec le reste du système.
- **Absence** : stockée comme un booléen `absent` (valeur nulle) plutôt que forcée à 0 — le traitement dans le calcul de moyenne (0 ou neutralisée) est différé à la Phase 7, qui ne fait pas encore partie du périmètre ici (Phase 6 = saisie et verrouillage uniquement, pas de calcul de moyenne/bulletin).
- Hors périmètre explicite (alternatives UML différées) : blocage automatique de la saisie Session Normale pour "assiduité insuffisante" (aucun suivi d'assiduité n'existe dans le projet) ; calcul de moyenne UE/matière, rang, mention — tout cela est Phase 7.

## Phase 7 — Bulletins et relevés de notes
- Statut : **terminée**

### Réalisé
- Backend :
  - Modèles `Bulletin` (`inscription_id`, `sequence_id`, `moyenne_generale`, `rang`, `fichier_pdf`, unique par inscription+séquence) et `ReleveDeNotes` (`inscription_id`, `semestre_id` nullable — null pour le relevé annuel classique — `moyenne_generale`, `mention`, `fichier_pdf`).
  - `BulletinService::cloturerSequence(classe, sequence)` : génère **en un seul lot** les bulletins de toute la classe (le rang exige de comparer tous les apprenants actifs entre eux) — vérifie que toutes les matières affectées ont une saisie **verrouillée** couvrant tous les apprenants actifs (sinon bloque avec la liste des matières incomplètes) ; moyenne pondérée par coefficient (note "Absent" = 0, résout l'hypothèse différée en Phase 6) ; rang par classement de compétition (ex æquo = même rang) ; PDF par apprenant (même pattern dompdf que `RecuService`) ; bloque toute re-clôture si des bulletins existent déjà.
  - `ReleveService::genererAnnuelClassique(classe)` : vérifie qu'un bulletin existe pour **chaque séquence** du niveau/année avant de générer (moyenne = moyenne des moyennes de séquence, mention selon barème classique). `ReleveService::genererSemestrielLmd(classe, semestre)` : vérifie que chaque UE a des notes **CC et Session Normale** verrouillées ; moyenne UE = pondération `ponderation_cc`/`ponderation_session_normale` posée en Phase 6 ; moyenne générale pondérée par `credits_ects` ; mention Admis/Ajourné.
  - `BulletinPolicy`/`ReleveDeNotesPolicy` (`super_admin`, `admin_etablissement`, `secretaire` — la secrétaire déclenche la clôture comme le prévoit l'UML) ; routes imbriquées `POST /classes/{classe}/sequences/{sequence}/cloturer`, `POST /classes/{classe}/releves/annuel`, `POST /classes/{classe}/semestres/{semestre}/releves`.
  - Tests Feature : `BulletinTest` (clôture bloquée si notes manquantes avec message nommant la matière, moyenne pondérée et rang corrects sur 2 apprenants, re-clôture bloquée, note "Absent" comptée comme 0, rôle enseignant/autre établissement rejeté), `ReleveTest` (relevé classique bloqué si séquence sans bulletin, mention correcte, relevé LMD bloqué si CC/SN manquant, moyenne pondérée par crédits ECTS et mention Admis/Ajourné correctes, secrétaire autorisée, comptable rejeté) — 83/83 tests OK au total (suite complète).
- Frontend :
  - `src/api/bulletinsApi.js` ; groupe "Pédagogie" étendu avec "Bulletins" et "Relevés de notes" (rôles `super_admin`/`admin_etablissement`/`secretaire`).
  - `BulletinsPage` : classe → séquence → "Clôturer la séquence" → tableau des bulletins (moyenne, rang, téléchargement). `RelevesPage` : classe → (si niveau LMD) semestre → génération en un clic pour toute la classe → tableau des relevés (moyenne, mention, téléchargement).
- Vérification bout-en-bout (Playwright) : `enseignant@djaart.school` saisit et verrouille les 3 séquences de "6ème A" ; `admin_etablissement` clôture chacune (bulletins générés, moyenne/rang corrects), re-clôture bloquée avec message clair, téléchargement réel du bulletin PDF via un vrai clic navigateur (fichier non vide) ; génération du relevé annuel réussie une fois les 3 séquences clôturées, moyenne et mention correctes.

### Bug corrigé pendant cette vérification
- **Convention de nommage des policies** : `ReleveDeNotesPolicy` avait été nommée `RelevePolicy` — Laravel 11 résout les policies par correspondance exacte `{NomDuModèle}Policy` (ici `ReleveDeNotes` → `ReleveDeNotesPolicy`), sans quoi `$this->authorize(..., ReleveDeNotes::class)` échoue silencieusement et refuse l'accès à **tout le monde**, y compris les admins — masqué en tests unitaires jusqu'à ce que `ReleveTest` échoue sur des cas pourtant correctement autorisés. Corrigé en renommant fichier et classe pour respecter la convention.
- **Grille de saisie non réinitialisée en changeant de séquence** (`SaisieNotesPage.jsx`) : `chargerGrille()` ne vidait pas l'ancien état `grille` avant de récupérer les notes de la séquence nouvellement sélectionnée, laissant transitoirement affichée (et verrouillée) la grille de la séquence précédente. Corrigé en réinitialisant `grille` à `null` au début du chargement (affiche "Chargement…" pendant la requête) — découvert en testant la saisie successive de plusieurs séquences dans la même session navigateur.

### Hypothèses / écarts documentés
- **Barème de mention non chiffré dans le cahier des charges** (choix assumé) : classique — Excellent ≥16, Bien ≥14, Assez Bien ≥12, Passable ≥10, Insuffisant <10 ; LMD — Admis si moyenne générale ≥10, sinon Ajourné (pas de session de rattrapage, déjà différée en Phase 6).
- **Génération en lot par classe** (pas apprenant par apprenant) pour bulletins et relevés — le rang et la vérification de complétude s'évaluent naturellement à l'échelle de la classe.
- **Portail apprenant/parent hors périmètre** : consultation réservée à secrétariat/admin (aucune authentification apprenant/parent n'existe dans ce projet, cf. même remarque en Phase 5 pour les reçus).

## Phase 8 — Effets académiques (attestations et cartes)
- Statut : **terminée**

### Réalisé
- Backend :
  - Dépendance `endroid/qr-code` ajoutée (génération de QR en PNG via le backend GD déjà activé depuis la Phase 0), sans conflit avec la contrainte PHP 8.2 du projet.
  - Modèles `Attestation` (`type` scolarité/fréquentation/réussite, `numero` séquentiel par établissement) et `CarteScolaire` (`numero` séquentiel + `numero_duplicata` par apprenant, `date_emission`/`date_expiration`).
  - `AttestationService::generer()` et `CarteScolaireService::generer()` : vérifient qu'une inscription `validee` existe pour l'apprenant sur l'année académique `en_cours` de son établissement (sinon bloquent) ; réutilisent le pattern de compteur atomique déjà posé en Phases 4/5/7 (`next_attestation_sequence`, `next_carte_sequence`) ; génèrent un PDF (dompdf, même charte DJAART SCHOOL) avec un QR code auto-porteur (`QrCodeService`, encode les données d'identification du document en texte, pas une URL de vérification publique).
  - `CarteScolaireService` bloque en plus si l'apprenant n'a pas de photo, et calcule `numero_duplicata` (0 pour la première carte, incrémenté à chaque réémission) tout en donnant systématiquement un `numero` séquentiel neuf.
  - **Premier upload de fichier du projet** : `ApprenantController::updatePhoto()` (disque `public`, `php artisan storage:link` exécuté une fois pour exposer les fichiers) — remplace l'ancienne photo si une nouvelle est téléversée.
  - `ApprenantPolicy::gererDocuments()` (nouvelle capacité) : secrétariat/admins uniquement (pas le comptable, hors de son ressort financier).
  - Tests Feature : `AttestationTest` (blocage si inscription non validée, numérotation séquentielle, téléchargement PDF, rôles enseignant/comptable rejetés, isolation multi-établissement), `CarteScolaireTest` (blocage si pas de photo, génération réussie après upload, réémission incrémentant `numero_duplicata` avec un nouveau `numero`, isolation multi-établissement) — 94/94 tests OK au total (suite complète).
- Frontend :
  - **Première "fiche apprenant" du projet** (`ApprenantsListPage` + `ApprenantFichePage`, nouveau lien de navigation top-level "Apprenants") : jusqu'ici la recherche d'apprenant n'existait qu'en ligne dans des modales (inscription, caisse), sans page de profil autonome.
  - `ApprenantFichePage` : affichage/upload de photo, génération d'attestation (sélection du type) et de carte scolaire en un clic, historique combiné des documents déjà émis avec lien de téléchargement et badge de duplicata.
- Vérification bout-en-bout (Playwright), connecté `secretaire@djaart.school` : upload de photo pour Aïcha Traoré, génération d'une attestation de scolarité (PDF téléchargé réel, non vide), génération d'une carte scolaire (PDF avec QR et photo intégrés), réémission d'une seconde carte → badge "Duplicata 1" confirmé ; création en direct d'une nouvelle inscription non payée → tentative de génération d'attestation correctement bloquée avec message clair.

### Hypothèses / écarts documentés
- **QR auto-porteur, pas d'URL de vérification publique** : aucun portail de consultation/vérification externe n'existe dans ce projet (même remarque que le portail apprenant différé en Phases 5/7) — le QR encode directement les données d'identification du document.
- **Blocage strict** (pas d'attestation "sous réserve") si l'inscription n'est pas validée, cohérent avec le choix déjà fait en Phases 5/7.

## Phase 9 — Tableaux de bord et rapports
- Statut : **terminée**

### Contexte et décision de périmètre
Le cahier des charges demande un tableau de bord par rôle, y compris "Apprenant". Or aucune liaison `User`↔`Apprenant` n'existe dans ce projet (déjà documenté comme différé en Phases 5, 7 et 8, sous l'angle du portail apprenant/parent). Avant de re-différer une 4ème fois par simple hypothèse silencieuse, la question a été posée explicitement à l'utilisateur : le tableau de bord "Apprenant" reste **hors périmètre** pour cette itération (décision confirmée). Cette phase livre les 4 tableaux de bord **staff** : Super Admin, Admin établissement, Comptable, Enseignant.

### Réalisé
- Backend :
  - **Un seul endpoint `GET /dashboard`** (pas de restriction de rôle sur la route elle-même, ouverte à tout utilisateur authentifié) : le contenu varie selon le rôle déterminé côté serveur (`DashboardController` teste `hasRole()` dans l'ordre super_admin → admin_etablissement → comptable → enseignant) et renvoie `{ role, data }`. Plus simple qu'un endpoint par rôle ; le frontend n'a qu'à choisir le composant de présentation correspondant à `role`.
  - `DashboardService` : une méthode par rôle.
    - `pourSuperAdmin()` : agrégats globaux (nb établissements, apprenants actifs tous établissements, encaissé du mois tous établissements, effectif d'utilisateurs par rôle via le scope Spatie `User::role()`) + un tableau par établissement (effectif, encaissé du mois) — bypass volontaire du scope multi-tenant, réservé à ce rôle.
    - `pourAdminEtablissement()` : effectifs par classe, taux de recouvrement (encaissé / `montant_total` attendu sur les inscriptions actives, tous statuts confondus hors annulée), liste des affectations dont la **période la plus récente** (dernière séquence ou dernier semestre configuré pour le niveau+année) n'a pas encore de saisie verrouillée, top-5 des impayés en retard.
    - `pourComptable()` : encaissé du jour, encaissé du mois, liste complète des impayés en retard.
    - `pourEnseignant()` : ses affectations avec, pour chacune, la période la plus récente et si elle est déjà soumise ou non.
  - `RapportService` (réutilisé par `DashboardService` pour la liste d'impayés, afin de ne pas dupliquer la règle de calcul) :
    - `listerImpayes()` : tranches dont `date_echeance` est dépassée et dont le solde (montant de la tranche − somme des paiements de **cette inscription** sur cette tranche, même méthode que `ApprenantController::echeancier`) est encore positif — agrégées par apprenant, triées par ancienneté de retard décroissante.
    - `statistiquesReussite()` : répartition des mentions déjà attribuées, à partir de `ReleveDeNotes.mention` (et non `Bulletin`, qui ne porte qu'une moyenne/rang par séquence sans mention).
  - **Rapports en PDF uniquement**, générés à la volée et streamés directement (`response()->streamDownload` équivalent — réponse PDF brute avec `Content-Disposition: attachment`), **non persistés en base** : ce sont des rapports consultatifs, pas des documents officiels numérotés comme les reçus/attestations/bulletins. `RapportController::impayes()`/`statistiquesReussite()`, route `role:super_admin|admin_etablissement|comptable` (l'enseignant et la secrétaire n'y ont pas accès).
  - Tests Feature : `DashboardTest` (agrégats globaux corrects pour super_admin sur 2 établissements, taux de recouvrement et top impayés corrects pour admin_etablissement avec isolation multi-établissement vérifiée, encaissements du jour/mois et impayés corrects pour comptable, affectations avec statut de saisie correct pour enseignant, isolation des affectations incomplètes entre établissements), `RapportTest` (PDF valides pour comptable/admin_etablissement/super_admin, accès refusé à enseignant et secrétaire) — 104/104 tests OK au total (suite complète).
- Frontend :
  - `src/api/dashboardApi.js` (`fetchDashboard`, URLs des deux rapports).
  - `DashboardPage.jsx` remplace le message générique de bienvenue : bascule sur l'un des 4 composants (`SuperAdminDashboard`, `AdminEtablissementDashboard`, `ComptableDashboard`, `EnseignantDashboard`, nouveau dossier `features/dashboard/`) selon `data.role` reçu de l'API. Cartes de métriques (`MetricCard`, couleurs de marque) + tableaux simples pour les listes (impayés, affectations incomplètes, effectifs, par établissement).
  - Boutons d'export PDF ("Rapport des impayés", "Statistiques de réussite") sur les tableaux de bord Admin établissement/Comptable, ouverture dans un nouvel onglet avec `rel="noopener"` (cohérent avec le correctif Referer/Sanctum de la Phase 5).
- Vérification bout-en-bout (Playwright, sur les données de démo existantes) : connexion successive `admin@djaart.school` (super_admin — 4 établissements, répartition par rôle et par établissement cohérente), `admin_etablissement@djaart.school` (taux de recouvrement 27,6 %, 1 affectation à saisir, impayés réels affichés), `comptable@djaart.school` (encaissements du jour/mois et impayés en retard identiques à ceux de l'admin établissement, même établissement), `enseignant@djaart.school` (affectation "6ème A / Mathématiques", statut "À saisir" correct) — aucune erreur console sur les 4 tableaux de bord ; téléchargement réel des deux rapports PDF pour l'admin établissement (contenu non vide, en-tête `%PDF` valide).

### Hypothèses / écarts documentés
- **Tableau de bord Apprenant explicitement hors périmètre** (décision utilisateur confirmée, cf. contexte ci-dessus) — aucune route ni composant n'est prévu pour ce rôle dans cette phase.
- **Rapports PDF uniquement**, pas d'export Excel/CSV séparé — le "/" du cahier des charges ("PDF/Excel") a été lu comme une alternative de format, pas une obligation des deux ; évite d'ajouter une dépendance lourde (type PhpSpreadsheet) pour un besoin secondaire.
- **Rapports non persistés/non numérotés** (contrairement aux reçus/attestations/cartes/bulletins) — générés à la demande à chaque téléchargement, cohérents avec leur nature consultative plutôt qu'officielle.
- **Statistiques de réussite basées sur `ReleveDeNotes.mention`**, pas `Bulletin` (qui n'a qu'une moyenne/rang par séquence, sans mention) — plus juste par rapport aux données réellement disponibles.

## Phases suivantes
Voir `DJAART_SCHOOL_CLAUDE_CODE_BUILD_PLAN.md` section 6 pour le détail des phases 10 à 11. Prochaine étape : **Phase 10**.
