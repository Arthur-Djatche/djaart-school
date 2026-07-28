<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Note;
use App\Models\Semestre;
use App\Models\Sequence;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NoteService
{
    public function soumettre(AffectationEnseignant $affectation, array $data): Collection
    {
        return DB::transaction(function () use ($affectation, $data) {
            $sequenceId = $data['sequence_id'] ?? null;
            $semestreId = $data['semestre_id'] ?? null;
            $typeEvaluation = $data['type_evaluation'];

            $dejaVerrouille = Note::where('affectation_id', $affectation->id)
                ->where('sequence_id', $sequenceId)
                ->where('semestre_id', $semestreId)
                ->where('type_evaluation', $typeEvaluation)
                ->whereNotNull('soumis_le')
                ->exists();

            if ($dejaVerrouille) {
                throw ValidationException::withMessages([
                    'notes' => "Cette saisie est déjà verrouillée. Contactez un administrateur pour la déverrouiller.",
                ]);
            }

            $this->verifierPeriodePrecedenteComplete($affectation, $sequenceId, $semestreId);

            if ($typeEvaluation === 'rattrapage') {
                $this->verifierRattrapageApplicable($affectation, $semestreId, $data['notes']);
            }

            $maintenant = now();

            foreach ($data['notes'] as $ligne) {
                $absent = $ligne['absent'] ?? false;

                Note::updateOrCreate(
                    [
                        'affectation_id' => $affectation->id,
                        'apprenant_id' => $ligne['apprenant_id'],
                        'sequence_id' => $sequenceId,
                        'semestre_id' => $semestreId,
                        'type_evaluation' => $typeEvaluation,
                    ],
                    [
                        'etablissement_id' => $affectation->etablissement_id,
                        'valeur' => $absent ? null : ($ligne['valeur'] ?? null),
                        'absent' => $absent,
                        'soumis_le' => $maintenant,
                    ],
                );
            }

            return Note::where('affectation_id', $affectation->id)
                ->where('sequence_id', $sequenceId)
                ->where('semestre_id', $semestreId)
                ->where('type_evaluation', $typeEvaluation)
                ->with('apprenant')
                ->get();
        });
    }

    public function deverrouiller(AffectationEnseignant $affectation, array $criteres): void
    {
        Note::where('affectation_id', $affectation->id)
            ->where('sequence_id', $criteres['sequence_id'] ?? null)
            ->where('semestre_id', $criteres['semestre_id'] ?? null)
            ->where('type_evaluation', $criteres['type_evaluation'])
            ->update(['soumis_le' => null]);
    }

    /**
     * Une periode (sequence ou semestre) de numero > 1 ne peut recevoir de
     * notes que si la periode precedente (meme niveau+annee) est complete
     * *pour la classe de l'affectation soumise* : chaque matiere affectee a
     * cette classe doit avoir, pour chaque apprenant actif, une note
     * verrouillee du/des type(s) attendu(s) sur cette periode precedente.
     * Meme granularite et meme idiome de message que
     * BulletinService::cloturerSequence.
     */
    private function verifierPeriodePrecedenteComplete(AffectationEnseignant $affectation, ?int $sequenceId, ?int $semestreId): void
    {
        if ($sequenceId) {
            $periode = Sequence::find($sequenceId);
            $modele = Sequence::class;
            $colonnePeriode = 'sequence_id';
            $typesRequis = ['sequence'];
        } else {
            $periode = Semestre::find($semestreId);
            $modele = Semestre::class;
            $colonnePeriode = 'semestre_id';
            $typesRequis = ['cc', 'session_normale'];
        }

        if (! $periode || $periode->numero <= 1) {
            return;
        }

        $periodePrecedente = $modele::where('niveau_id', $periode->niveau_id)
            ->where('annee_academique_id', $periode->annee_academique_id)
            ->where('numero', $periode->numero - 1)
            ->first();

        if (! $periodePrecedente) {
            return;
        }

        $affectationsClasse = AffectationEnseignant::where('classe_id', $affectation->classe_id)
            ->where('annee_academique_id', $affectation->annee_academique_id)
            ->with('matiere')
            ->get()
            // En LMD, un EC n'appartient qu'a un seul semestre : ne verifier
            // que les affectations dont la matiere appartient bien a la
            // periode precedente (sinon un EC du semestre 2 serait a tort
            // considere "incomplet" au semestre 1, ou l'inverse).
            ->when($colonnePeriode === 'semestre_id', fn ($affectations) => $affectations->filter(
                fn ($affectationClasse) => $affectationClasse->matiere->semestre_id === $periodePrecedente->id,
            ));

        $apprenantIds = $affectation->classe->inscriptions()->where('statut', '!=', 'annulee')->pluck('apprenant_id');

        $matieresIncompletes = [];

        foreach ($affectationsClasse as $affectationClasse) {
            foreach ($typesRequis as $type) {
                $nombreVerrouille = Note::where('affectation_id', $affectationClasse->id)
                    ->where($colonnePeriode, $periodePrecedente->id)
                    ->where('type_evaluation', $type)
                    ->whereIn('apprenant_id', $apprenantIds)
                    ->whereNotNull('soumis_le')
                    ->count();

                if ($nombreVerrouille < $apprenantIds->count()) {
                    $matieresIncompletes[] = $affectationClasse->matiere->nom;
                }
            }
        }

        if (! empty($matieresIncompletes)) {
            throw ValidationException::withMessages([
                'notes' => "La période précédente ({$periodePrecedente->libelle}) n'est pas encore complète pour : ".implode(', ', array_unique($matieresIncompletes)),
            ]);
        }
    }

    /**
     * Le rattrapage ne remplace la Session Normale (dans le calcul CC/SN deja
     * pondere par matiere) que pour un EC non valide (moyenne CC/SN < 10) —
     * saisissable uniquement si CC et SN sont deja verrouilles.
     */
    private function verifierRattrapageApplicable(AffectationEnseignant $affectation, int $semestreId, array $lignes): void
    {
        $matiere = $affectation->matiere;

        foreach ($lignes as $ligne) {
            if (($ligne['absent'] ?? false) || ($ligne['valeur'] ?? null) === null) {
                continue;
            }

            $cc = Note::where('affectation_id', $affectation->id)
                ->where('apprenant_id', $ligne['apprenant_id'])
                ->where('semestre_id', $semestreId)
                ->where('type_evaluation', 'cc')
                ->whereNotNull('soumis_le')
                ->first();

            $sn = Note::where('affectation_id', $affectation->id)
                ->where('apprenant_id', $ligne['apprenant_id'])
                ->where('semestre_id', $semestreId)
                ->where('type_evaluation', 'session_normale')
                ->whereNotNull('soumis_le')
                ->first();

            if (! $cc || ! $sn) {
                throw ValidationException::withMessages([
                    'notes' => 'CC et Session Normale doivent être saisis et verrouillés avant le rattrapage.',
                ]);
            }

            $ccValeur = $cc->absent ? 0 : (float) $cc->valeur;
            $snValeur = $sn->absent ? 0 : (float) $sn->valeur;
            $moyenne = ($ccValeur * $matiere->ponderation_cc + $snValeur * $matiere->ponderation_session_normale) / 100;

            if ($moyenne >= 10) {
                throw ValidationException::withMessages([
                    'notes' => "L'EC est déjà validé, le rattrapage n'est pas applicable pour cet apprenant.",
                ]);
            }
        }
    }
}
