import { useParams } from 'react-router-dom'
import * as paiementApi from '../../../api/paiementApi'

/**
 * Page minimaliste ouverte automatiquement apres un encaissement (CaissePage) :
 * affiche le reçu en plein écran et déclenche aussitôt l'impression, sans
 * clic intermédiaire. La boîte de dialogue d'impression du navigateur reste
 * incontournable (aucune API web ne permet d'imprimer silencieusement) —
 * c'est le clic "Télécharger" qui disparaît, pas la boîte de dialogue.
 */
export default function PrintRecuPage() {
  const { recuId } = useParams()

  return (
    <iframe
      src={paiementApi.recuInlineUrl(recuId)}
      title="Reçu"
      onLoad={(event) => event.target.contentWindow?.print()}
      style={{ position: 'fixed', inset: 0, width: '100%', height: '100%', border: 'none' }}
    />
  )
}
