# DJAART SCHOOL — Feuille de route de développement (pour Claude Code)

> Ce document est le cahier des charges technique et le plan d'exécution étape par étape à suivre pour développer l'application **DJAART SCHOOL**. Il doit être traité comme la source de vérité du projet. À lire intégralement avant de commencer à coder.

---

## 0. Instructions générales pour Claude Code

- **Travailler phase par phase**, dans l'ordre indiqué en section 6. Ne pas passer à la phase suivante tant que la phase en cours n'est pas fonctionnelle et validée (voir "Definition of Done" de chaque phase).
- **Toujours proposer un plan avant de coder une phase** (fichiers à créer, migrations, routes, composants), puis exécuter.
- **Commits atomiques** : un commit par fonctionnalité logique, message clair (`feat: ...`, `fix: ...`, `refactor: ...`).
- **Créer/mettre à jour un fichier `PROGRESS.md`** à la racine du projet après chaque phase terminée, listant ce qui a été fait et ce qui reste.
- **Ne jamais casser une phase précédente** : lancer les tests existants avant de merger une nouvelle fonctionnalité.
- **Priorité absolue : sécurité et fiabilité des données financières et académiques** (paiements, notes). En cas de doute sur une règle métier, privilégier l'option la plus sûre et documenter l'hypothèse prise dans `PROGRESS.md`.
- **On termine entièrement la version Web (backend Laravel + frontend React) avant de démarrer la version mobile.** La version mobile Android sera abordée uniquement après validation complète de la phase 10.

---

## 1. Présentation du projet

DJAART SCHOOL est une plateforme multi-établissements de gestion scolaire couvrant :
- **Primaire / Secondaire** (classes, séquences, bulletins)
- **Universitaire** (filières, système LMD, semestres, notes CC/Session Normale, relevés)
- **Centre de formation** (filières DQP/CQP)

Fonctions principales : inscriptions, paiement de scolarité en tranches avec génération de reçus, gestion pédagogique (affectation enseignant/matière/classe, saisie des notes), génération des effets académiques (attestation de scolarité, carte scolaire/étudiant, bulletins, relevés de notes).

