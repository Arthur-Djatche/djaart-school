import { useState } from 'react'
import { Link } from 'react-router-dom'
import logo from '../../assets/logo.png'
import Button from '../../components/ui/Button'
import useAuth from '../../hooks/useAuth'
import DemandeDemoModal from './DemandeDemoModal'

const ETAPES = [
  {
    titre: 'Créez votre établissement',
    texte: "Après votre demande de démo, notre équipe crée votre espace et paramètre votre premier compte administrateur.",
  },
  {
    titre: 'Paramétrez filières, classes et frais',
    texte: 'Filières, niveaux, classes et grilles de frais de scolarité (comptant ou en tranches) — configurés en quelques minutes.',
  },
  {
    titre: 'Invitez votre équipe',
    texte: 'Secrétariat, comptabilité, enseignants : chacun accède uniquement aux écrans utiles à son rôle.',
  },
  {
    titre: 'Gérez au quotidien',
    texte: 'Inscriptions, encaissements, notes, bulletins et documents officiels — tout au même endroit.',
  },
]

const FONCTIONNALITES = [
  { titre: 'Inscriptions & matricules automatiques', texte: 'Matricule unique généré à la volée, réinscriptions détectées automatiquement.' },
  { titre: 'Encaissement flexible & reçus', texte: "Encaissez n'importe quel montant contre le solde total de la pension, reçu PDF instantané." },
  { titre: 'Bulletins & relevés classique / LMD', texte: 'Moyennes, rangs, mentions, UE/EC, rattrapage — calculés automatiquement.' },
  { titre: 'Cartes scolaires & attestations', texte: 'Documents officiels avec QR code, générés à l\'unité ou en masse pour toute une classe.' },
  { titre: 'Tableaux de bord par rôle', texte: 'Chaque utilisateur voit les indicateurs pertinents pour sa fonction, rien de plus.' },
  { titre: 'Primaire, secondaire, université, centres de formation', texte: 'Un seul système pour tous les types d\'établissements, y compris les départements universitaires.' },
  { titre: 'Séance photo intégrée', texte: 'Photographiez une classe entière depuis le navigateur ou importez un lot de photos en un clic.' },
  { titre: 'Multi-établissements', texte: 'Pilotez plusieurs établissements depuis un même compte, avec une isolation stricte des données.' },
]

const FORMULES = [
  {
    nom: 'Starter',
    prix: '25 000',
    cible: "Jusqu'à 150 apprenants",
    avantages: ['1 établissement', 'Inscriptions & finances', 'Bulletins & relevés', 'Support par e-mail'],
    populaire: false,
  },
  {
    nom: 'Établissement',
    prix: '60 000',
    cible: "Jusqu'à 500 apprenants",
    avantages: [
      '1 établissement',
      'Tout Starter, plus :',
      'Cartes scolaires & attestations avec QR',
      'Documents & séance photo en masse',
      'Support prioritaire',
    ],
    populaire: true,
  },
  {
    nom: 'Réseau',
    prix: 'Sur devis',
    cible: 'Multi-établissements, apprenants illimités',
    avantages: ['Établissements illimités', 'Tout Établissement, plus :', 'Tableau de bord consolidé', 'Accompagnement dédié'],
    populaire: false,
  },
]

function NavLinkAnchor({ href, children }) {
  return (
    <a href={href} className="text-sm font-medium text-slate-600 transition hover:text-brand-blue">
      {children}
    </a>
  )
}

