<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Note;
use App\Models\Semestre;
use App\Models\Sequence;
use App\Services\Concerns\EmbedsEtablissementBranding;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Proces-verbal des notes par affectation et par periode : document de
 * travail imprimable par l'enseignant, genere a la volee et jamais persiste
 * ni numerote (meme logique que RapportService — un instantane des donnees
 * deja saisies, pas un document officiel comme le bulletin/releve).
 */
class PvService
{
    use EmbedsEtablissementBranding;

    private const TYPES_LMD = ['cc' => 'Contrôle Continu', 'session_normale' => 'Session Normale', 'rattrapage' => 'Rattrapage'];

    public function genererPourAffectation(AffectationEnseignant $affectation, Sequence|Semestre $periode): string
    {
        $affectation->loadMissing(['classe.niveau', 'matiere', 'enseignant']);

        $estLmd = $periode instanceof Semestre;
        $colonnePeriode = $estLmd ? 'semestre_id' : 'sequence_id';
        $types = $estLmd ? self::TYPES_LMD : ['sequence' => 'Séquence'];

        $apprenants = $affectation->classe->inscriptions()
            ->where('statut', '!=', 'annulee')
            ->with('apprenant')
            ->get()
            ->pluck('apprenant')
            ->sortBy('nom');

        $notesParType = [];
        foreach (array_keys($types) as $type) {
            $notesParType[$type] = Note::where('affectation_id', $affectation->id)
                ->where($colonnePeriode, $periode->id)
                ->where('type_evaluation', $type)
                ->get()
                ->keyBy('apprenant_id');
        }

        $lignes = $apprenants->map(function ($apprenant) use ($notesParType, $types) {
            $valeurs = [];
            foreach (array_keys($types) as $type) {
                $note = $notesParType[$type]->get($apprenant->id);
                $valeurs[$type] = $note
                    ? ($note->absent ? 'Absent' : ($note->valeur !== null ? number_format($note->valeur, 2, ',', ' ') : '—'))
                    : '—';
            }

            return ['apprenant' => $apprenant, 'valeurs' => $valeurs];
        });

        $pdf = Pdf::loadView('pdf.pv', [
            'etablissement' => $affectation->classe->etablissement,
            'affectation' => $affectation,
            'periode' => $periode,
            'types' => $types,
            'lignes' => $lignes,
            'logoDataUri' => $this->logoDataUri($affectation->classe->etablissement),
            'enteteDataUri' => $this->enteteDataUri($affectation->classe->etablissement),
        ]);

        return $pdf->output();
    }
}
