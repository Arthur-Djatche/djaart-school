<?php

namespace App\Services;

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

                $bulletins->push($bulletin);
            }

            return $bulletins;
        });
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
