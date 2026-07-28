<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Controller;
use App\Models\AffectationEnseignant;
use App\Models\Semestre;
use App\Models\Sequence;
use App\Services\PvService;

class PvController extends Controller
{
    public function __construct(private readonly PvService $pvService)
    {
    }

    public function pourSequence(AffectationEnseignant $affectation, Sequence $sequence)
    {
        $this->authorize('voirNotes', $affectation);

        $pdf = $this->pvService->genererPourAffectation($affectation, $sequence);

        return $this->streamPdf($pdf, "pv-{$affectation->id}-{$sequence->id}.pdf");
    }

    public function pourSemestre(AffectationEnseignant $affectation, Semestre $semestre)
    {
        $this->authorize('voirNotes', $affectation);

        $pdf = $this->pvService->genererPourAffectation($affectation, $semestre);

        return $this->streamPdf($pdf, "pv-{$affectation->id}-{$semestre->id}.pdf");
    }

    private function streamPdf(string $contenu, string $nomFichier)
    {
        return response($contenu, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$nomFichier}\"",
        ]);
    }
}
