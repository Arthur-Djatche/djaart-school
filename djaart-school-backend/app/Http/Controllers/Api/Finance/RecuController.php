<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Recu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecuController extends Controller
{
    /**
     * Sans ?inline=1 : telechargement classique (Content-Disposition
     * attachment), utilise par les liens "Telecharger le recu" existants.
     * Avec ?inline=1 : affichage direct dans le navigateur, utilise par la
     * page d'impression automatique declenchee juste apres un encaissement
     * (CaissePage) — evite de forcer un telechargement avant de pouvoir
     * imprimer.
     */
    public function telecharger(Request $request, Recu $recu)
    {
        $this->authorize('view', $recu);

        if ($request->boolean('inline')) {
            return response(Storage::disk('local')->get($recu->fichier_pdf), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"recu-{$recu->numero_recu}.pdf\"",
            ]);
        }

        return Storage::disk('local')->download($recu->fichier_pdf, "recu-{$recu->numero_recu}.pdf");
    }
}
