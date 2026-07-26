# DJAART SCHOOL — Analyse et Conception UML
### Système de gestion multi-établissements (Primaire, Secondaire, Universitaire, Centre de formation DQP/CQP)

---

## 1. Contexte et objectifs

DJAART SCHOOL est une plateforme multi-établissements destinée à gérer :

- **Primaire / Secondaire** : classes, séquences, bulletins.
- **Universitaire** : filières, système **LMD** (Licence-Master-Doctorat), semestres, notes de **CC** (Contrôle Continu) et de **Session Normale (SN)**, relevés de notes.
- **Centre de formation** : filières préparant aux **DQP** (Diplôme de Qualification Professionnelle) et **CQP** (Certificat de Qualification Professionnelle).

Le système doit couvrir 4 grands domaines fonctionnels transverses à tous les types d'établissements :

| Domaine | Contenu |
|---|---|
| **D1 — Paramétrage académique** | Établissements, années académiques, filières, niveaux/classes, matières/UE, frais de scolarité et tranches |
| **D2 — Inscription & Finance** | Inscription des apprenants, paiement en tranches, génération de reçus |
| **D3 — Pédagogie** | Affectation enseignant ↔ matière ↔ classe, saisie des notes (séquence ou CC/SN), génération bulletins/relevés |
| **D4 — Effets académiques** | Attestation de scolarité, carte scolaire/étudiant, relevé de notes |

---

## 2. Acteurs du système

| Acteur | Rôle |
|---|---|
| **Super Administrateur** | Gère les établissements, les comptes admin, la configuration globale |
| **Administrateur d'établissement** | Paramètre l'année académique, les classes/niveaux, les frais de scolarité (montant + découpage en tranches), affecte les enseignants aux matières |
| **Secrétaire / Chargé des inscriptions** | Inscrit les apprenants, édite les effets académiques (attestation, carte) |
| **Comptable / Caissier** | Encaisse les paiements de tranches, génère les reçus |
| **Enseignant** | Saisit les notes des apprenants pour les matières/UE qui lui sont affectées |
| **Apprenant / Étudiant / Parent** | Consulte son dossier, ses notes, ses bulletins/relevés, ses reçus (portail apprenant) |

---

## 3. Diagramme de cas d'utilisation

### 3.1 Vue globale

```mermaid
graph LR
    SuperAdmin((Super Admin))
    Admin((Admin Établissement))
    Secretaire((Secrétaire))
    Comptable((Comptable))
    Enseignant((Enseignant))
    Apprenant((Apprenant / Parent))

    subgraph "D1 - Paramétrage académique"
        UC1[Créer/Configurer établissement]
        UC2[Créer année académique]
        UC3[Créer filière / niveau / classe]
        UC4[Définir matière / UE]
        UC5[Configurer frais de scolarité et tranches]
    end

    subgraph "D2 - Inscription & Finance"
        UC6[Inscrire un apprenant]
        UC7[Réinscrire un apprenant]
        UC8[Effectuer un paiement de tranche]
        UC9[Générer un reçu]
        UC10[Consulter situation financière]
    end

    subgraph "D3 - Pédagogie"
        UC11[Affecter enseignant à matière/classe]
        UC12[Saisir les notes CC/Séquence]
        UC13[Saisir les notes Session Normale]
        UC14[Générer bulletin de séquence]
        UC15[Générer relevé de notes annuel/semestriel]
    end

    subgraph "D4 - Effets académiques"
        UC16[Générer attestation de scolarité]
        UC17[Générer carte scolaire/étudiant]
    end

    SuperAdmin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Admin --> UC5
    Admin --> UC11

    Secretaire --> UC6
    Secretaire --> UC7
    Secretaire --> UC16
    Secretaire --> UC17

    Comptable --> UC8
    Comptable --> UC9
    Comptable --> UC10

    Enseignant --> UC12
    Enseignant --> UC13

    Secretaire --> UC14
    Secretaire --> UC15

    Apprenant --> UC10
    Apprenant --> UC15
```

### 3.2 Zoom — Module Inscription & Paiement

