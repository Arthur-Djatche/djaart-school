<?php

namespace App\Services;

use App\Mail\PaiementRecuMail;
use App\Models\Etablissement;
use App\Models\Paiement;
use App\Models\Recu;
use App\Services\Concerns\EmbedsEtablissementBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class RecuService
{
    use EmbedsEtablissementBranding;

    private const MODE_LABELS = [
        'especes' => 'Espèces',
        'mobile_money' => 'Mobile Money',
        'virement' => 'Virement',
        'cheque' => 'Chèque',
    ];

    public function __construct(private readonly EcheancierService $echeancierService)
    {
    }

    public function genererPour(Paiement $paiement): Recu
    {
        return DB::transaction(function () use ($paiement) {
            $etablissement = Etablissement::where('id', $paiement->etablissement_id)->lockForUpdate()->first();
            $numero = $etablissement->next_recu_sequence;
            $etablissement->update(['next_recu_sequence' => $numero + 1]);

            $paiement->loadMissing([
                'inscription.apprenant',
                'inscription.classe',
                'inscription.anneeAcademique',
                'inscription.fraisScolarite',
            ]);

            $recu = Recu::create([
                'etablissement_id' => $paiement->etablissement_id,
                'paiement_id' => $paiement->id,
                'numero_recu' => $numero,
                'fichier_pdf' => '',
            ]);

            $montantTotalPension = (float) $paiement->inscription->fraisScolarite->montant_total;
            $totalPayeACejour = $this->echeancierService->totalPaye($paiement->inscription);
            $soldeRestant = $this->echeancierService->soldeTotalPension($paiement->inscription);

            $pdf = Pdf::loadView('pdf.recu', [
                'recu' => $recu,
                'etablissement' => $etablissement,
                'apprenant' => $paiement->inscription->apprenant,
                'classe' => $paiement->inscription->classe,
                'anneeAcademique' => $paiement->inscription->anneeAcademique,
                'paiement' => $paiement,
                'montantTotalPension' => $montantTotalPension,
                'totalPayeACejour' => $totalPayeACejour,
                'soldeRestant' => $soldeRestant,
                'modeLabel' => self::MODE_LABELS[$paiement->mode_paiement] ?? $paiement->mode_paiement,
                'logoDataUri' => $this->logoDataUri($etablissement),
                'enteteDataUri' => $this->enteteDataUri($etablissement),
            ]);

            $chemin = "recus/{$paiement->etablissement_id}/{$numero}.pdf";
            Storage::disk('local')->put($chemin, $pdf->output());

            $recu->update(['fichier_pdf' => $chemin]);

            if ($paiement->inscription->apprenant->email) {
                Mail::to($paiement->inscription->apprenant->email)->send(new PaiementRecuMail($recu));
            }

            return $recu;
        });
    }
}
