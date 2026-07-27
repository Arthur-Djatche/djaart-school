<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Etablissement;
use App\Services\RapportService;
use Illuminate\Http\Request;

class RapportController extends Controller
{
    public function __construct(private readonly RapportService $rapportService)
    {
    }

    public function impayes(Request $request)
    {
        $etablissement = $this->etablissementDuRequete($request);
        $pdf = $this->rapportService->genererImpayesPdf($etablissement);

        return $this->streamPdf($pdf, 'rapport-impayes.pdf');
    }

    public function statistiquesReussite(Request $request)
    {
        $etablissement = $this->etablissementDuRequete($request);
        $pdf = $this->rapportService->genererStatistiquesReussitePdf($etablissement);

        return $this->streamPdf($pdf, 'rapport-statistiques-reussite.pdf');
    }

    private function etablissementDuRequete(Request $request): ?Etablissement
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return null;
        }

        return Etablissement::find($user->etablissement_id);
    }

    private function streamPdf(string $contenu, string $nomFichier)
    {
        return response($contenu, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$nomFichier}\"",
        ]);
    }
}
