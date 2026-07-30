<?php

namespace App\Services;

use App\Mail\BulletinPretMail;
use App\Models\AffectationEnseignant;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\ConduiteReleve;
use App\Models\Note;
use App\Models\Sequence;
use App\Services\Concerns\EmbedsEtablissementBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BulletinService
{
    use EmbedsEtablissementBranding;

    public function cloturerSequence(Classe $classe, Sequence $sequence): Collection
    {
        return DB::transaction(function () use ($classe, $sequence) {
            $affectations = AffectationEnseignant::where('classe_id', $classe->id)->with(['matiere', 'enseignant'])->get();

            if ($affectations->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => "Aucune matière n'est affectée à cette classe.",
                ]);
            }

            $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

            if ($inscriptions->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Aucun apprenant actif dans cette classe.',
                ]);
            }

            if (Bulletin::whereIn('inscription_id', $inscriptions->pluck('id'))->where('sequence_id', $sequence->id)->exists()) {
                throw ValidationException::withMessages([
                    'sequence_id' => 'Des bulletins existent déjà pour cette classe et cette séquence.',
                ]);
            }

            $manquants = [];
            $donneesParAffectation = [];

            foreach ($affectations as $affectation) {
                $notes = Note::where('affectation_id', $affectation->id)
                    ->where('sequence_id', $sequence->id)
                    ->where('type_evaluation', 'sequence')
                    ->get()
                    ->keyBy('apprenant_id');

                $verrouille = $notes->isNotEmpty() && $notes->first()->soumis_le !== null;
                $couvertureComplete = $inscriptions->every(fn ($inscription) => $notes->has($inscription->apprenant_id));

                if (! $verrouille || ! $couvertureComplete) {
                    $manquants[] = $affectation->matiere->nom;

                    continue;
                }

                $donneesParAffectation[] = ['matiere' => $affectation->matiere, 'enseignant' => $affectation->enseignant, 'notes' => $notes];
            }

            if ($manquants !== []) {
                throw ValidationException::withMessages([
                    'sequence_id' => 'Notes manquantes ou non verrouillées pour : '.implode(', ', $manquants).'.',
                ]);
            }

            $moyennes = [];

            foreach ($inscriptions as $inscription) {
                $sommeNotes = 0.0;
                $sommeCoefficients = 0.0;

                foreach ($donneesParAffectation as $donnee) {
                    $note = $donnee['notes']->get($inscription->apprenant_id);
                    $valeur = $note->absent ? 0.0 : (float) $note->valeur;
                    $coefficient = (float) $donnee['matiere']->coefficient;
                    $sommeNotes += $valeur * $coefficient;
                    $sommeCoefficients += $coefficient;
                }

                $moyennes[$inscription->id] = $sommeCoefficients > 0
                    ? round($sommeNotes / $sommeCoefficients, 2)
                    : 0.0;
            }

            $classement = $this->calculerRangs($moyennes);
            $effectif = $inscriptions->count();

            $valeursMoyennes = array_values($moyennes);
            $statistiquesClasse = [
                'moyenne_classe' => round(array_sum($valeursMoyennes) / count($valeursMoyennes), 2),
                'taux_reussite' => round(
                    count(array_filter($valeursMoyennes, fn ($m) => $m >= 10)) / count($valeursMoyennes) * 100,
                    1,
                ),
                'moyenne_max' => max($valeursMoyennes),
                'moyenne_min' => min($valeursMoyennes),
            ];

            $conduites = ConduiteReleve::where('sequence_id', $sequence->id)
                ->whereIn('inscription_id', $inscriptions->pluck('id'))
                ->get()
                ->keyBy('inscription_id');

            $bulletins = collect();

            foreach ($inscriptions as $inscription) {
                $lignes = collect($donneesParAffectation)->map(function ($donnee) use ($inscription) {
                    $note = $donnee['notes']->get($inscription->apprenant_id);

                    return [
                        'matiere' => $donnee['matiere']->nom,
                        'enseignant' => $donnee['enseignant']->name,
                        'groupe' => $donnee['matiere']->groupe ?? 'Non groupé',
                        'coefficient' => $donnee['matiere']->coefficient,
                        'valeur' => $note->absent ? 0 : $note->valeur,
                        'absent' => $note->absent,
                        'appreciation' => $note->absent ? null : $this->appreciation((float) $note->valeur),
                    ];
                })->all();

                $detailsGroupes = $this->calculerDetailsGroupes($donneesParAffectation, $inscription);
                $conduite = $conduites->get($inscription->id);

                $bulletin = Bulletin::create([
                    'etablissement_id' => $classe->etablissement_id,
                    'inscription_id' => $inscription->id,
                    'sequence_id' => $sequence->id,
                    'moyenne_generale' => $moyennes[$inscription->id],
                    'rang' => $classement[$inscription->id],
                    'details_groupes' => $detailsGroupes,
                    'details_lignes' => $lignes,
                    'moyenne_classe' => $statistiquesClasse['moyenne_classe'],
                    'taux_reussite' => $statistiquesClasse['taux_reussite'],
                    'moyenne_max' => $statistiquesClasse['moyenne_max'],
                    'moyenne_min' => $statistiquesClasse['moyenne_min'],
                    'absences' => $conduite->absences ?? 0,
                    'absences_non_justifiees' => $conduite->absences_non_justifiees ?? 0,
                    'retards' => $conduite->retards ?? 0,
                    'retards_non_justifies' => $conduite->retards_non_justifies ?? 0,
                    'mention_travail' => $conduite->mention_travail ?? null,
                    'mention_conduite' => $conduite->mention_conduite ?? null,
                    'tableau_honneur' => $moyennes[$inscription->id] >= 12,
                    'fichier_pdf' => '',
                ]);

                $pdf = Pdf::loadView('pdf.bulletin', [
                    'etablissement' => $classe->etablissement,
                    'apprenant' => $inscription->apprenant,
                    'classe' => $classe,
                    'anneeAcademique' => $sequence->anneeAcademique,
                    'sequence' => $sequence,
                    'bulletin' => $bulletin,
                    'lignes' => $lignes,
                    'detailsGroupes' => $detailsGroupes,
                    'effectif' => $effectif,
                    'logoDataUri' => $this->logoDataUri($classe->etablissement),
                    'signatureDataUri' => $this->signatureDataUri($classe->etablissement),
                    'enteteDataUri' => $this->enteteDataUri($classe->etablissement),
                ]);

                $chemin = "bulletins/{$classe->etablissement_id}/{$sequence->id}/{$inscription->id}.pdf";
                Storage::disk('local')->put($chemin, $pdf->output());
                $bulletin->update(['fichier_pdf' => $chemin]);

                if ($inscription->apprenant->email) {
                    Mail::to($inscription->apprenant->email)->send(
                        new BulletinPretMail($bulletin->setRelation('inscription', $inscription), $sequence->libelle),
                    );
                }

                $bulletins->push($bulletin);
            }

            return $bulletins;
        });
    }

    /**
     * Bulletin jumele : le bulletin donne + celui de sa sequence "paire"
     * fixe (1<->2, 3<->4...) pour le meme apprenant, sur une seule page en
     * deux tableaux distincts (pas fusionnes). Genere a la volee, jamais
     * persiste (meme logique que PvService) ; si la sequence paire n'a pas
     * encore ete cloturee, son emplacement affiche un message explicite
     * plutot que d'echouer.
     */
    public function genererBulletinJumele(Bulletin $bulletin): string
    {
        $bulletin->loadMissing(['inscription.apprenant', 'inscription.classe.etablissement', 'inscription.anneeAcademique', 'sequence']);

        $numeroPaire = $bulletin->sequence->numero % 2 === 1 ? $bulletin->sequence->numero + 1 : $bulletin->sequence->numero - 1;

        $sequencePaire = Sequence::where('niveau_id', $bulletin->sequence->niveau_id)
            ->where('annee_academique_id', $bulletin->sequence->annee_academique_id)
            ->where('numero', $numeroPaire)
            ->first();

        $bulletinPaire = $sequencePaire
            ? Bulletin::where('inscription_id', $bulletin->inscription_id)->where('sequence_id', $sequencePaire->id)->first()
            : null;

        $classe = $bulletin->inscription->classe;
        $effectif = $classe->inscriptions()->where('statut', '!=', 'annulee')->count();

        $pdf = Pdf::loadView('pdf.bulletin-jumele', [
            'etablissement' => $classe->etablissement,
            'apprenant' => $bulletin->inscription->apprenant,
            'classe' => $classe,
            'anneeAcademique' => $bulletin->inscription->anneeAcademique,
            'effectif' => $effectif,
            'blocs' => [
                ['sequence' => $bulletin->sequence, 'bulletin' => $bulletin],
                ['sequence' => $sequencePaire, 'bulletin' => $bulletinPaire],
            ],
            'logoDataUri' => $this->logoDataUri($classe->etablissement),
            'signatureDataUri' => $this->signatureDataUri($classe->etablissement),
            'enteteDataUri' => $this->enteteDataUri($classe->etablissement),
        ]);

        return $pdf->output();
    }

    /**
     * Bulletin annuel detaille : pour chaque apprenant actif de la classe,
     * une page recapitulant chaque matiere sequence par sequence (a partir
     * des details_lignes persistes a chaque cloture, sans recalcul) plus la
     * moyenne annuelle par matiere et generale. Genere a la volee, jamais
     * persiste — distinct du relevé annuel existant (ReleveService), qui ne
     * montre que la moyenne globale de chaque sequence, pas le detail.
     */
    public function genererBulletinAnnuelDetaille(Classe $classe): string
    {
        $sequences = Sequence::where('niveau_id', $classe->niveau_id)
            ->where('annee_academique_id', $classe->annee_academique_id)
            ->orderBy('numero')
            ->get();

        if ($sequences->isEmpty()) {
            throw ValidationException::withMessages([
                'classe_id' => "Aucune séquence paramétrée pour ce niveau et cette année académique.",
            ]);
        }

        $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

        if ($inscriptions->isEmpty()) {
            throw ValidationException::withMessages([
                'classe_id' => 'Aucun apprenant actif dans cette classe.',
            ]);
        }

        $bulletinsParInscription = Bulletin::whereIn('inscription_id', $inscriptions->pluck('id'))
            ->whereIn('sequence_id', $sequences->pluck('id'))
            ->get()
            ->groupBy('inscription_id');

        $pages = $inscriptions->map(function ($inscription) use ($sequences, $bulletinsParInscription) {
            $bulletinsApprenant = $bulletinsParInscription->get($inscription->id, collect())->keyBy('sequence_id');
            $premierBulletin = $bulletinsApprenant->first();
            $ordreMatieres = $premierBulletin ? collect($premierBulletin->details_lignes)->pluck('matiere')->all() : [];

            $matrice = collect($ordreMatieres)->map(function ($matiereNom) use ($sequences, $bulletinsApprenant) {
                $noteParSequence = fn ($sequence) => optional(
                    collect(optional($bulletinsApprenant->get($sequence->id))->details_lignes)->firstWhere('matiere', $matiereNom)
                );

                $notesParSequence = $sequences->mapWithKeys(function ($sequence) use ($noteParSequence) {
                    $ligne = $noteParSequence($sequence);

                    return [$sequence->id => $ligne->isNotEmpty()
                        ? ($ligne['absent'] ? 'Ab' : number_format((float) $ligne['valeur'], 2, ',', ' '))
                        : '—'];
                });

                $valeursNumeriques = $sequences->map(function ($sequence) use ($noteParSequence) {
                    $ligne = $noteParSequence($sequence);

                    return $ligne->isNotEmpty() && ! $ligne['absent'] ? (float) $ligne['valeur'] : null;
                })->filter(fn ($v) => $v !== null);

                return [
                    'matiere' => $matiereNom,
                    'notes_par_sequence' => $notesParSequence,
                    'moyenne_annuelle' => $valeursNumeriques->isNotEmpty() ? round($valeursNumeriques->avg(), 2) : null,
                ];
            });

            $moyennesSequences = $bulletinsApprenant->pluck('moyenne_generale');

            return [
                'apprenant' => $inscription->apprenant,
                'matrice' => $matrice,
                'moyenne_annuelle' => $moyennesSequences->isNotEmpty() ? round($moyennesSequences->avg(), 2) : null,
                'sequences_manquantes' => $sequences->count() - $bulletinsApprenant->count(),
            ];
        });

        $pdf = Pdf::loadView('pdf.bulletin-annuel-detaille', [
            'etablissement' => $classe->etablissement,
            'classe' => $classe,
            'anneeAcademique' => $classe->anneeAcademique,
            'sequences' => $sequences,
            'pages' => $pages,
            'logoDataUri' => $this->logoDataUri($classe->etablissement),
            'signatureDataUri' => $this->signatureDataUri($classe->etablissement),
            'enteteDataUri' => $this->enteteDataUri($classe->etablissement),
        ]);

        return $pdf->output();
    }

    /**
     * Sous-totaux par groupe de matieres (Groupe I/II/III...), pour un
     * apprenant donne. Une matiere sans groupe est regroupee sous
     * "Non groupé" plutot que d'etre exclue.
     *
     * @param  array<int, array{matiere: \App\Models\Matiere, notes: \Illuminate\Support\Collection}>  $donneesParAffectation
     * @return array<int, array{libelle: string, total_coefficient: float, total_points: float, moyenne: float}>
     */
    private function calculerDetailsGroupes(array $donneesParAffectation, $inscription): array
    {
        return collect($donneesParAffectation)
            ->groupBy(fn ($donnee) => $donnee['matiere']->groupe ?? 'Non groupé')
            ->map(function ($items, $libelle) use ($inscription) {
                $totalCoefficient = 0.0;
                $totalPoints = 0.0;

                foreach ($items as $donnee) {
                    $note = $donnee['notes']->get($inscription->apprenant_id);
                    $valeur = $note->absent ? 0.0 : (float) $note->valeur;
                    $coefficient = (float) $donnee['matiere']->coefficient;
                    $totalCoefficient += $coefficient;
                    $totalPoints += $valeur * $coefficient;
                }

                return [
                    'libelle' => $libelle,
                    'total_coefficient' => $totalCoefficient,
                    'total_points' => round($totalPoints, 2),
                    'moyenne' => $totalCoefficient > 0 ? round($totalPoints / $totalCoefficient, 2) : 0.0,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Appreciation par matiere selon la note obtenue. Bareme reconstruit a
     * partir des couples (note, appreciation) du specimen fourni par
     * l'utilisateur (bulletin_annuel_secondaire) — verifie exact sur les 12
     * exemples disponibles (ex. Anglais 11.75 -> Passable, EPS 13.50 -> Assez
     * Bien), pas invente.
     */
    private function appreciation(float $valeur): string
    {
        return match (true) {
            $valeur < 5 => 'Très Faible',
            $valeur < 8 => 'Faible',
            $valeur < 9.5 => 'Insuffisant',
            $valeur < 10 => 'À peine passable',
            $valeur < 12 => 'Passable',
            $valeur < 14 => 'Assez Bien',
            $valeur < 16 => 'Bien',
            default => 'Excellent',
        };
    }

    /**
     * Classement par compétition (les ex æquo partagent le même rang, le rang
     * suivant tient compte du nombre d'apprenants déjà classés).
     *
     * @param  array<int, float>  $moyennes  inscription_id => moyenne
     * @return array<int, int> inscription_id => rang
     */
    private function calculerRangs(array $moyennes): array
    {
        arsort($moyennes);

        $classement = [];
        $rangCourant = 0;
        $position = 0;
        $derniereMoyenne = null;

        foreach ($moyennes as $inscriptionId => $moyenne) {
            $position++;

            if ($derniereMoyenne === null || $moyenne < $derniereMoyenne) {
                $rangCourant = $position;
                $derniereMoyenne = $moyenne;
            }

            $classement[$inscriptionId] = $rangCourant;
        }

        return $classement;
    }
}