*(Le détail fonctionnel complet — acteurs, cas d'utilisation, modèle de données, diagrammes UML — se trouve dans le document d'analyse `DJAART_SCHOOL_Analyse_Conception_UML.md` fourni séparément. Ce document sert de spécification fonctionnelle de référence ; il faut s'y référer pour toute règle métier non détaillée ici.)*

---

## 2. Stack technique imposée

| Couche | Technologie |
|---|---|
| Backend / API | **Laravel** (dernière version stable 11.x), PHP 8.2+ |
| Authentification API | **Laravel Sanctum** (SPA + tokens) |
| Frontend Web | **React (Vite)** + **React Router** |
| Style | **Tailwind CSS** (+ composants réutilisables, pas de UI kit lourd type Bootstrap) |
| Base de données | **MySQL** via **XAMPP** (environnement de développement local) |
| ORM | **Eloquent** |
| Génération PDF | `barryvdh/laravel-dompdf` ou `spatie/laravel-pdf` |
| Upload / stockage fichiers | Laravel Storage (local en dev, prêt pour S3-compatible en prod) |
| Autorisations | **Spatie Laravel-Permission** (rôles & permissions) |
| Validation | Form Requests Laravel côté backend + validation miroir côté frontend |
| Tests backend | PHPUnit / Pest |
| Tests frontend | Vitest + React Testing Library |
| Gestion d'état frontend | React Context + hooks personnalisés, ou Zustand si complexité justifiée |
| Requêtes HTTP frontend | Axios avec instance centralisée + intercepteurs |
| Mobile (phase ultérieure) | **React Native (Android d'abord)**, réutilisation de l'API Laravel existante |

---

## 3. Environnement de développement (XAMPP)

- PHP 8.2+, MySQL 8+, Apache (ou artisan serve pour l'API en dev).
- Base de données : créer une base `djaart_school` via phpMyAdmin (fournie par XAMPP).
- Fichier `.env` du backend Laravel :
  - `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=djaart_school`, `DB_USERNAME=root`, `DB_PASSWORD=` (selon config XAMPP locale).
  - `SANCTUM_STATEFUL_DOMAINS` et `SESSION_DOMAIN` configurés pour `localhost` + port du frontend Vite (ex. `localhost:5173`).
  - `APP_URL` cohérent avec l'URL de l'API.
- CORS (`config/cors.php`) : autoriser uniquement l'origine du frontend (pas de wildcard `*` en environnement avec cookies/session).
- Le frontend React (Vite) tourne séparément (`npm run dev`), consomme l'API via une baseURL configurable (`.env` du frontend : `VITE_API_URL`).

---

## 4. Architecture

- **Backend** : API RESTful Laravel, organisée par domaine métier (voir structure ci-dessous). Toutes les réponses au format JSON standardisé (`data`, `message`, `errors`).
- **Frontend** : Single Page Application React, découpée en modules alignés sur les domaines fonctionnels (Auth, Paramétrage, Inscriptions, Finance, Pédagogie, Documents).
- **Multi-tenant logique** : chaque enregistrement métier est rattaché à un `etablissement_id`. Toute requête backend doit filtrer automatiquement par établissement de l'utilisateur connecté (sauf Super Admin), via un **Global Scope Eloquent** ou middleware dédié — jamais une vérification manuelle oubliable dans chaque contrôleur.
- **RBAC** : rôles principaux — `super_admin`, `admin_etablissement`, `secretaire`, `comptable`, `enseignant`, `apprenant`. Permissions granulaires par action (ex. `notes.saisir`, `paiements.encaisser`, `documents.generer`).

### Structure de dossiers indicative (backend)

```
app/
  Models/
  Http/
    Controllers/Api/
      Auth/
      Parametrage/       (Etablissement, AnneeAcademique, Filiere, Niveau, Classe, Matiere)
      Finance/           (FraisScolarite, Tranche, Paiement, Recu)
      Inscription/
      Pedagogie/         (AffectationEnseignant, Note, Bulletin, ReleveDeNotes)
      Documents/         (Attestation, CarteScolaire)
    Requests/            (Form Requests de validation, un par action sensible)
    Resources/           (API Resources pour formatter les réponses JSON)
    Middleware/
  Services/               (logique métier complexe : calcul moyennes, échéanciers, numérotation reçus/attestations)
  Policies/               (autorisations fines par modèle)
database/
  migrations/
  seeders/
  factories/
routes/
  api.php
tests/
  Feature/
  Unit/
```

### Structure de dossiers indicative (frontend)

```
src/
  api/                  (client axios, un fichier par ressource : authApi, inscriptionApi, paiementApi...)
  components/
    ui/                 (composants génériques : Button, Modal, Table, Input, Badge, Toast...)
    layout/             (Sidebar, Topbar, DashboardLayout selon rôle)
  features/
    auth/
    parametrage/
    inscriptions/
    finance/
    pedagogie/
    documents/
    dashboard/
  hooks/
  context/              (AuthContext, EtablissementContext)
  routes/               (routes protégées par rôle)
  utils/
```

---

## 5. Exigences transverses (à respecter dans TOUTES les phases)

### Sécurité
- Hachage des mots de passe (bcrypt/argon2 natif Laravel), politique de mot de passe forte.
- Authentification via Sanctum (tokens SPA), expiration et renouvellement de session gérés proprement.
- Autorisation systématique via Policies/Gates + middleware de rôle sur **chaque** route API (jamais uniquement côté frontend).
- Validation stricte de toutes les entrées (Form Requests), whitelist des champs autorisés (pas de mass assignment non contrôlé — utiliser `$fillable` explicite).
- Protection CSRF pour les routes stateful, rate limiting sur les routes sensibles (login, paiement).
- Journalisation (audit trail) des actions sensibles : création/modification de paiement, saisie de note, génération de document, changement de rôle.
- Aucune donnée sensible (mot de passe, tokens) dans les logs ou réponses API.
- Isolation stricte multi-établissement testée (un admin d'un établissement ne doit jamais accéder aux données d'un autre).

### UI/UX
- Interfaces réactives (feedback immédiat : loaders, toasts de succès/erreur, désactivation de bouton pendant soumission).
- Formulaires avec validation en temps réel et messages d'erreur clairs.
- Tableaux avec recherche, tri, pagination pour toutes les listes (apprenants, paiements, notes...).
- Design cohérent via un système de design Tailwind (palette de couleurs, typographie, espacement définis une fois dans `tailwind.config.js` puis réutilisés).
- Responsive (desktop prioritaire pour la version web, mais utilisable sur tablette).
- Dashboards différenciés par rôle (un enseignant ne voit pas les mêmes widgets qu'un comptable).

### Qualité
- Tests backend (Feature tests) sur chaque endpoint critique (auth, inscription, paiement, notes, génération de documents).
- Seeders de données de démonstration réalistes pour chaque type d'établissement (au moins un établissement primaire, un secondaire, un universitaire, un centre de formation) permettant de tester rapidement.

---

## 6. Plan de développement étape par étape

> Chaque phase doit se terminer par : migrations + modèles + endpoints API testés (Postman/tests automatisés) + interfaces React fonctionnelles + mise à jour de `PROGRESS.md`.

### Phase 0 — Initialisation du projet
- Créer le projet Laravel (`laravel new djaart-school-backend`) et le connecter à MySQL via XAMPP.
- Créer le projet React + Vite + Tailwind (`djaart-school-frontend`).
- Configurer Sanctum, CORS, structure de dossiers (section 4).
- Installer Spatie Laravel-Permission, dompdf/spatie-pdf.
- Mettre en place le layout de base React (connexion, dashboard vide, sidebar par rôle).
- **DoD** : `php artisan serve` + `npm run dev` fonctionnent ensemble, un utilisateur de test peut se connecter et voir un dashboard vide.

### Phase 1 — Authentification & gestion des utilisateurs/rôles
- Migrations : `users`, `roles`, `permissions` (via Spatie), `password_resets`.
- Endpoints : login, logout, me, gestion des comptes (CRUD utilisateurs par Super Admin/Admin), attribution de rôle.
- Frontend : pages login, mot de passe oublié, gestion des comptes (liste + création + édition de rôle), garde de routes par rôle.
- **DoD** : chaque rôle (super_admin, admin_etablissement, secretaire, comptable, enseignant, apprenant) peut se connecter et n'accède qu'aux menus qui lui sont autorisés.

### Phase 2 — Paramétrage académique
- Migrations : `etablissements`, `annees_academiques`, `filieres`, `niveaux`, `classes`, `matieres`.
- Endpoints CRUD complets pour chaque entité, avec règles : un `niveau` a un `type_systeme` (`classique` ou `lmd`), une `classe` est liée à un `niveau` + une `annee_academique`.
- Frontend : écrans de gestion (établissement, années académiques, filières/niveaux/classes, matières) réservés aux admins.
- **DoD** : un admin peut entièrement paramétrer un établissement (les 4 types) de zéro : année académique, filières, niveaux/classes, matières.

### Phase 3 — Frais de scolarité et tranches
- Migrations : `frais_scolarite` (niveau_id, annee_academique_id, montant_total, nombre_tranches), `tranches` (frais_scolarite_id, numero, montant, date_echeance).
- Endpoint de création avec **validation bloquante** : somme des tranches = montant total.
- Frontend : écran de configuration des frais par classe/niveau avec génération assistée des tranches (ex. répartition automatique proposée, modifiable).
- **DoD** : un admin configure les frais et l'échéancier de tranches pour chaque niveau/année, avec contrôle de cohérence.

### Phase 4 — Inscriptions
- Migrations : `inscriptions` (apprenant_id, classe_id, annee_academique_id, frais_scolarite_id, statut, type_inscription).
- Génération automatique du matricule apprenant.
- Endpoint d'inscription avec rattachement automatique de la grille de frais de la classe.
- Gestion du cycle de vie (`EN_COURS`, `VALIDEE`, `SUSPENDUE`, `ANNULEE`, `CLOTUREE`) — voir diagramme d'état du document d'analyse.
- Frontend : formulaire d'inscription (recherche/création apprenant, sélection classe/année, récapitulatif frais), liste des inscriptions avec filtres (classe, statut, année).
- **DoD** : une secrétaire inscrit un apprenant de bout en bout et voit son échéancier de paiement généré automatiquement.

### Phase 5 — Paiements et reçus
- Migrations : `paiements` (inscription_id, tranche_id, montant, mode_paiement, caissier_id), `recus` (paiement_id, numero_recu, fichier_pdf).
- Service de calcul de solde par tranche (gestion paiement partiel/total), numérotation séquentielle sécurisée des reçus (jamais de doublon, même en cas de concurrence — utiliser transaction + verrou).
- Génération PDF du reçu (template propre : en-tête établissement, détails apprenant, tranche, montant, mode, date, numéro).
- Frontend : interface caisse (recherche rapide apprenant, échéancier visuel payé/dû/en retard, saisie paiement, impression reçu immédiate), historique des paiements par apprenant.
- **DoD** : un comptable encaisse une tranche (totale ou partielle) et le reçu PDF est généré et téléchargeable instantanément ; le solde de l'apprenant est à jour.

### Phase 6 — Pédagogie : affectations et saisie des notes
- Migrations : `affectations_enseignant` (enseignant_id, matiere_id, classe_id, annee_academique_id), `sequences` (pour classique), `semestres` (pour LMD), `notes` (apprenant_id, affectation_id, sequence_id/semestre_id, type_evaluation, valeur).
- Endpoints : affectation par l'admin, saisie de notes par l'enseignant (avec verrouillage après soumission), gestion différenciée classique (séquence unique) vs LMD (CC + Session Normale avec pondération configurable).
- Frontend : écran admin d'affectation matière/classe/enseignant ; écran enseignant de saisie de notes (grille apprenants x note, par classe/matière/séquence ou UE/semestre/type d'évaluation).
- **DoD** : un enseignant affecté saisit les notes de sa classe pour une séquence (ou son UE pour CC/SN) et la saisie est verrouillée après soumission.

### Phase 7 — Bulletins et relevés de notes
- Migrations : `bulletins` (inscription_id, sequence_id, moyenne_generale, rang, fichier_pdf), `releves_notes` (inscription_id, semestre_id ou annee, moyenne_generale, mention, fichier_pdf).
- Service de calcul : moyenne pondérée par matière/UE, moyenne générale, rang (classique) ; moyenne UE = f(CC, SN), crédits ECTS validés, mention/décision (LMD).
- Génération PDF (bulletin par apprenant, relevé officiel numéroté).
- Frontend : déclenchement de clôture de séquence/semestre par la secrétaire (avec blocage si notes manquantes), consultation/téléchargement des bulletins et relevés (secrétariat + portail apprenant en lecture seule).
- **DoD** : à la clôture d'une séquence, tous les bulletins de la classe sont générés correctement ; à la fin d'un semestre LMD ou d'une année classique, les relevés de notes sont générés avec la bonne mention/décision.

### Phase 8 — Effets académiques (attestations et cartes)
- Migrations : `attestations` (apprenant_id, type, numero, fichier_pdf), `cartes_scolaires` (apprenant_id, numero, date_emission, date_expiration, fichier_pdf).
- Génération PDF avec numérotation unique par établissement, QR code de vérification.
- Frontend : bouton "Générer attestation" / "Générer carte" depuis la fiche apprenant, historique des documents déjà émis (avec gestion des duplicatas).
- **DoD** : depuis la fiche d'un apprenant inscrit, la secrétaire génère en un clic une attestation de scolarité et une carte scolaire/étudiant conformes.

### Phase 9 — Tableaux de bord et rapports
- Dashboard Super Admin (vue multi-établissements), Admin établissement (effectifs, taux de recouvrement des frais, notes en attente), Comptable (encaissements du jour/mois, impayés), Enseignant (classes/matières à saisir), Apprenant (situation financière, notes, documents).
- Export de rapports (liste des impayés, statistiques de réussite) en PDF/Excel.
- **DoD** : chaque rôle dispose d'un tableau de bord pertinent et fonctionnel à sa connexion.

### Phase 10 — Durcissement, tests, optimisation, préparation déploiement
- Revue de sécurité complète (checklist OWASP de base : injection, XSS, CSRF, contrôle d'accès, gestion des sessions).
- Tests de charge légers sur les endpoints critiques (paiement, saisie de notes en période de rentrée).
- Optimisation des requêtes (eager loading, index MySQL sur les colonnes de recherche/filtrage fréquentes : `matricule`, `etablissement_id`, `classe_id`).
- Documentation technique finale (README d'installation, variables d'environnement, procédure de sauvegarde MySQL).
- **DoD** : la version Web est jugée stable, sécurisée et prête pour un déploiement pilote dans un établissement réel.

### Phase 11 (à démarrer uniquement après validation de la phase 10) — Version mobile Android
- Choix technique : **React Native** consommant la même API Laravel (aucune duplication de logique métier côté serveur).
- Périmètre mobile priorisé : portail apprenant/parent (consultation notes, bulletins, situation financière, documents) et module enseignant (saisie de notes hors-ligne synchronisable).
- Authentification mobile via Sanctum (tokens), gestion sécurisée du stockage du token (Keychain/Keystore, pas de stockage en clair).
- **DoD** : application Android fonctionnelle publiable en version bêta interne, avant d'envisager une version iOS.

---

## 7. Conventions de code

- **Backend** : PSR-12, noms de classes/méthodes en anglais, noms de colonnes de base de données en `snake_case` français ou anglais mais **cohérent sur tout le projet** (recommandé : anglais technique pour les colonnes, ex. `student_id`, pour faciliter la maintenance future).
- **Frontend** : composants fonctionnels React, hooks personnalisés pour la logique réutilisable, nommage `PascalCase` pour les composants, `camelCase` pour les fonctions/variables.
- Un module = un dossier `feature` avec ses composants, hooks et appels API co-localisés.
- Pas de logique métier dans les contrôleurs Laravel au-delà de l'orchestration simple : déléguer aux **Services** dès que la logique dépasse quelques lignes (calcul de moyenne, génération de numéro de reçu, etc.).

---

## 8. Livrable attendu à chaque phase

1. Migrations + modèles Eloquent (avec relations définies).
2. Form Requests de validation.
3. Contrôleurs + routes API + Policies.
4. Tests Feature couvrant les cas nominaux et au moins un cas d'erreur par endpoint sensible.
5. Composants et pages React correspondants, connectés à l'API réelle (pas de mock une fois la phase backend terminée).
6. Mise à jour de `PROGRESS.md`.

---

*Ce document est destiné à guider un développement assisté par Claude Code, phase par phase, jusqu'à la mise en production de la version Web, avant d'entamer la version mobile Android.*