```mermaid
graph LR
    Secretaire((Secrétaire))
    Comptable((Comptable))
    Apprenant((Apprenant))

    UC6[Inscrire apprenant]
    UC6a[Vérifier dossier / pièces]
    UC6b[Affecter à une classe]
    UC8[Effectuer paiement de tranche]
    UC8a[Sélectionner tranche due]
    UC9[Générer reçu]
    UC16[Générer attestation de scolarité]

    Secretaire --> UC6
    UC6 -.include.-> UC6a
    UC6 -.include.-> UC6b
    UC6 -.extend.-> UC16

    Comptable --> UC8
    UC8 -.include.-> UC8a
    UC8 -.include.-> UC9
    Apprenant --> UC8
```

### 3.3 Zoom — Module Pédagogie (Classique vs LMD)

```mermaid
graph LR
    Admin((Admin))
    Enseignant((Enseignant))
    Secretaire((Secrétaire))

    UC11[Affecter enseignant à matière/classe]
    UC12a[Saisir notes - Séquence primaire/secondaire]
    UC12b[Saisir notes - CC LMD]
    UC12c[Saisir notes - Session Normale LMD]
    UC14[Générer bulletin de séquence]
    UC15a[Générer relevé de notes annuel classique]
    UC15b[Générer relevé de notes semestriel LMD]

    Admin --> UC11
    Enseignant --> UC12a
    Enseignant --> UC12b
    Enseignant --> UC12c
    UC12a -.extend.-> UC14
    UC12b -.include.-> UC15b
    UC12c -.include.-> UC15b
    UC14 -.extend.-> UC15a
    Secretaire --> UC14
    Secretaire --> UC15a
    Secretaire --> UC15b
```

---

## 4. Description détaillée des scénarios (fiches de cas d'utilisation)

### UC-01 — Configurer les frais de scolarité et les tranches (Admin)

| Élément | Description |
|---|---|
| **Acteur principal** | Administrateur d'établissement |
| **Objectif** | Définir le montant de la pension pour une classe/niveau donné, pour une année académique, et le découper en mensualités/tranches |
| **Préconditions** | L'année académique et la classe/niveau existent déjà |
| **Scénario nominal** | 1. L'admin sélectionne l'établissement, l'année académique et la classe/niveau.<br>2. Il saisit le montant total de la scolarité.<br>3. Il choisit le mode de paiement : **comptant** ou **par tranches**.<br>4. S'il choisit "par tranches", il définit le nombre de tranches, le montant et la date d'échéance de chacune (ex. 1ère tranche 40%, puis mensualités).<br>5. Le système valide que la somme des tranches = montant total.<br>6. Le système enregistre la grille tarifaire. |
| **Scénarios alternatifs** | A1 : Somme des tranches ≠ montant total → message d'erreur, retour à l'étape 4.<br>A2 : Modification d'une grille déjà utilisée par des inscriptions → avertissement, création d'une nouvelle version plutôt qu'écrasement. |
| **Postconditions** | La grille de frais (FraisScolarite + Tranches) est disponible pour les inscriptions de cette classe/niveau/année |

---

### UC-02 — Inscrire un apprenant

