import { useEffect, useState } from 'react'

function dejaInstallee() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true
}

function estIosSafari() {
  const ua = window.navigator.userAgent
  return /iphone|ipad|ipod/i.test(ua) && /safari/i.test(ua) && !/crios|fxios|edgios/i.test(ua)
}

// Bouton flottant "Installer l'app" — Chrome/Edge/Android exposent
// l'evenement beforeinstallprompt qu'on intercepte pour declencher
// l'installation nous-memes plutot que d'attendre le menu du navigateur.
// iOS Safari n'expose aucun evenement de ce type (aucune API d'installation
// programmatique) : on y affiche a la place une astuce "Partager > Sur
// l'ecran d'accueil", seul chemin d'installation possible sur cette plateforme.
export default function InstallPwaButton() {
  const [evenementInstall, setEvenementInstall] = useState(null)
  const [installee, setInstallee] = useState(dejaInstallee)
  const [astuceIos, setAstuceIos] = useState(false)

  useEffect(() => {
    if (installee) return

    const onBeforeInstallPrompt = (event) => {
      event.preventDefault()
      setEvenementInstall(event)
    }
    const onAppInstalled = () => {
      setInstallee(true)
      setEvenementInstall(null)
    }

    window.addEventListener('beforeinstallprompt', onBeforeInstallPrompt)
    window.addEventListener('appinstalled', onAppInstalled)
    return () => {
      window.removeEventListener('beforeinstallprompt', onBeforeInstallPrompt)
      window.removeEventListener('appinstalled', onAppInstalled)
    }
  }, [installee])

  if (installee) return null

  const handleClick = async () => {
    if (evenementInstall) {
      evenementInstall.prompt()
      await evenementInstall.userChoice
      setEvenementInstall(null)
      return
    }
    if (estIosSafari()) {
      setAstuceIos(true)
    }
  }

  if (!evenementInstall && !estIosSafari()) return null

  return (
    <>
      <button
        type="button"
        onClick={handleClick}
        className="fixed bottom-5 right-5 z-30 flex items-center gap-2 rounded-full bg-brand-orange px-4 py-3 text-sm font-semibold text-white shadow-2xl transition hover:bg-brand-orange-light sm:bottom-6 sm:right-6"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          <path d="M12 3v12" />
          <path d="M7 10l5 5 5-5" />
          <path d="M4 19h16" />
        </svg>
        Installer l'app
      </button>

      {astuceIos && (
        <div
          className="fixed inset-0 z-40 flex items-end justify-center bg-brand-navy/50 p-4 sm:items-center"
          onClick={() => setAstuceIos(false)}
        >
          <div className="w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl" onClick={(e) => e.stopPropagation()}>
            <p className="mb-2 text-sm font-semibold text-brand-navy">Installer DJAART SCHOOL sur iPhone/iPad</p>
            <p className="text-sm text-slate-600">
              Appuyez sur le bouton <strong>Partager</strong> (icône carrée avec une flèche vers le haut) dans la
              barre de Safari, puis choisissez <strong>« Sur l'écran d'accueil »</strong>.
            </p>
            <button
              type="button"
              onClick={() => setAstuceIos(false)}
              className="mt-4 w-full rounded-lg bg-brand-blue px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-blue-light"
            >
              Compris
            </button>
          </div>
        </div>
      )}
    </>
  )
}
