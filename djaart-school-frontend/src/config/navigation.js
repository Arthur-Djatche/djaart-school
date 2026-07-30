const PARAMETRAGE_ROLES = ['super_admin', 'admin_etablissement']
const INSCRIPTION_ROLES = ['super_admin', 'admin_etablissement', 'secretaire', 'comptable']
const FINANCE_ROLES = ['super_admin', 'admin_etablissement', 'comptable']
const PEDAGOGIE_ROLES = ['super_admin', 'admin_etablissement', 'enseignant']
const BULLETINS_ROLES = ['super_admin', 'admin_etablissement', 'secretaire']

const CLASSIQUE = ['primaire', 'secondaire', 'centre_formation']
const UNIVERSITAIRE = ['universitaire']

// "permission" : cle du catalogue App\Support\GrantablePermissions (backend) —
// un admin_etablissement peut l'accorder individuellement a un acteur, qui
// voit alors apparaitre l'entree meme sans le role habituellement requis.
// "Comptes utilisateurs" et "Demandes de démo" n'en ont volontairement pas :
// jamais delegables.
export const NAVIGATION = [
  { label: 'Tableau de bord', to: '/dashboard', roles: null },
  { label: 'Inscriptions', to: '/inscriptions', roles: INSCRIPTION_ROLES, permission: 'acces.inscriptions' },
  { label: 'Apprenants', to: '/apprenants', roles: BULLETINS_ROLES, permission: 'acces.apprenants' },
  { label: 'Séance photo', to: '/apprenants/seance-photo', roles: BULLETINS_ROLES, permission: 'acces.seance_photo' },
  { label: 'Documents en masse', to: '/documents/masse', roles: BULLETINS_ROLES, permission: 'acces.documents_masse' },
  {
    label: 'Paramétrage',
    roles: PARAMETRAGE_ROLES,
    children: [
      { label: 'Établissement(s)', to: '/parametrage/etablissements', roles: PARAMETRAGE_ROLES, permission: 'acces.parametrage_etablissement' },
      { label: 'Années académiques', to: '/parametrage/annees-academiques', roles: PARAMETRAGE_ROLES, permission: 'acces.parametrage_academique' },
      { label: 'Départements', to: '/parametrage/departements', roles: PARAMETRAGE_ROLES, etablissementTypes: UNIVERSITAIRE, permission: 'acces.parametrage_academique' },
      { label: 'Filières', to: '/parametrage/filieres', roles: PARAMETRAGE_ROLES, permission: 'acces.parametrage_academique' },
      { label: 'Classes', to: '/parametrage/classes', roles: PARAMETRAGE_ROLES, permission: 'acces.parametrage_academique' },
      { label: 'Matières', to: '/parametrage/matieres', roles: PARAMETRAGE_ROLES, permission: 'acces.parametrage_academique' },
      { label: "Unités d'enseignement", to: '/parametrage/unites-enseignement', roles: PARAMETRAGE_ROLES, etablissementTypes: UNIVERSITAIRE, permission: 'acces.parametrage_academique' },
    ],
  },
  {
    label: 'Finance',
    roles: FINANCE_ROLES,
    children: [
      { label: 'Frais de scolarité', to: '/finance/frais-scolarite', roles: PARAMETRAGE_ROLES, permission: 'acces.frais_scolarite' },
      { label: 'Caisse', to: '/finance/caisse', roles: FINANCE_ROLES, permission: 'acces.caisse' },
    ],
  },
  {
    label: 'Pédagogie',
    roles: [...PEDAGOGIE_ROLES, 'secretaire'],
    children: [
      { label: 'Affectations', to: '/pedagogie/affectations', roles: PARAMETRAGE_ROLES, permission: 'acces.affectations' },
      { label: 'Séquences', to: '/pedagogie/sequences', roles: PARAMETRAGE_ROLES, etablissementTypes: CLASSIQUE, permission: 'acces.sequences' },
      { label: 'Semestres', to: '/pedagogie/semestres', roles: PARAMETRAGE_ROLES, etablissementTypes: UNIVERSITAIRE, permission: 'acces.semestres' },
      { label: 'Saisie des notes', to: '/pedagogie/notes', roles: PEDAGOGIE_ROLES, permission: 'acces.notes' },
      { label: 'Saisie de la conduite', to: '/pedagogie/conduite', roles: [...PEDAGOGIE_ROLES, 'secretaire'], etablissementTypes: CLASSIQUE, permission: 'acces.conduite' },
      { label: 'Bulletins', to: '/pedagogie/bulletins', roles: BULLETINS_ROLES, etablissementTypes: CLASSIQUE, permission: 'acces.bulletins' },
      { label: 'Relevés de notes', to: '/pedagogie/releves', roles: BULLETINS_ROLES, permission: 'acces.releves' },
    ],
  },
  { label: 'Comptes utilisateurs', to: '/users', roles: ['super_admin', 'admin_etablissement'] },
  { label: 'Demandes de démo', to: '/demandes-demo', roles: ['super_admin'] },
  { label: 'Commandes', to: '/commandes', roles: ['super_admin'] },
]

function allowed(roles, permission, userRoles, userPermissions) {
  const parRole = !roles || roles.some((role) => userRoles.includes(role))
  const parPermission = Boolean(permission) && userPermissions.includes(permission)

  return parRole || parPermission
}

function allowedForType(etablissementTypes, etablissementType) {
  return !etablissementTypes || !etablissementType || etablissementTypes.includes(etablissementType)
}

export function navigationForRoles(userRoles = [], etablissementType = null, userPermissions = []) {
  return NAVIGATION.map((item) => {
    if (!item.children) {
      return allowed(item.roles, item.permission, userRoles, userPermissions) && allowedForType(item.etablissementTypes, etablissementType)
        ? item
        : null
    }

    const children = item.children.filter(
      (child) => allowed(child.roles, child.permission, userRoles, userPermissions) && allowedForType(child.etablissementTypes, etablissementType),
    )

    return children.length > 0 ? { ...item, children } : null
  }).filter(Boolean)
}