| Élément | Description |
|---|---|
| **Acteur principal** | Secrétaire / Chargé des inscriptions |
| **Objectif** | Créer le dossier d'un apprenant et l'affecter à une classe pour une année académique |
| **Préconditions** | L'année académique est ouverte ; la classe a des places disponibles ; les frais de scolarité de la classe sont configurés |
| **Scénario nominal** | 1. La secrétaire crée ou recherche la fiche de l'apprenant (identité, filiation, documents).<br>2. Elle sélectionne l'établissement, l'année académique et la classe/niveau (filière + niveau + groupe pour université/centre).<br>3. Le système attribue un **matricule** unique et rattache automatiquement la **grille de frais** de la classe.<br>4. Le statut de l'inscription passe à "EN COURS".<br>5. Si un paiement initial est versé immédiatement, le système enchaîne sur UC-04 (paiement).<br>6. L'inscription est validée ("VALIDÉE") dès que la 1ère tranche obligatoire est réglée (règle paramétrable). |
| **Scénarios alternatifs** | A1 : Classe pleine → proposition d'une classe parallèle ou liste d'attente.<br>A2 : Réinscription d'un apprenant déjà existant → le système réutilise le dossier et l'historique, ne recrée pas le matricule (sauf changement d'établissement). |
| **Postconditions** | Une Inscription est créée, liée à l'apprenant, la classe, l'année académique et la grille de frais |

---

### UC-03 — Réinscrire un apprenant

| Élément | Description |
|---|---|
| **Acteur principal** | Secrétaire |
| **Objectif** | Faire passer un apprenant à l'année académique/niveau suivant |
| **Préconditions** | L'apprenant a un dossier existant ; l'année académique précédente est soldée ou en cours de clôture |
| **Scénario nominal** | 1. Recherche de l'apprenant.<br>2. Le système propose le niveau supérieur (promotion automatique selon la moyenne, si activé) ou permet un choix manuel (redoublement).<br>3. Création d'une nouvelle Inscription liée à la nouvelle année académique/classe.<br>4. Rattachement de la nouvelle grille de frais. |
| **Postconditions** | Nouvelle inscription créée, historique des inscriptions précédentes conservé |

---

### UC-04 — Effectuer un paiement de tranche et générer le reçu

| Élément | Description |
|---|---|
| **Acteur principal** | Comptable / Caissier |
| **Objectif** | Encaisser le règlement d'une tranche de scolarité et produire un reçu |
| **Préconditions** | L'apprenant est inscrit et a des tranches dues |
| **Scénario nominal** | 1. Le comptable recherche l'apprenant (par matricule ou nom).<br>2. Le système affiche l'échéancier : tranches payées, en attente, en retard.<br>3. Le comptable sélectionne la tranche à régler (paiement total ou partiel de la tranche).<br>4. Il saisit le montant, le mode de paiement (espèces, mobile money, virement, chèque).<br>5. Le système enregistre le Paiement, met à jour le solde de la tranche (PAYÉE / PARTIELLE).<br>6. Le système génère automatiquement un **Reçu** numéroté (PDF), imprimable. |
| **Scénarios alternatifs** | A1 : Paiement partiel → la tranche reste "PARTIELLEMENT PAYÉE", un reçu partiel est émis.<br>A2 : Montant saisi > solde dû → système propose d'affecter le surplus à la tranche suivante.<br>A3 : Tranche déjà soldée → blocage de la saisie. |
| **Postconditions** | Un Paiement et un Reçu sont enregistrés ; le solde de l'apprenant est mis à jour |

---

### UC-05 — Affecter un enseignant à une matière/classe

| Élément | Description |
|---|---|
| **Acteur principal** | Administrateur d'établissement |
| **Objectif** | Associer un enseignant à une ou plusieurs matières/UE d'une classe pour l'année académique |
| **Préconditions** | L'enseignant est enregistré dans la plateforme ; les matières et classes existent |
| **Scénario nominal** | 1. L'admin sélectionne une classe.<br>2. Il liste les matières/UE de cette classe (selon le programme du niveau/filière).<br>3. Pour chaque matière, il choisit un enseignant.<br>4. Le système crée l'**Affectation** (classe + matière + enseignant + année académique).<br>5. L'enseignant voit désormais cette matière/classe dans son espace pour la saisie des notes. |
| **Scénarios alternatifs** | A1 : Matière déjà affectée à un autre enseignant → remplacement avec historisation. |
| **Postconditions** | L'enseignant peut saisir les notes de la matière pour cette classe |

---

### UC-06 — Saisir les notes (Séquence — Primaire/Secondaire)

| Élément | Description |
|---|---|
| **Acteur principal** | Enseignant |
| **Objectif** | Saisir les notes des apprenants d'une classe pour sa matière, à chaque séquence |
| **Préconditions** | L'enseignant est affecté à la matière/classe ; la séquence est ouverte |
| **Scénario nominal** | 1. L'enseignant sélectionne sa classe, sa matière et la séquence en cours.<br>2. Le système affiche la liste des apprenants inscrits et actifs.<br>3. L'enseignant saisit une note par apprenant (+ coefficient déjà défini par la matière).<br>4. Il valide et soumet.<br>5. Le système verrouille la saisie (modifiable seulement via demande à l'admin après soumission). |
| **Scénarios alternatifs** | A1 : Apprenant absent → note "ABS" gérée selon règle de l'établissement (0 ou neutralisée).<br>A2 : Séquence clôturée → saisie bloquée. |
| **Postconditions** | Les notes de la séquence sont enregistrées et disponibles pour le calcul du bulletin |

---

### UC-07 — Saisir les notes CC / Session Normale (Système LMD — Université)

| Élément | Description |
|---|---|
| **Acteur principal** | Enseignant |
| **Objectif** | Saisir les notes de Contrôle Continu (CC) et de Session Normale (SN) par UE et par semestre |
| **Préconditions** | L'enseignant est affecté à l'UE ; le semestre est actif |
| **Scénario nominal** | 1. L'enseignant sélectionne la filière, le niveau (L1…M2), le semestre, l'UE.<br>2. Il choisit le type d'évaluation : **CC** ou **Session Normale**.<br>3. Il saisit les notes des étudiants inscrits à cette UE.<br>4. Le système calcule automatiquement la moyenne de l'UE selon la pondération paramétrée (ex. CC 40% + SN 60%).<br>5. Validation et verrouillage de la saisie. |
| **Scénarios alternatifs** | A1 : Étudiant non admis à la SN (assidu insuffisant) → blocage de saisie SN pour cet étudiant.<br>A2 : Note SN < moyenne requise → l'UE est marquée "à rattraper", gérée en session de rattrapage. |
| **Postconditions** | Les notes CC et SN de l'UE sont enregistrées et utilisées pour le relevé de notes semestriel |

---

### UC-08 — Générer un bulletin de séquence

| Élément | Description |
|---|---|
| **Acteur principal** | Secrétaire (déclenchement) / Système (calcul) |
| **Objectif** | Produire le bulletin d'un apprenant après clôture d'une séquence |
| **Préconditions** | Toutes les matières de la classe ont soumis leurs notes pour la séquence |
| **Scénario nominal** | 1. La secrétaire (ou le système automatiquement) lance la clôture de la séquence pour une classe.<br>2. Le système calcule la moyenne pondérée par matière, la moyenne générale, le rang de l'apprenant dans la classe.<br>3. Le système génère le bulletin PDF individuel pour chaque apprenant.<br>4. Les bulletins sont archivés et consultables par l'apprenant/parent. |
| **Scénarios alternatifs** | A1 : Note manquante dans une matière → alerte, blocage de la clôture tant que non résolu (ou notation "NN" selon règle). |
| **Postconditions** | Un Bulletin est créé pour chaque apprenant de la classe pour cette séquence |

---

### UC-09 — Générer le relevé de notes (fin d'année classique / fin de semestre LMD)

| Élément | Description |
|---|---|
| **Acteur principal** | Secrétaire (déclenchement) / Système (calcul) |
| **Objectif** | Produire le relevé de notes officiel de l'apprenant |
| **Préconditions** | — *Primaire/Secondaire* : toutes les séquences de l'année sont clôturées.<br>— *Université* : toutes les UE du semestre ont des notes CC + SN validées. |
| **Scénario nominal** | 1. Sélection de l'apprenant (ou de toute la classe/promotion).<br>2. Le système agrège : moyennes de séquences (classique) ou moyennes d'UE + crédits ECTS obtenus (LMD).<br>3. Calcul de la moyenne générale, de la mention/décision (admis, ajourné, redoublant, crédits validés).<br>4. Génération du document PDF officiel, numéroté, signé numériquement (cachet établissement). |
| **Scénarios alternatifs** | A1 : UE non validée → mention "à rattraper" sur le relevé, calcul des crédits restants (LMD).<br>A2 : Relevé déjà généré → nouvelle version horodatée, ancienne archivée. |
| **Postconditions** | Un ReleveDeNotes est créé et archivé, disponible en téléchargement |

---

### UC-10 — Générer l'attestation de scolarité

| Élément | Description |
|---|---|
| **Acteur principal** | Secrétaire |
| **Objectif** | Délivrer une attestation prouvant que l'apprenant est régulièrement inscrit |
| **Préconditions** | L'apprenant possède une Inscription au statut "VALIDÉE" pour l'année académique en cours |
| **Scénario nominal** | 1. La secrétaire recherche l'apprenant.<br>2. Le système vérifie que l'inscription est active/validée.<br>3. Elle choisit le type d'attestation (scolarité, fréquentation, réussite).<br>4. Le système génère le document PDF pré-rempli (identité, classe, année académique, numéro d'attestation unique) et l'archive. |
| **Scénarios alternatifs** | A1 : Inscription non validée (paiement insuffisant) → blocage ou attestation "sous réserve" selon politique de l'établissement. |
| **Postconditions** | Une Attestation numérotée est générée et archivée |

---

### UC-11 — Générer la carte scolaire / d'étudiant

| Élément | Description |
|---|---|
| **Acteur principal** | Secrétaire |
| **Objectif** | Émettre la carte d'identification de l'apprenant |
| **Préconditions** | Inscription validée, photo de l'apprenant disponible |
| **Scénario nominal** | 1. Recherche de l'apprenant.<br>2. Le système pré-remplit le visuel de la carte (photo, matricule, nom, classe/filière, établissement, QR code, date de validité = fin d'année académique).<br>3. Génération du fichier (PDF prêt à imprimer / format carte).<br>4. Enregistrement dans l'historique des cartes émises. |
| **Scénarios alternatifs** | A1 : Carte perdue → réémission avec incrémentation d'un numéro de duplicata. |
| **Postconditions** | Une CarteScolaire est générée et liée à l'apprenant |

