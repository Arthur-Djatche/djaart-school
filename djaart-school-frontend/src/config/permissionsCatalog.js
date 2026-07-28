// Miroir frontend de App\Support\GrantablePermissions (backend) — mêmes 15
// clés, regroupées par domaine pour l'affichage dans UserPermissionsModal.
// "Comptes utilisateurs" et "Demandes de démo" restent hors catalogue :
// jamais délégables.
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
      { cle: 'acces.sequences', label: 'Séquences' },
      { cle: 'acces.semestres', label: 'Semestres' },
      { cle: 'acces.notes', label: 'Saisie des notes' },
      { cle: 'acces.conduite', label: 'Saisie de la conduite' },
      { cle: 'acces.bulletins', label: 'Bulletins' },
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
