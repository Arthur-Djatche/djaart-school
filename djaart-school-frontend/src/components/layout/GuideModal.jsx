import { Link } from 'react-router-dom'
import Modal from '../ui/Modal'
import useAuth from '../../hooks/useAuth'
import { guideEtapesPourActeur } from '../../config/guideSteps'

export default function GuideModal({ onClose }) {
  const { user } = useAuth()
  const etablissementTypes = [user?.etablissement?.type_etablissement].filter(Boolean)
  const etapes = guideEtapesPourActeur(user?.roles ?? [], etablissementTypes)

  return (
    <Modal title="Guide d'utilisation" onClose={onClose} size="lg">
      <p className="mb-4 text-sm text-slate-500">
        Ordre recommandé pour paramétrer votre établissement et le faire vivre au quotidien — chaque étape dépend
        généralement de la précédente. Cliquez sur un titre pour vous rendre directement sur l'écran concerné.
      </p>
      <div className="flex flex-col gap-4">
        {etapes.map((etape) => (
          <div key={etape.titre} className="rounded-xl border border-slate-200 p-4">
            <Link to={etape.to} onClick={onClose} className="text-sm font-semibold text-brand-blue hover:underline">
              {etape.titre}
            </Link>
            <p className="mt-1 text-sm text-slate-600">{etape.description}</p>
          </div>
        ))}
      </div>
    </Modal>
  )
}