---

## 5. Diagramme de classes (modèle du domaine)

```mermaid
classDiagram
    class Personne {
        <<abstract>>
        +UUID id
        +String nom
        +String prenom
        +Date dateNaissance
        +String sexe
        +String telephone
        +String email
        +String adresse
        +String photo
    }

    class Utilisateur {
        +String login
        +String motDePasseHash
        +RoleEnum role
        +Boolean actif
        +seConnecter()
    }

    class Apprenant {
        +String matricule
        +StatutApprenant statut
    }

    class Enseignant {
        +String matricule
        +String specialite
        +Date dateEmbauche
    }

    class Personnel {
        +String matricule
        +String fonction
    }

    class Etablissement {
        +UUID id
        +String nom
        +TypeEtablissement type
        +String sigle
        +String adresse
    }

    class AnneeAcademique {
        +UUID id
        +String libelle
        +Date dateDebut
        +Date dateFin
        +StatutAnnee statut
    }

    class Filiere {
        +UUID id
        +String nom
        +String code
    }

    class Niveau {
        +UUID id
        +String libelle
        +Integer ordre
        +TypeSysteme typeSysteme
    }

    class Classe {
        +UUID id
        +String libelle
        +Integer effectifMax
    }

    class Matiere {
        +UUID id
        +String nom
        +Float coefficient
        +Integer creditsECTS
    }

    class AffectationEnseignant {
        +UUID id
        +Date dateAffectation
    }

    class FraisScolarite {
        +UUID id
        +Decimal montantTotal
        +Integer nombreTranches
    }

    class Tranche {
        +UUID id
        +Integer numero
        +Decimal montant
        +Date dateEcheance
    }

    class Inscription {
        +UUID id
        +Date dateInscription
        +TypeInscription typeInscription
        +StatutInscription statut
    }

    class Paiement {
        +UUID id
        +Decimal montant
        +Date datePaiement
        +ModePaiement modePaiement
    }

    class Recu {
        +UUID id
        +String numeroRecu
        +Date dateEmission
        +String fichierPDF
    }

    class Sequence {
        +UUID id
        +Integer numero
        +String libelle
    }

    class Semestre {
        +UUID id
        +Integer numero
    }

    class Note {
        +UUID id
        +Float valeur
        +TypeEvaluation typeEvaluation
        +Date dateSaisie
    }

    class Bulletin {
        +UUID id
        +Float moyenneGenerale
        +Integer rang
        +String fichierPDF
    }

    class ReleveDeNotes {
        +UUID id
        +Float moyenneGenerale
        +String mention
        +String fichierPDF
    }

    class Attestation {
        +UUID id
        +TypeAttestation type
        +String numero
        +String fichierPDF
    }

    class CarteScolaire {
        +UUID id
        +String numero
        +Date dateEmission
        +Date dateExpiration
        +String fichierPDF
    }

    Personne <|-- Apprenant
    Personne <|-- Enseignant
    Personne <|-- Personnel
    Utilisateur "1" --> "0..1" Enseignant
    Utilisateur "1" --> "0..1" Personnel

    Etablissement "1" --> "*" AnneeAcademique
    Etablissement "1" --> "*" Filiere
    Filiere "1" --> "*" Niveau
    Niveau "1" --> "*" Classe
    AnneeAcademique "1" --> "*" Classe
    Classe "1" --> "*" AffectationEnseignant
    Matiere "1" --> "*" AffectationEnseignant
    Enseignant "1" --> "*" AffectationEnseignant

    Niveau "1" --> "*" FraisScolarite
    AnneeAcademique "1" --> "*" FraisScolarite
    FraisScolarite "1" --> "*" Tranche

    Apprenant "1" --> "*" Inscription
    Classe "1" --> "*" Inscription
    Inscription "1" --> "1" FraisScolarite
    Inscription "1" --> "*" Paiement
    Tranche "1" --> "*" Paiement
    Paiement "1" --> "1" Recu

    AffectationEnseignant "1" --> "*" Note
    Apprenant "1" --> "*" Note
    Sequence "1" --> "*" Note
    Semestre "1" --> "*" Note

    Inscription "1" --> "*" Bulletin
    Sequence "1" --> "*" Bulletin
    Inscription "1" --> "*" ReleveDeNotes
    Semestre "0..1" --> "*" ReleveDeNotes

    Apprenant "1" --> "*" Attestation
    Apprenant "1" --> "*" CarteScolaire
```

