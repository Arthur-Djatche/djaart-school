<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Recu;
use Illuminate\Support\Facades\Storage;

class RecuController extends Controller
{
    public function telecharger(Recu $recu)
    {
        $this->authorize('view', $recu);

        return Storage::disk('local')->download($recu->fichier_pdf, "recu-{$recu->numero_recu}.pdf");
    }
}
