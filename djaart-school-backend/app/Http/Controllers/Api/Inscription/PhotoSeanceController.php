<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Controller;
use App\Models\Classe;
use App\Services\PhotoSeanceService;
use Illuminate\Http\Request;

class PhotoSeanceController extends Controller
{
    public function __construct(private readonly PhotoSeanceService $photoSeanceService)
    {
    }

    public function liste(Request $request, Classe $classe)
    {
        $this->autoriserGererDocuments($request, $classe);

        $pdf = $this->photoSeanceService->genererListePdf($classe);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="liste-photos.pdf"',
        ]);
    }

    private function autoriserGererDocuments(Request $request, Classe $classe): void
    {
        $user = $request->user();

        $autorise = ($user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire']) || $user->can('acces.seance_photo'))
            && ($user->hasRole('super_admin') || $user->etablissement_id === $classe->etablissement_id);

        abort_unless($autorise, 403);
    }
}