**Notes de modélisation :**
- `Niveau.typeSysteme` distingue **CLASSIQUE** (primaire/secondaire, utilise `Sequence`) de **LMD** (université, utilise `Semestre` + `TypeEvaluation` CC/SN).
- `Matiere` porte `creditsECTS` uniquement significatif en mode LMD ; en mode classique on utilise `coefficient`.
- `FraisScolarite` est défini par couple (Niveau, AnnéeAcademique), permettant à l'admin de fixer un montant différent chaque année.
- Le **Centre de formation (DQP/CQP)** réutilise exactement le même modèle : `Filiere` = filière de formation, `Niveau` = le diplôme visé (DQP ou CQP), `Semestre`/`Sequence` selon le découpage choisi par l'établissement.

---

## 6. Diagrammes de séquence

### 6.1 Inscription d'un apprenant + génération de l'attestation

```mermaid
sequenceDiagram
    actor Secretaire
    participant UI as Interface
    participant SVC as ServiceInscription
    participant DB as Base de données
    participant DOC as ServiceDocuments

    Secretaire->>UI: Rechercher/Créer apprenant
    UI->>SVC: creerApprenant(donnees)
    SVC->>DB: INSERT Apprenant
    DB-->>SVC: apprenantId

    Secretaire->>UI: Sélectionner classe + année académique
    UI->>SVC: inscrire(apprenantId, classeId, anneeId)
    SVC->>DB: SELECT FraisScolarite(classeId, anneeId)
    DB-->>SVC: grilleFrais
    SVC->>DB: INSERT Inscription(statut=EN_COURS)
    DB-->>SVC: inscriptionId

    SVC-->>UI: Inscription créée + échéancier de tranches
    UI-->>Secretaire: Afficher échéancier

    opt Génération immédiate de l'attestation
        Secretaire->>UI: Demander attestation de scolarité
        UI->>DOC: genererAttestation(inscriptionId)
        DOC->>DB: Vérifier statut inscription
        DB-->>DOC: statut = VALIDÉE / EN_COURS
        DOC->>DOC: Générer PDF numéroté
        DOC->>DB: INSERT Attestation
        DOC-->>UI: fichier PDF
        UI-->>Secretaire: Télécharger/Imprimer attestation
    end
```

