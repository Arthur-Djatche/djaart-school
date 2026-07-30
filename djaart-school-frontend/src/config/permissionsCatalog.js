// Miroir frontend de App\Support\GrantablePermissions (backend) — mêmes 15
// clés, regroupées par domaine pour l'affichage dans UserPermissionsModal.
// "Comptes utilisateurs" et "Demandes de démo" restent hors catalogue :
// jamais délégables.
const CLASSIQUE = ['primaire', 'secondaire', 'centre_formation']
const UNIVERSITAIRE = ['universitaire']

export const PERMISSIONS_CATALOG = [
  {
    domaine: 'Inscriptions & apprenants',
    droits: [
      { cle: 'acces.inscriptions', label: 'Inscriptions' },
      { cle: 'acces.apprenants', label: 'Apprenants' },
      { cle: 'acces.seance_photo', label: 'Séance photo' },
      { cle: 'acces.documents_masse', label: 'Documents en masse' },
    ],
  },
  {
    domaine: 'Finance',
    droits: [
      { cle: 'acces.frais_scolarite', label: 'Frais de scolarité' },
      { cle: 'acces.caisse', label: 'Caisse' },
    ],
  },
  {
    domaine: 'Pédagogie',
    droits: [
      { cle: 'acces.affectations', label: 'Affectations' },
      { cle: 'acces.sequences', label: 'Séquences', etablissementTypes: CLASSIQUE },
      { cle: 'acces.semestres', label: 'Semestres', etablissementTypes: UNIVERSITAIRE },
      { cle: 'acces.notes', label: 'Saisie des notes' },
      { cle: 'acces.conduite', label: 'Saisie de la conduite', etablissementTypes: CLASSIQUE },
      { cle: 'acces.bulletins', label: 'Bulletins', etablissementTypes: CLASSIQUE },
      { cle: 'acces.releves', label: 'Relevés de notes' },
    ],
  },
  {
    domaine: 'Paramétrage',
    droits: [
      { cle: 'acces.parametrage_etablissement', label: 'Établissement(s)' },
      { cle: 'acces.parametrage_academique', label: 'Années, départements, filières, classes, matières, UE' },
    ],
  },
]

// Ne garde que les droits pertinents pour le(s) type(s) d'etablissement
// donnes (un droit sans etablissementTypes est toujours pertinent).
export function filtrerCataloguePourTypes(catalogue, etablissementTypes = []) {
  return catalogue
    .map((groupe) => ({
      ...groupe,
      droits: groupe.droits.filter(
        (droit) => !droit.etablissementTypes || droit.etablissementTypes.some((type) => etablissementTypes.includes(type)),
      ),
    }))
    .filter((groupe) => groupe.droits.length > 0)
}
