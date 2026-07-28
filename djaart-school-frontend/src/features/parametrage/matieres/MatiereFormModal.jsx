import { useEffect, useState } from 'react'
import Button from '../../../components/ui/Button'
import Input from '../../../components/ui/Input'
import Modal from '../../../components/ui/Modal'
import Select from '../../../components/ui/Select'
import * as parametrageApi from '../../../api/parametrageApi'
import * as pedagogieApi from '../../../api/pedagogieApi'

async function loadAllNiveaux() {
  const { data: filieresResponse } = await parametrageApi.fetchFilieres({ page: 1 })
  const niveauxLists = await Promise.all(
    filieresResponse.data.map(async (filiere) => {
      const { data } = await parametrageApi.fetchNiveauxByFiliere(filiere.id)
      return data.data.map((niveau) => ({ ...niveau, filiereNom: filiere.nom }))
    }),
  )
  return niveauxLists.flat()
}

export default function MatiereFormModal({ matiere, onClose, onSubmit }) {
  const [niveaux, setNiveaux] = useState([])
  const [niveauId, setNiveauId] = useState(matiere?.niveau_id ?? '')
  const [code, setCode] = useState(matiere?.code ?? '')
  const [nom, setNom] = useState(matiere?.nom ?? '')
  const [groupe, setGroupe] = useState(matiere?.groupe ?? '')
  const [coefficient, setCoefficient] = useState(matiere?.coefficient ?? 1)
  const [creditsEcts, setCreditsEcts] = useState(matiere?.credits_ects ?? '')
  const [ponderationCc, setPonderationCc] = useState(matiere?.ponderation_cc ?? 40)
  const [ponderationSn, setPonderationSn] = useState(matiere?.ponderation_session_normale ?? 60)
  const [semestres, setSemestres] = useState([])
  const [semestreId, setSemestreId] = useState(matiere?.semestre_id ?? '')
  const [unites, setUnites] = useState([])
  const [uniteEnseignementId, setUniteEnseignementId] = useState(matiere?.unite_enseignement_id ?? '')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const [loadingOptions, setLoadingOptions] = useState(true)

  useEffect(() => {
    (async () => {
      setNiveaux(await loadAllNiveaux())
      setLoadingOptions(false)
    })()
  }, [])

  const niveau = niveaux.find((n) => n.id === Number(niveauId))
  const estLmd = niveau?.type_systeme === 'lmd'

  useEffect(() => {
    setSemestres([])
    if (!estLmd || !niveauId) return
    (async () => {
      const { data } = await pedagogieApi.fetchSemestres({ niveau_id: niveauId })
      setSemestres(data.data)
    })()
  }, [niveauId, estLmd])

  useEffect(() => {
    setUnites([])
    if (!estLmd || !semestreId) return
    (async () => {
      const { data } = await parametrageApi.fetchUnitesEnseignementBySemestre(semestreId)
      setUnites(data.data)
    })()
  }, [semestreId, estLmd])

  const handleSubmit = async (event) => {
    event.preventDefault()
    setError('')
    setLoading(true)
    try {
      await onSubmit({
        niveau_id: Number(niveauId),
        code: code || null,
        nom,
        groupe: groupe || null,
        coefficient: Number(coefficient),
        credits_ects: creditsEcts ? Number(creditsEcts) : null,
        ponderation_cc: Number(ponderationCc),
        ponderation_session_normale: Number(ponderationSn),
        semestre_id: estLmd && semestreId ? Number(semestreId) : null,
        unite_enseignement_id: estLmd && uniteEnseignementId ? Number(uniteEnseignementId) : null,
      })
    } catch (err) {
      setError(err.response?.data?.message ?? 'Une erreur est survenue.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Modal title={matiere ? 'Modifier la matière' : 'Nouvelle matière'} onClose={onClose}>
      {loadingOptions ? (
        <p className="text-slate-500">Chargement…</p>
      ) : (
        <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
          <Select
            id="niveau_id"
            label="Niveau"
            value={niveauId}
            onChange={(e) => { setNiveauId(e.target.value); setSemestreId(''); setUniteEnseignementId('') }}
            placeholder="Sélectionner un niveau"
            options={niveaux.map((n) => ({ value: n.id, label: `${n.filiereNom} — ${n.libelle}` }))}
            required
          />

          {estLmd && (
            <>
              <Select
                id="semestre_id"
                label="Semestre (EC propre à ce semestre)"
                value={semestreId}
                onChange={(e) => { setSemestreId(e.target.value); setUniteEnseignementId('') }}
                placeholder="Sélectionner un semestre"
                options={semestres.map((s) => ({ value: s.id, label: s.libelle }))}
                required
              />
              {semestreId && (
                <Select
                  id="unite_enseignement_id"
                  label="Unité d'enseignement"
                  value={uniteEnseignementId}
                  onChange={(e) => setUniteEnseignementId(e.target.value)}
                  placeholder="Sélectionner une UE"
                  options={unites.map((u) => ({ value: u.id, label: `${u.code} — ${u.nom}` }))}
                  required
                />
              )}
              <Input id="code" label="Code EC (ex. IGL1111)" value={code} onChange={(e) => setCode(e.target.value)} />
            </>
          )}

          <Input id="nom" label="Nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
          <Input
            id="groupe"
            label="Groupe (bulletin classique, ex. « Groupe I »)"
            value={groupe}
            onChange={(e) => setGroupe(e.target.value)}
            placeholder="Laisser vide si non groupée"
          />
          <Input
            id="coefficient"
            type="number"
            step="0.5"
            min="0.5"
            label="Coefficient"
            value={coefficient}
            onChange={(e) => setCoefficient(e.target.value)}
            required
          />
          <Input
            id="credits_ects"
            type="number"
            min="1"
            label="Crédits ECTS (LMD uniquement)"
            value={creditsEcts}
            onChange={(e) => setCreditsEcts(e.target.value)}
          />
          <div className="flex gap-2">
            <Input
              id="ponderation_cc"
              type="number"
              min="0"
              max="100"
              label="Pondération CC % (LMD)"
              value={ponderationCc}
              onChange={(e) => setPonderationCc(e.target.value)}
              className="flex-1"
            />
            <Input
              id="ponderation_session_normale"
              type="number"
              min="0"
              max="100"
              label="Pondération SN % (LMD)"
              value={ponderationSn}
              onChange={(e) => setPonderationSn(e.target.value)}
              className="flex-1"
            />
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <div className="flex justify-end gap-2">
            <Button type="button" variant="ghost" onClick={onClose}>
              Annuler
            </Button>
            <Button type="submit" loading={loading}>
              Enregistrer
            </Button>
          </div>
        </form>
      )}
    </Modal>
  )
}