### 6.2 Paiement d'une tranche + génération du reçu

```mermaid
sequenceDiagram
    actor Comptable
    participant UI as Interface
    participant SVC as ServicePaiement
    participant DB as Base de données
    participant DOC as ServiceDocuments

    Comptable->>UI: Rechercher apprenant
    UI->>SVC: getEcheancier(inscriptionId)
    SVC->>DB: SELECT Tranches + Paiements
    DB-->>SVC: échéancier
    SVC-->>UI: Liste tranches (payées / dues / en retard)

    Comptable->>UI: Sélectionner tranche + saisir montant + mode
    UI->>SVC: enregistrerPaiement(trancheId, montant, mode)
    SVC->>DB: INSERT Paiement
    SVC->>SVC: Calculer solde tranche
    alt Solde = 0
        SVC->>DB: UPDATE Tranche(statut=PAYEE)
    else Solde > 0
        SVC->>DB: UPDATE Tranche(statut=PARTIELLE)
    end

    SVC->>DOC: genererRecu(paiementId)
    DOC->>DOC: Générer numéro de reçu séquentiel
    DOC->>DB: INSERT Recu
    DOC-->>SVC: fichier PDF
    SVC-->>UI: Confirmation + reçu
    UI-->>Comptable: Imprimer/Télécharger le reçu
```