export default function LandingPage() {
  const { user } = useAuth()
  const [showDemoModal, setShowDemoModal] = useState(false)

  return (
    <div className="min-h-screen bg-white text-brand-navy">
      <header className="sticky top-0 z-30 border-b border-slate-100 bg-white/90 backdrop-blur">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6">
          <img src={logo} alt="DJAART SCHOOL" className="h-9 w-auto" />
          <nav className="hidden items-center gap-6 md:flex">
            <NavLinkAnchor href="#fonctionnalites">Fonctionnalités</NavLinkAnchor>
            <NavLinkAnchor href="#tarifs">Tarifs</NavLinkAnchor>
            <NavLinkAnchor href="#contact">Contact</NavLinkAnchor>
          </nav>
          <div className="flex items-center gap-2 sm:gap-3">
            {user ? (
              <Link to="/dashboard">
                <Button size="sm">Tableau de bord</Button>
              </Link>
            ) : (
              <Link to="/login">
                <Button variant="ghost" size="sm">
                  Se connecter
                </Button>
              </Link>
            )}
            <Button size="sm" variant="accent" onClick={() => setShowDemoModal(true)}>
              Demander une démo
            </Button>
          </div>
        </div>
      </header>

      <section className="brand-mesh relative overflow-hidden px-4 py-20 text-white sm:px-6">
        <div className="mx-auto grid max-w-6xl items-center gap-12 md:grid-cols-2">
          <div>
            <span className="inline-block rounded-full bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-brand-orange-light">
              Gestion scolaire tout-en-un
            </span>
            <h1 className="mt-4 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
              La gestion de votre établissement, enfin simple et moderne.
            </h1>
            <p className="mt-5 max-w-xl text-lg text-white/80">
              Inscriptions, finances, notes, bulletins et documents officiels — DJAART SCHOOL centralise tout, du
              primaire à l'université, avec un tableau de bord dédié à chaque membre de votre équipe.
            </p>
            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
              <Button size="lg" variant="accent" onClick={() => setShowDemoModal(true)}>
                Demander une démo gratuite
              </Button>
              <a href="#fonctionnalites">
                <Button size="lg" variant="outlineOnDark" className="w-full sm:w-auto">
                  Découvrir les fonctionnalités
                </Button>
              </a>
            </div>
          </div>

          <div className="rounded-2xl bg-white/10 p-4 shadow-2xl backdrop-blur">
            <div className="rounded-xl bg-white p-4 text-brand-navy shadow-soft">
              <div className="mb-3 flex items-center justify-between">
                <span className="text-sm font-semibold">Tableau de bord — Admin établissement</span>
                <span className="rounded-full bg-brand-teal-tint px-2 py-0.5 text-xs font-medium text-brand-teal">En ligne</span>
              </div>
              <div className="grid grid-cols-3 gap-2">
                {[
                  { label: 'Effectif', value: '842', color: 'bg-brand-blue-tint text-brand-blue' },
                  { label: 'Encaissé (mois)', value: '4,2M', color: 'bg-brand-teal-tint text-brand-teal' },
                  { label: 'Impayés', value: '18', color: 'bg-brand-orange-tint text-brand-orange' },
                ].map((carte) => (
                  <div key={carte.label} className={`rounded-lg p-3 ${carte.color}`}>
                    <p className="text-lg font-bold">{carte.value}</p>
                    <p className="text-xs opacity-80">{carte.label}</p>
                  </div>
                ))}
              </div>
              <div className="mt-3 flex flex-col gap-1.5">
                {[70, 45, 90, 30].map((largeur, index) => (
                  <div key={index} className="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div className="h-full rounded-full bg-brand-blue" style={{ width: `${largeur}%` }} />
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="px-4 py-20 sm:px-6">
        <div className="mx-auto max-w-6xl">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight">Comment commencer</h2>
            <p className="mt-3 text-slate-500">Un accompagnement simple, de la demande de démo à l'usage quotidien.</p>
          </div>
          <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {ETAPES.map((etape, index) => (
              <div key={etape.titre} className="rounded-2xl border border-slate-100 bg-white p-6 shadow-soft">
                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-brand-blue text-sm font-bold text-white">
                  {index + 1}
                </span>
                <h3 className="mt-4 font-semibold text-brand-navy">{etape.titre}</h3>
                <p className="mt-2 text-sm text-slate-500">{etape.texte}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="fonctionnalites" className="bg-brand-blue-tint/40 px-4 py-20 sm:px-6">
        <div className="mx-auto max-w-6xl">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight">Fonctionnalités clés</h2>
            <p className="mt-3 text-slate-500">Tout ce dont votre établissement a besoin, déjà intégré.</p>
          </div>
          <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {FONCTIONNALITES.map((fonctionnalite) => (
              <div key={fonctionnalite.titre} className="rounded-2xl bg-white p-6 shadow-soft">
                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-orange-tint text-lg text-brand-orange">
                  ★
                </span>
                <h3 className="mt-4 font-semibold text-brand-navy">{fonctionnalite.titre}</h3>
                <p className="mt-2 text-sm text-slate-500">{fonctionnalite.texte}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="tarifs" className="px-4 py-20 sm:px-6">
        <div className="mx-auto max-w-6xl">
          <div className="mx-auto max-w-2xl text-center">
            <h2 className="text-3xl font-bold tracking-tight">Des formules adaptées à votre établissement</h2>
            <p className="mt-3 text-slate-500">Tarifs indicatifs en FCFA, ajustés selon vos besoins réels lors de la démo.</p>
          </div>
          <div className="mt-12 grid gap-6 lg:grid-cols-3">
            {FORMULES.map((formule) => (
              <div
                key={formule.nom}
                className={`flex flex-col rounded-2xl border p-8 ${
                  formule.populaire ? 'border-brand-orange bg-white shadow-brand ring-2 ring-brand-orange' : 'border-slate-200 bg-white shadow-soft'
                }`}
              >
                {formule.populaire && (
                  <span className="mb-3 inline-block w-fit rounded-full bg-brand-orange px-3 py-1 text-xs font-semibold text-white">
                    Le plus populaire
                  </span>
                )}
                <h3 className="text-lg font-semibold text-brand-navy">{formule.nom}</h3>
                <p className="mt-1 text-sm text-slate-500">{formule.cible}</p>
                <p className="mt-6">
                  <span className="text-3xl font-bold text-brand-navy">{formule.prix}</span>
                  {formule.prix !== 'Sur devis' && <span className="text-sm text-slate-500"> FCFA / mois</span>}
                </p>
                <ul className="mt-6 flex flex-1 flex-col gap-2.5 text-sm text-slate-600">
                  {formule.avantages.map((avantage) => (
                    <li key={avantage} className="flex items-start gap-2">
                      <span className="mt-0.5 text-brand-teal">✓</span>
                      {avantage}
                    </li>
                  ))}
                </ul>
                <Button
                  variant={formule.populaire ? 'accent' : 'outline'}
                  className="mt-8 justify-center"
                  onClick={() => setShowDemoModal(true)}
                >
                  Demander une démo
                </Button>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section id="contact" className="bg-brand-navy px-4 py-16 text-center text-white sm:px-6">
        <div className="mx-auto max-w-2xl">
          <h2 className="text-3xl font-bold tracking-tight">Prêt à moderniser la gestion de votre établissement ?</h2>
          <p className="mt-3 text-white/70">Demandez une démo gratuite, sans engagement — notre équipe vous recontacte rapidement.</p>
          <Button size="lg" variant="accent" className="mt-8" onClick={() => setShowDemoModal(true)}>
            Demander une démo gratuite
          </Button>
        </div>
      </section>

      <footer className="border-t border-slate-100 px-4 py-10 sm:px-6">
        <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 text-sm text-slate-500 sm:flex-row">
          <img src={logo} alt="DJAART SCHOOL" className="h-7 w-auto" />
          <p>© {new Date().getFullYear()} DJAART SCHOOL — Tous droits réservés.</p>
          <a href="mailto:contact@djaart.school" className="text-brand-blue hover:underline">
            contact@djaart.school
          </a>
        </div>
      </footer>

      {showDemoModal && <DemandeDemoModal onClose={() => setShowDemoModal(false)} />}
    </div>
  )
}
