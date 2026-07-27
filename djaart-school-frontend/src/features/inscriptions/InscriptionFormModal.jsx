import { useEffect, useState } from 'react'
import Button from '../../components/ui/Button'
import Input from '../../components/ui/Input'
import Modal from '../../components/ui/Modal'
import Select from '../../components/ui/Select'
import * as inscriptionApi from '../../api/inscriptionApi'
import * as parametrageApi from '../../api/parametrageApi'
import * as financeApi from '../../api/financeApi'

const SEXES = [
  { value: 'F', label: 'Féminin' },
  { value: 'M', label: 'Masculin' },
]

export default function InscriptionFormModal({ onClose, onSubmit }) {
  const [classes, setClasses] = useState([])
  const [classeId, setClasseId] = useState('')
  const [fraisRecap, setFraisRecap] = useState(null)
  const [loadingRecap, setLoadingRecap] = useState(false)

  const [search, setSearch] = useState('')
  const [results, setResults] = useState([])
  const [searching, setSearching] = useState(false)
  const [selectedApprenant, setSelectedApprenant] = useState(null)
  const [showNewApprenantForm, setShowNewApprenantForm] = useState(false)
  const [newApprenant, setNewApprenant] = useState({
    nom: '', prenom: '', date_naissance: '', sexe: 'F', telephone: '', email: '', adresse: '',
  })

  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    (async () => {
      const { data } = await parametrageApi.fetchClasses({ page: 1 })
      setClasses(data.data)
    })()
  }, [])

  useEffect(() => {
    if (!classeId) {
      setFraisRecap(null)
      return
    }
    const classe = classes.find((c) => c.id === Number(classeId))
    if (!classe) return

    setLoadingRecap(true)
    financeApi
      .fetchFraisScolarite({ niveau_id: classe.niveau_id, annee_academique_id: classe.annee_academique_id })
      .then(({ data }) => setFraisRecap(data.data[0] ?? null))
      .finally(() => setLoadingRecap(false))
  }, [classeId, classes])

  const handleSearch = async () => {
    setSearching(true)
    try {
      const { data } = await inscriptionApi.searchApprenants(search)
      setResults(data.data)
    } finally {
      setSearching(false)
    }
  }

  const chooseApprenant = (apprenant) => {
    setSelectedApprenant(apprenant)
    setShowNewApprenantForm(false)
    setResults([])
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    if (!classeId) {
      setError('Veuillez sélectionner une classe.')
      return
    }
    if (!selectedApprenant && !showNewApprenantForm) {
      setError('Veuillez rechercher un apprenant existant ou en créer un nouveau.')
      return
    }
    setLoading(true)
    try {
      const payload = { classe_id: Number(classeId) }
      if (selectedApprenant) {
        payload.apprenant_id = selectedApprenant.id
      } else {
        payload.apprenant = newApprenant
      }
      await onSubmit(payload)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title="Nouvelle inscription" onClose={onClose}>
      <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
        <div className="flex flex-col gap-2 rounded-lg border border-slate-200 p-3">
          <p className="text-sm font-medium text-brand-navy">1. Apprenant</p>

          {selectedApprenant ? (
            <div className="flex items-center justify-between rounded bg-slate-50 px-3 py-2 text-sm">
              <span>
                {selectedApprenant.matricule} — {selectedApprenant.prenom} {selectedApprenant.nom}
              </span>
              <Button type="button" variant="ghost" onClick={() => setSelectedApprenant(null)}>
                Changer
              </Button>
            </div>
          ) : showNewApprenantForm ? (
            <div className="flex flex-col gap-3">
              <div className="flex justify-end">
                <Button type="button" variant="ghost" onClick={() => setShowNewApprenantForm(false)}>
                  Annuler la création
                </Button>
              </div>
              <Input id="nom" label="Nom" value={newApprenant.nom} onChange={(e) => setNewApprenant({ ...newApprenant, nom: e.target.value })} required />
              <Input id="prenom" label="Prénom" value={newApprenant.prenom} onChange={(e) => setNewApprenant({ ...newApprenant, prenom: e.target.value })} required />
              <Input id="date_naissance" type="date" label="Date de naissance" value={newApprenant.date_naissance} onChange={(e) => setNewApprenant({ ...newApprenant, date_naissance: e.target.value })} required />
              <Select id="sexe" label="Sexe" value={newApprenant.sexe} onChange={(e) => setNewApprenant({ ...newApprenant, sexe: e.target.value })} options={SEXES} />
              <Input id="telephone" label="Téléphone" value={newApprenant.telephone} onChange={(e) => setNewApprenant({ ...newApprenant, telephone: e.target.value })} />
              <Input id="email" type="email" label="E-mail" value={newApprenant.email} onChange={(e) => setNewApprenant({ ...newApprenant, email: e.target.value })} />
              <Input id="adresse" label="Adresse" value={newApprenant.adresse} onChange={(e) => setNewApprenant({ ...newApprenant, adresse: e.target.value })} />
            </div>
          ) : (
            <div className="flex flex-col gap-2">
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
                  {results.map((apprenant) => (
                    <li key={apprenant.id} className="flex items-center justify-between px-3 py-2 text-sm">
                      <span>{apprenant.matricule} — {apprenant.prenom} {apprenant.nom}</span>
                      <Button type="button" variant="ghost" onClick={() => chooseApprenant(apprenant)}>
                        Choisir
                      </Button>
                    </li>
                  ))}
                </ul>
              )}
              <Button type="button" variant="ghost" onClick={() => setShowNewApprenantForm(true)}>
                + Nouvel apprenant
              </Button>
            </div>
          )}
        </div>

        <div className="flex flex-col gap-2 rounded-lg border border-slate-200 p-3">
          <p className="text-sm font-medium text-brand-navy">2. Classe</p>
          <Select
            id="classe_id"
            value={classeId}
            onChange={(e) => setClasseId(e.target.value)}
            placeholder="Sélectionner une classe"
            options={classes.map((c) => ({ value: c.id, label: c.libelle }))}
          />

          {loadingRecap && <p className="text-sm text-slate-500">Chargement de l'échéancier…</p>}
          {fraisRecap && (
            <div className="rounded bg-slate-50 p-3 text-sm">
              <p className="font-medium text-brand-navy">
                Échéancier — {fraisRecap.montant_total} ({fraisRecap.mode === 'comptant' ? 'comptant' : 'tranches'})
              </p>
              <p className="text-brand-orange">
                Frais d'inscription : {fraisRecap.frais_inscription} — à encaisser pour valider l'inscription
              </p>
              <ul className="mt-1 flex flex-col gap-1">
                {fraisRecap.tranches.map((t) => (
                  <li key={t.numero}>Tranche {t.numero} : {t.montant} — échéance {t.date_echeance}</li>
                ))}
              </ul>
            </div>
          )}
          {classeId && !loadingRecap && !fraisRecap && (
            <p className="text-sm text-red-600">Aucune grille de frais configurée pour cette classe.</p>
          )}
        </div>

        {error && <p className="text-sm text-red-600">{error}</p>}
        <div className="flex justify-end gap-2">
          <Button type="button" variant="ghost" onClick={onClose}>
            Annuler
          </Button>
          <Button type="submit" loading={loading}>
            Inscrire
          </Button>
        </div>
      </form>
    </Modal>
  )
}