### 6.3 Saisie des notes (classique) et génération du bulletin

```mermaid
sequenceDiagram
    actor Enseignant
    participant UI as Interface
    participant SVC as ServiceNotes
    participant DB as Base de données
    actor Secretaire
    participant DOCB as ServiceBulletin

    Enseignant->>UI: Sélectionner classe / matière / séquence
    UI->>SVC: getListeApprenants(classeId)
    SVC->>DB: SELECT Inscription WHERE classe & statut actif
    DB-->>SVC: liste apprenants
    SVC-->>UI: Afficher grille de saisie

    Enseignant->>UI: Saisir notes + valider
    UI->>SVC: soumettreNotes(matiereId, sequenceId, notes[])
    SVC->>DB: INSERT/UPDATE Note (verrouillé)
    SVC-->>UI: Confirmation

    Secretaire->>UI: Clôturer la séquence pour la classe
    UI->>DOCB: cloturerSequence(classeId, sequenceId)
    DOCB->>DB: Vérifier que toutes matières ont soumis
    alt Notes complètes
        DOCB->>DB: SELECT toutes les Notes de la séquence
        DOCB->>DOCB: Calculer moyennes, rangs
        DOCB->>DB: INSERT Bulletin (par apprenant)
        DOCB-->>UI: Bulletins générés (PDF)
    else Notes incomplètes
        DOCB-->>UI: Erreur - matière(s) manquante(s)
    end
```

### 6.4 Saisie CC/SN (LMD) et génération du relevé de notes semestriel

```mermaid
sequenceDiagram
    actor Enseignant
    participant UI as Interface
    participant SVC as ServiceNotesLMD
    participant DB as Base de données
    actor Secretaire
    participant DOCR as ServiceReleve

    Enseignant->>UI: Sélectionner filière / niveau / semestre / UE
    Enseignant->>UI: Choisir type évaluation (CC ou SN)
    UI->>SVC: soumettreNotes(ueId, semestreId, typeEval, notes[])
    SVC->>DB: INSERT/UPDATE Note(typeEvaluation)
    SVC->>SVC: Calculer moyenne UE = f(CC, SN) selon pondération
    SVC->>DB: UPDATE moyenneUE
    SVC-->>UI: Confirmation

    Secretaire->>UI: Générer relevé de notes du semestre
    UI->>DOCR: genererReleve(etudiantId, semestreId)
    DOCR->>DB: SELECT moyennes UE + crédits ECTS
    alt Toutes les UE évaluées
        DOCR->>DOCR: Calculer moyenne générale, crédits validés, décision
        DOCR->>DB: INSERT ReleveDeNotes
        DOCR-->>UI: Relevé PDF officiel
    else UE manquante(s)
        DOCR-->>UI: Erreur - UE(s) sans note SN
    end
```

