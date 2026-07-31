// Ordre recommande pour parametrer et faire vivre un etablissement dans
// DJAART SCHOOL — chaque etape depend techniquement de la precedente (ex. on
// ne peut pas inscrire un apprenant tant que les frais de scolarite du
// niveau/annee ne sont pas configures). etablissementTypes filtre les etapes
// non pertinentes pour le type de l'etablissement actif (meme convention que
// config/navigation.js) ; roles restreint l'etape aux acteurs concernes.
const UNIVERSITAIRE = ['universitaire']
const CLASSIQUE = ['primaire', 'secondaire', 'centre_formation']
const ADMIN = ['super_admin', 'admin_etablissement']

export const GUIDE_ETAPES = [
  {
    titre: '1. Établissement',
    to: '/parametrage/etablissements',
    roles: ADMIN,
    description:
      "Vérifiez le nom, le type et l'adresse de votre établissement. Ajoutez le logo, la signature (avec le grade du signataire) et l'en-tête depuis « Logo & signature » : ils apparaîtront sur tous les documents PDF (bulletins, relevés, reçus, attestations).",
  },
  {
    titre: '2. Année académique',
    to: '/parametrage/annees-academiques',
    roles: ADMIN,
    description:
      "Créez l'année en cours (ex. 2025-2026) avec ses dates de début et de fin, statut « en cours ». Tout le reste (classes, inscriptions, notes...) s'y rattache — sans année académique active, rien d'autre n'est possible.",
  },
  {
    titre: '3. Départements',
    to: '/parametrage/departements',
    roles: ADMIN,
    etablissementTypes: UNIVERSITAIRE,
    description:
      "Uniquement pour un établissement universitaire (LMD). Un département regroupe plusieurs filières/spécialités (ex. « Génie Informatique » regroupant Génie Logiciel et Réseaux). Créez-le avant vos filières.",
  },
  {
    titre: '4. Filières',
    to: '/parametrage/filieres',
    roles: ADMIN,
    description:
      "Une filière (ex. « Général », ou une spécialité universitaire rattachée à un département). Chaque niveau et chaque classe dépend d'une filière.",
  },
  {
    titre: '5. Niveaux',
    to: '/parametrage/filieres',
    roles: ADMIN,
    description:
      "Depuis une filière, ouvrez « Niveaux » pour créer les niveaux (ex. 6ème, Licence 1...). Chaque niveau précise son type de système (classique = séquences, LMD = semestres) — ce choix conditionne tout l'affichage des notes et bulletins pour ce niveau.",
  },
  {
    titre: '6. Classes',
    to: '/parametrage/classes',
    roles: ADMIN,
    description:
      "Une classe (ex. « 6ème A ») rattache un niveau à l'année académique en cours, avec un effectif maximum. C'est dans une classe que les apprenants sont inscrits.",
  },
  {
    titre: "7. Unités d'enseignement",
    to: '/parametrage/unites-enseignement',
    roles: ADMIN,
    etablissementTypes: UNIVERSITAIRE,
    description:
      "Uniquement en LMD. Une UE (fondamentale, professionnelle ou transversale) regroupe plusieurs matières (EC) au sein d'un semestre — à créer avant les matières.",
  },
  {
    titre: '8. Matières',
    to: '/parametrage/matieres',
    roles: ADMIN,
    description:
      "Rattachez chaque matière à un niveau (classique) ou à une UE/un semestre (LMD), avec son coefficient. En LMD, précisez aussi les crédits ECTS et les pondérations CC/Session Normale (leur somme doit faire 100).",
  },
  {
    titre: '9. Frais de scolarité',
    to: '/finance/frais-scolarite',
    roles: ADMIN,
    description:
      "Indispensable avant toute inscription : configurez le montant total, les frais d'inscription (inclus dans le total, pas un supplément) et le mode de paiement (comptant ou en tranches avec échéances) pour chaque niveau de l'année en cours. Une classe sans grille de frais rejette toute inscription.",
  },
  {
    titre: '10. Inscriptions',
    to: '/inscriptions',
    roles: [...ADMIN, 'secretaire', 'comptable'],
    description:
      "Inscrivez un apprenant (nouveau ou déjà connu) dans une classe. Un matricule est généré automatiquement. L'inscription passe à « validée » dès que les frais d'inscription (pas la tranche entière) sont encaissés en Caisse.",
  },
  {
    titre: '11. Affectations des enseignants',
    to: '/pedagogie/affectations',
    roles: ADMIN,
    description:
      "Affectez chaque enseignant à une classe et une matière pour l'année en cours. Sans affectation, un enseignant ne peut saisir aucune note — et la matière n'apparaîtra pas dans la liste à compléter pour clôturer une séquence.",
  },
  {
    titre: '12. Séquences',
    to: '/pedagogie/sequences',
    roles: ADMIN,
    etablissementTypes: CLASSIQUE,
    description:
      "Découpez l'année en séquences numérotées (ex. 3 séquences) pour chaque niveau. La saisie des notes et les bulletins s'organisent séquence par séquence.",
  },
  {
    titre: '13. Semestres',
    to: '/pedagogie/semestres',
    roles: ADMIN,
    etablissementTypes: UNIVERSITAIRE,
    description:
      "Équivalent LMD des séquences : Semestre 1, Semestre 2... Chaque UE/matière est rattachée à un semestre précis.",
  },
  {
    titre: '14. Saisie des notes',
    to: '/pedagogie/notes',
    roles: [...ADMIN, 'enseignant'],
    description:
      "Chaque enseignant saisit les notes de ses propres affectations (classique : par séquence ; LMD : CC puis Session Normale par semestre/UE), puis clique « Soumettre » pour verrouiller le lot. Un admin peut « déverrouiller » pour permettre une correction.",
  },
  {
    titre: '15. Bulletins (établissement classique)',
    to: '/pedagogie/bulletins',
    roles: [...ADMIN, 'secretaire'],
    etablissementTypes: CLASSIQUE,
    description:
      "Une fois TOUTES les matières affectées d'une classe saisies ET verrouillées pour la séquence choisie, sélectionnez la classe puis la séquence et cliquez « Clôturer la séquence » : les bulletins de toute la classe sont générés en un lot (moyenne, rang, PDF). S'il manque une matière, un message précis l'indique — impossible de clôturer partiellement.",
  },
  {
    titre: '16. Relevés de notes',
    to: '/pedagogie/releves',
    roles: [...ADMIN, 'secretaire'],
    description:
      "Classique : le relevé annuel exige qu'un bulletin existe déjà pour chaque séquence de l'année (clôturez-les toutes d'abord). LMD : le relevé semestriel exige que CC et Session Normale soient verrouillés pour chaque UE du semestre choisi — pas de bulletin intermédiaire en LMD, on va directement au relevé.",
  },
  {
    titre: '17. Documents (attestations, cartes scolaires, photos)',
    to: '/apprenants',
    roles: [...ADMIN, 'secretaire'],
    description:
      "Depuis la fiche d'un apprenant inscrit : photo, attestation, carte scolaire à l'unité. Pour tout un groupe d'un coup, utilisez « Séance photo » (import en masse par ordre alphabétique) et « Documents en masse » (génération + zip pour toute une classe).",
  },
  {
    titre: '18. Comptes utilisateurs',
    to: '/users',
    roles: ADMIN,
    description:
      "Ajoutez vos secrétaires, comptables et enseignants : un mot de passe est généré automatiquement et envoyé par e-mail (jamais choisi par vous). Un même e-mail déjà utilisé ailleurs est rattaché à ce nouvel établissement plutôt que dupliqué — utile si un acteur intervient dans plusieurs établissements avec un rôle différent dans chacun ; il bascule alors entre eux depuis le sélecteur du tableau de bord (Topbar, ou menu ☰ sur mobile).",
  },
]

export function guideEtapesPourActeur(roles = [], etablissementTypes = []) {
  return GUIDE_ETAPES.filter((etape) => {
    const parRole = !etape.roles || etape.roles.some((role) => roles.includes(role))
    const parType =
      !etape.etablissementTypes || etablissementTypes.length === 0 || etape.etablissementTypes.some((type) => etablissementTypes.includes(type))
    return parRole && parType
  })
}
