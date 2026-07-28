import { useEffect, useRef, useState } from 'react'
import Button from '../../../components/ui/Button'
import * as apprenantsApi from '../../../api/apprenantsApi'
import useToast from '../../../hooks/useToast'

const LARGEUR_MAX = 1280

export default function CameraCaptureMode({ roster }) {
  const { showToast } = useToast()
  const videoRef = useRef(null)
  const canvasRef = useRef(null)
  const streamRef = useRef(null)
  const [index, setIndex] = useState(0)
  const [erreurCamera, setErreurCamera] = useState('')
  const [capturing, setCapturing] = useState(false)

  const termine = index >= roster.length
  const apprenantCourant = roster[index]?.apprenant

  useEffect(() => {
    if (termine) return undefined

    let annule = false

    ;(async () => {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: { ideal: 'environment' } },
          audio: false,
        })
        if (annule) {
          stream.getTracks().forEach((track) => track.stop())
          return
        }
        streamRef.current = stream
        if (videoRef.current) {
          videoRef.current.srcObject = stream
        }
        setErreurCamera('')
      } catch {
        setErreurCamera(
          "Impossible d'accéder à la caméra. Vérifiez les autorisations du navigateur, ou utilisez l'import de photos.",
        )
      }
    })()

    return () => {
      annule = true
      streamRef.current?.getTracks().forEach((track) => track.stop())
      streamRef.current = null
    }
  }, [termine])

  const capturer = async () => {
    const video = videoRef.current
    const canvas = canvasRef.current
    if (!video || !canvas || !apprenantCourant) return

    const ratio = video.videoWidth > LARGEUR_MAX ? LARGEUR_MAX / video.videoWidth : 1
    canvas.width = video.videoWidth * ratio
    canvas.height = video.videoHeight * ratio
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height)

    setCapturing(true)
    try {
      const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9))
      const file = new File([blob], `${apprenantCourant.matricule}.jpg`, { type: 'image/jpeg' })
      await apprenantsApi.uploadPhoto(apprenantCourant.id, file)
      showToast(`Photo enregistrée — ${apprenantCourant.prenom} ${apprenantCourant.nom}`, 'success')
      setIndex((current) => current + 1)
    } catch (err) {
      showToast(err.response?.data?.message ?? "Échec de l'enregistrement de la photo.", 'error')
    } finally {
      setCapturing(false)
    }
  }

  if (termine) {
    return (
      <div className="rounded-xl border border-slate-200 bg-white p-6 text-center">
        <p className="text-lg font-semibold text-brand-navy">Séance photo terminée</p>
        <p className="mt-1 text-sm text-slate-500">
          {roster.length} apprenant{roster.length > 1 ? 's' : ''} photographié{roster.length > 1 ? 's' : ''}.
        </p>
        <Button className="mt-4" variant="ghost" onClick={() => setIndex(0)}>
          Recommencer la séance
        </Button>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
        <span className="font-medium text-brand-navy">
          {apprenantCourant?.matricule} — {apprenantCourant?.prenom} {apprenantCourant?.nom}
        </span>
        <span className="rounded-full bg-brand-blue-tint px-3 py-1 text-sm font-medium text-brand-blue">
          {index + 1} / {roster.length}
        </span>
      </div>

      {erreurCamera ? (
        <p className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-600">{erreurCamera}</p>
      ) : (
        <div className="overflow-hidden rounded-xl bg-black">
          {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
          <video ref={videoRef} autoPlay playsInline muted className="aspect-video w-full object-contain" />
        </div>
      )}
      <canvas ref={canvasRef} className="hidden" />

      <div className="flex flex-col gap-2 sm:flex-row">
        <Button onClick={capturer} loading={capturing} disabled={!!erreurCamera} className="flex-1 justify-center" size="lg">
          Capturer la photo
        </Button>
        <Button variant="ghost" onClick={() => setIndex((current) => current + 1)} className="sm:w-auto">
          Passer cet apprenant
        </Button>
      </div>
    </div>
  )
}