---

## 7. Diagrammes d'état

### 7.1 Cycle de vie d'une Inscription

```mermaid
stateDiagram-v2
    [*] --> EN_COURS: Création du dossier
    EN_COURS --> VALIDEE: 1ère tranche payée
    EN_COURS --> ANNULEE: Abandon / rejet du dossier
    VALIDEE --> SUSPENDUE: Impayé après relance
    SUSPENDUE --> VALIDEE: Régularisation du paiement
    VALIDEE --> CLOTUREE: Fin d'année académique
    CLOTUREE --> [*]
    ANNULEE --> [*]
```

### 7.2 Cycle de vie d'une Tranche de paiement

```mermaid
stateDiagram-v2
    [*] --> EN_ATTENTE: Échéancier généré
    EN_ATTENTE --> PARTIELLE: Paiement partiel reçu
    EN_ATTENTE --> PAYEE: Paiement intégral reçu
    PARTIELLE --> PAYEE: Complément versé
    EN_ATTENTE --> EN_RETARD: Date échéance dépassée
    PARTIELLE --> EN_RETARD: Date échéance dépassée
    EN_RETARD --> PAYEE: Régularisation
    PAYEE --> [*]
```

---

## 8. Dictionnaire de données (extrait des entités clés)

| Entité | Attribut clé | Règle métier |
|---|---|---|
| `Apprenant` | `matricule` | Généré automatiquement, unique par établissement |
| `FraisScolarite` | `montantTotal`, `nombreTranches` | Configuré uniquement par un **Admin**, par (Niveau, AnnéeAcadémique) |
| `Tranche` | `montant` | Somme des tranches = `montantTotal` de `FraisScolarite` (contrôle bloquant) |
| `Inscription` | `statut` | Passe à `VALIDEE` seulement si la règle de validation (ex : 1ère tranche réglée) est respectée |
| `Note` | `typeEvaluation` | `SEQUENCE` (classique) ou `CC` / `SESSION_NORMALE` (LMD) — jamais mélangés dans un même établissement |
| `Bulletin` | `rang` | Recalculé à chaque clôture de séquence, sur l'ensemble des apprenants actifs de la classe |
| `ReleveDeNotes` | `mention` | Calculée selon barème (classique : Excellent/Bien/Passable… ; LMD : Admis/Ajourné + crédits ECTS validés) |
| `Attestation` / `CarteScolaire` | `numero` | Séquence unique par établissement, non réutilisable (traçabilité/audit) |

---

## 9. Recommandations d'architecture technique

- **Architecture** : API REST (backend) + Frontend web (admin/secrétariat/enseignants) + portail apprenant/parent, éventuellement application mobile pour les enseignants (saisie de notes hors-ligne synchronisable).
- **Multi-tenant** : chaque `Etablissement` est un tenant logique — toutes les requêtes sont filtrées par `etablissementId` (isolation stricte des données entre écoles).
- **Génération de documents** : moteur de templates PDF (reçus, bulletins, relevés, attestations, cartes) avec numérotation séquentielle horodatée et QR code de vérification d'authenticité.
- **Gestion des rôles** : RBAC (Role-Based Access Control) — un enseignant ne voit que ses classes/matières affectées ; un comptable ne voit que le module financier ; un admin ne configure que son propre établissement (sauf Super Admin).
- **Journalisation (audit trail)** : toute saisie de note, tout paiement et toute génération de document doivent être tracés (qui, quand, quoi) — essentiel pour un système académique/financier.
- **Verrouillage des données figées** : une fois une séquence/semestre clôturé(e) et un bulletin/relevé généré, les notes sources doivent être verrouillées (modification uniquement via processus de correction tracé).

---

*Document réalisé comme base d'analyse et de conception (spécifications fonctionnelles, cas d'utilisation, diagramme de classes, diagrammes de séquence et d'état) pour le développement du système DJAART SCHOOL.*
