<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Note;
use App\Models\ReleveDeNotes;
use App\Models\Semestre;
use App\Models\Sequence;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReleveService
{
    private const SEUIL_ADMISSION = 10.0;

    public function genererAnnuelClassique(Classe $classe): Collection
    {
        return DB::transaction(function () use ($classe) {
            $sequences = Sequence::where('niveau_id', $classe->niveau_id)
                ->where('annee_academique_id', $classe->annee_academique_id)
                ->orderBy('numero')
                ->get();

            if ($sequences->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => "Aucune séquence n'est configurée pour ce niveau et cette année académique.",
                ]);
            }

            $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

            if ($inscriptions->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Aucun apprenant actif dans cette classe.',
                ]);
            }

            if (ReleveDeNotes::whereIn('inscription_id', $inscriptions->pluck('id'))->whereNull('semestre_id')->exists()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Des relevés annuels existent déjà pour cette classe.',
                ]);
            }

            $manquants = [];
            $bulletinsParInscription = [];

            foreach ($inscriptions as $inscription) {
                $bulletins = Bulletin::where('inscription_id', $inscription->id)
                    ->whereIn('sequence_id', $sequences->pluck('id'))
                    ->get()
                    ->keyBy('sequence_id');

                if ($bulletins->count() < $sequences->count()) {
                    $manquants[] = "{$inscription->apprenant->prenom} {$inscription->apprenant->nom}";

                    continue;
                }

                $bulletinsParInscription[$inscription->id] = $bulletins;
            }

            if ($manquants !== []) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Toutes les séquences ne sont pas encore clôturées pour : '.implode(', ', $manquants).'.',
                ]);
            }

            $releves = collect();

            foreach ($inscriptions as $inscription) {
                $bulletins = $bulletinsParInscription[$inscription->id];
                $moyenneGenerale = round($bulletins->avg('moyenne_generale'), 2);
                $mention = $this->mentionClassique($moyenneGenerale);

                $releve = ReleveDeNotes::create([
                    'etablissement_id' => $classe->etablissement_id,
                    'inscription_id' => $inscription->id,
                    'semestre_id' => null,
                    'moyenne_generale' => $moyenneGenerale,
                    'mention' => $mention,
                    'fichier_pdf' => '',
                ]);

                $lignes = $sequences->map(fn ($sequence) => [
                    'sequence' => $sequence->libelle,
                    'moyenne' => $bulletins->get($sequence->id)->moyenne_generale,
                ])->all();

                $pdf = Pdf::loadView('pdf.releve', [
                    'etablissement' => $classe->etablissement,
                    'apprenant' => $inscription->apprenant,
                    'classe' => $classe,
                    'anneeAcademique' => $inscription->anneeAcademique,
                    'semestre' => null,
                    'releve' => $releve,
                    'lignes' => $lignes,
                ]);

                $chemin = "releves/{$classe->etablissement_id}/annuel/{$inscription->id}.pdf";
                Storage::disk('local')->put($chemin, $pdf->output());
                $releve->update(['fichier_pdf' => $chemin]);

                $releves->push($releve);
            }

            return $releves;
        });
    }

    public function genererSemestrielLmd(Classe $classe, Semestre $semestre): Collection
    {
        return DB::transaction(function () use ($classe, $semestre) {
            $affectations = AffectationEnseignant::where('classe_id', $classe->id)->with('matiere')->get();

            if ($affectations->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => "Aucune UE n'est affectée à cette classe.",
                ]);
            }

            $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

            if ($inscriptions->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Aucun apprenant actif dans cette classe.',
                ]);
            }

            if (ReleveDeNotes::whereIn('inscription_id', $inscriptions->pluck('id'))->where('semestre_id', $semestre->id)->exists()) {
                throw ValidationException::withMessages([
                    'semestre_id' => 'Des relevés existent déjà pour cette classe et ce semestre.',
                ]);
            }

            $manquants = [];
            $donneesParAffectation = [];

            foreach ($affectations as $affectation) {
                $notesCc = Note::where('affectation_id', $affectation->id)
                    ->where('semestre_id', $semestre->id)
                    ->where('type_evaluation', 'cc')
                    ->get()->keyBy('apprenant_id');
                $notesSn = Note::where('affectation_id', $affectation->id)
                    ->where('semestre_id', $semestre->id)
                    ->where('type_evaluation', 'session_normale')
                    ->get()->keyBy('apprenant_id');

                $ccVerrouille = $notesCc->isNotEmpty() && $notesCc->first()->soumis_le !== null;
                $snVerrouille = $notesSn->isNotEmpty() && $notesSn->first()->soumis_le !== null;
                $couvertureComplete = $inscriptions->every(
                    fn ($inscription) => $notesCc->has($inscription->apprenant_id) && $notesSn->has($inscription->apprenant_id),
                );

                if (! $ccVerrouille || ! $snVerrouille || ! $couvertureComplete) {
                    $manquants[] = $affectation->matiere->nom;

                    continue;
                }

                $donneesParAffectation[] = ['matiere' => $affectation->matiere, 'cc' => $notesCc, 'sn' => $notesSn];
            }

            if ($manquants !== []) {
                throw ValidationException::withMessages([
                    'semestre_id' => 'Notes CC/Session Normale manquantes ou non verrouillées pour : '.implode(', ', $manquants).'.',
                ]);
            }

            $releves = collect();

            foreach ($inscriptions as $inscription) {
                $sommeMoyennesPonderees = 0.0;
                $sommeCredits = 0.0;
                $lignes = [];

                foreach ($donneesParAffectation as $donnee) {
                    $matiere = $donnee['matiere'];
                    $cc = (float) $donnee['cc']->get($inscription->apprenant_id)->valeur;
                    $sn = (float) $donnee['sn']->get($inscription->apprenant_id)->valeur;
                    $moyenneUe = round(($cc * $matiere->ponderation_cc + $sn * $matiere->ponderation_session_normale) / 100, 2);
                    $credits = (float) ($matiere->credits_ects ?? 1);
                    $validee = $moyenneUe >= self::SEUIL_ADMISSION;

                    $sommeMoyennesPonderees += $moyenneUe * $credits;
                    $sommeCredits += $credits;

                    $lignes[] = [
                        'matiere' => $matiere->nom,
                        'credits_ects' => $matiere->credits_ects,
                        'moyenne_ue' => $moyenneUe,
                        'validee' => $validee,
                    ];
                }

                $moyenneGenerale = $sommeCredits > 0 ? round($sommeMoyennesPonderees / $sommeCredits, 2) : 0.0;
                $mention = $moyenneGenerale >= self::SEUIL_ADMISSION ? 'Admis' : 'Ajourné';

                $releve = ReleveDeNotes::create([
                    'etablissement_id' => $classe->etablissement_id,
                    'inscription_id' => $inscription->id,
                    'semestre_id' => $semestre->id,
                    'moyenne_generale' => $moyenneGenerale,
                    'mention' => $mention,
                    'fichier_pdf' => '',
                ]);

                $pdf = Pdf::loadView('pdf.releve', [
                    'etablissement' => $classe->etablissement,
                    'apprenant' => $inscription->apprenant,
                    'classe' => $classe,
                    'anneeAcademique' => $inscription->anneeAcademique,
                    'semestre' => $semestre,
                    'releve' => $releve,
                    'lignes' => $lignes,
                ]);

                $chemin = "releves/{$classe->etablissement_id}/{$semestre->id}/{$inscription->id}.pdf";
                Storage::disk('local')->put($chemin, $pdf->output());
                $releve->update(['fichier_pdf' => $chemin]);

                $releves->push($releve);
            }

            return $releves;
        });
    }

    private function mentionClassique(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Excellent',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= self::SEUIL_ADMISSION => 'Passable',
            default => 'Insuffisant',
        };
    }
}
