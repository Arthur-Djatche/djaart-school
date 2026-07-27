<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Note;
use App\Models\Sequence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class BulletinService
{
    public function cloturerSequence(Classe $classe, Sequence $sequence): Collection
    {
        return DB::transaction(function () use ($classe, $sequence) {
            $affectations = AffectationEnseignant::where('classe_id', $classe->id)->with('matiere')->get();

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

                $donneesParAffectation[] = ['matiere' => $affectation->matiere, 'notes' => $notes];
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
            $bulletins = collect();

            foreach ($inscriptions as $inscription) {
                $lignes = collect($donneesParAffectation)->map(function ($donnee) use ($inscription) {
                    $note = $donnee['notes']->get($inscription->apprenant_id);

                    return [
                        'matiere' => $donnee['matiere']->nom,
                        'coefficient' => $donnee['matiere']->coefficient,
                        'valeur' => $note->absent ? 0 : $note->valeur,
                        'absent' => $note->absent,
                    ];
                })->all();

                $bulletin = Bulletin::create([
                    'etablissement_id' => $classe->etablissement_id,
                    'inscription_id' => $inscription->id,
                    'sequence_id' => $sequence->id,
                    'moyenne_generale' => $moyennes[$inscription->id],
                    'rang' => $classement[$inscription->id],
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
                    'effectif' => $effectif,
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
