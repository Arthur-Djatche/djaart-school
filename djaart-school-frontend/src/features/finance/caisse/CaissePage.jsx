import { useState } from 'react'
import DashboardLayout from '../../../components/layout/DashboardLayout'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import * as inscriptionApi from '../../../api/inscriptionApi'
import * as paiementApi from '../../../api/paiementApi'
import useToast from '../../../hooks/useToast'
import PaiementFormModal from './PaiementFormModal'

const STATUT_BADGES = {
  payee: 'bg-brand-teal/10 text-brand-teal',
  partielle: 'bg-brand-orange/10 text-brand-orange',
  en_attente: 'bg-slate-100 text-slate-600',
  en_retard: 'bg-red-100 text-red-600',
}

const STATUT_LABELS = {
  payee: 'Payée',
  partielle: 'Partielle',
  en_attente: 'En attente',
  en_retard: 'En retard',
}

const MODE_LABELS = {
  especes: 'Espèces',
  mobile_money: 'Mobile Money',
  virement: 'Virement',
  cheque: 'Chèque',
}

export default function CaissePage() {
  const { showToast } = useToast()
  const [search, setSearch] = useState('')
  const [results, setResults] = useState([])
  const [searching, setSearching] = useState(false)
  const [apprenant, setApprenant] = useState(null)
  const [inscriptions, setInscriptions] = useState([])
  const [historique, setHistorique] = useState([])
  const [loadingEcheancier, setLoadingEcheancier] = useState(false)
  const [inscriptionAEncaisser, setInscriptionAEncaisser] = useState(null)
  const [dernierRecuUrl, setDernierRecuUrl] = useState(null)

  const loadEcheancier = async (apprenantId) => {
    setLoadingEcheancier(true)
    try {
      const [{ data: echeancierData }, { data: paiementsData }] = await Promise.all([
        paiementApi.fetchEcheancier(apprenantId),
        paiementApi.fetchPaiements({ apprenant_id: apprenantId }),
      ])
      setInscriptions(echeancierData.data.inscriptions)
      setHistorique(paiementsData.data)
    } finally {
      setLoadingEcheancier(false)
    }
  }

  const handleSearch = async () => {
    setSearching(true)
    try {
      const { data } = await inscriptionApi.searchApprenants(search)
      setResults(data.data)
    } finally {
      setSearching(false)
    }
  }

  const chooseApprenant = async (chosen) => {
    setApprenant(chosen)
    setResults([])
    setDernierRecuUrl(null)
    await loadEcheancier(chosen.id)
  }

  const handleEncaisser = async (payload) => {
    // Reserve la fenetre d'impression tout de suite, dans le meme geste
    // utilisateur que le clic sur "Encaisser" : ouvrir la fenetre APRES l'appel
    // asynchrone serait bloque par le navigateur comme un pop-up non sollicite
    // (meme lecon que le correctif rel="noreferrer" de la Phase 5).
    const fenetreImpression = window.open('', '_blank')

    const { data } = await paiementApi.createPaiement({
      inscription_id: inscriptionAEncaisser.id,
      ...payload,
    })
    showToast('Paiement enregistré.', 'success')
    setInscriptionAEncaisser(null)
    setDernierRecuUrl(paiementApi.recuDownloadUrl(data.data.recu.id))
    if (fenetreImpression) {
      fenetreImpression.location = `${window.location.origin}/imprimer-recu/${data.data.recu.id}`
    }
    await loadEcheancier(apprenant.id)
  }

  return (
    <DashboardLayout>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-brand-navy">Caisse</h1>
        <p className="text-sm text-slate-500">
          Recherchez un apprenant pour consulter son échéancier et encaisser un paiement (n'importe quel montant, tant qu'il ne dépasse pas le solde de la pension).
        </p>
      </div>

      <div className="mb-6 flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3">
        <div className="flex gap-2">
          <Input
            id="search"
            placeholder="Rechercher par nom ou matricule…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="flex-1"
          />
          <Button type="button" variant="ghost" loading={searching} onClick={handleSearch}>
            Rechercher
          </Button>
        </div>
        {results.length > 0 && (
          <ul className="flex flex-col divide-y divide-slate-100 rounded border border-slate-200">
            {results.map((r) => (
              <li key={r.id} className="flex items-center justify-between px-3 py-2 text-sm">
                <span>{r.matricule} — {r.prenom} {r.nom}</span>
                <Button type="button" variant="ghost" onClick={() => chooseApprenant(r)}>
                  Sélectionner
                </Button>
              </li>
            ))}
          </ul>
        )}
      </div>

      {apprenant && (
        <div className="flex flex-col gap-6">
          <div className="flex flex-col gap-2 rounded-lg bg-slate-50 p-3 text-sm sm:flex-row sm:items-center sm:justify-between">
            <span className="font-medium text-brand-navy">
              {apprenant.matricule} — {apprenant.prenom} {apprenant.nom}
            </span>
            {dernierRecuUrl && (
              <a
                href={dernierRecuUrl}
                target="_blank"
                rel="noopener"
                className="rounded-lg bg-brand-teal px-4 py-2 text-center text-sm font-medium text-white hover:brightness-95"
              >
                Télécharger le reçu du dernier paiement
              </a>
            )}
          </div>

          {loadingEcheancier ? (
            <p className="text-slate-500">Chargement de l'échéancier…</p>
          ) : (
            inscriptions.map((inscription) => (
              <div key={inscription.id} className="rounded-lg border border-slate-200 bg-white p-4">
                <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                  <p className="font-medium text-brand-navy">
                    {inscription.classe.libelle} — {inscription.annee_academique.libelle}
                  </p>
                  {inscription.solde_total_pension > 0 && (
                    <Button variant="accent" onClick={() => setInscriptionAEncaisser(inscription)} className="justify-center">
                      Encaisser un paiement
                    </Button>
                  )}
                </div>
                <p className="mb-3 text-sm text-slate-600">
                  Solde restant sur la pension :{' '}
                  <span className={inscription.solde_total_pension > 0 ? 'font-semibold text-red-600' : 'font-semibold text-brand-teal'}>
                    {inscription.solde_total_pension}
                  </span>
                </p>
                <div className="flex flex-col gap-2">
                  {inscription.tranches.map((tranche) => (
                    <div
                      key={tranche.id}
                      className="flex flex-col gap-1 rounded border border-slate-100 px-3 py-2 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-2"
                    >
                      <span>
                        Tranche {tranche.numero} — {tranche.montant} (échéance {tranche.date_echeance})
                      </span>
                      <span className={`w-fit rounded-full px-2 py-0.5 text-xs font-medium ${STATUT_BADGES[tranche.statut]}`}>
                        {STATUT_LABELS[tranche.statut]}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            ))
          )}

          <div className="rounded-lg border border-slate-200 bg-white p-4">
            <p className="mb-3 font-medium text-brand-navy">Historique des paiements</p>
            {historique.length === 0 ? (
              <p className="text-sm text-slate-500">Aucun paiement enregistré.</p>
            ) : (
              <ul className="flex flex-col divide-y divide-slate-100 text-sm">
                {historique.map((p) => (
                  <li key={p.id} className="flex flex-col gap-1 py-2 sm:flex-row sm:items-center sm:justify-between sm:gap-2">
                    <span>
                      {p.date_paiement} — {p.montant} ({MODE_LABELS[p.mode_paiement]})
                    </span>
                    {p.recu && (
                      <a
                        href={paiementApi.recuDownloadUrl(p.recu.id)}
                        target="_blank"
                        rel="noopener"
                        className="text-brand-blue hover:underline"
                      >
                        Reçu
                      </a>
                    )}
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>
      )}

      {inscriptionAEncaisser && (
        <PaiementFormModal
          inscription={inscriptionAEncaisser}
          onClose={() => setInscriptionAEncaisser(null)}
          onSubmit={handleEncaisser}
        />
      )}
    </DashboardLayout>
  )
}
