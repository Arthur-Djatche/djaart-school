export default function formatMontant(montant) {
  return `${new Intl.NumberFormat('fr-FR').format(montant)} FCFA`
}
