<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Note;
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
}
