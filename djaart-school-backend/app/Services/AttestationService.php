<?php

namespace App\Services;

use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Etablissement;
use App\Services\Concerns\EmbedsEtablissementBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttestationService
{
    use EmbedsEtablissementBranding;

    private const TYPE_LABELS = [
        'scolarite' => 'Attestation de Scolarité',
        'frequentation' => 'Attestation de Fréquentation',
        'reussite' => 'Attestation de Réussite',
    ];

    public function __construct(private readonly QrCodeService $qrCodeService)
    {
    }

    public function generer(Apprenant $apprenant, string $type): Attestation
    {
        return DB::transaction(function () use ($apprenant, $type) {
            $inscription = $apprenant->inscriptions()
                ->where('statut', 'validee')
                ->whereHas('anneeAcademique', fn ($query) => $query->where('statut', 'en_cours'))
                ->with(['classe', 'anneeAcademique'])
                ->latest('id')
                ->first();

            if (! $inscription) {
                throw ValidationException::withMessages([
                    'apprenant_id' => "Cet apprenant n'a aucune inscription validée pour l'année académique en cours.",
                ]);
            }

            $etablissement = Etablissement::where('id', $apprenant->etablissement_id)->lockForUpdate()->first();
            $numero = $etablissement->next_attestation_sequence;
            $etablissement->update(['next_attestation_sequence' => $numero + 1]);

            $attestation = Attestation::create([
                'etablissement_id' => $apprenant->etablissement_id,
                'apprenant_id' => $apprenant->id,
                'type' => $type,
                'numero' => $numero,
                'fichier_pdf' => '',
            ]);

            $qrDataUri = $this->qrCodeService->genererDataUri(
                "DJAART-SCHOOL|ATTESTATION|{$etablissement->sigle}-{$numero}|{$apprenant->matricule}",
            );

            $pdf = Pdf::loadView('pdf.attestation', [
                'etablissement' => $etablissement,
                'apprenant' => $apprenant,
                'classe' => $inscription->classe,
                'anneeAcademique' => $inscription->anneeAcademique,
                'attestation' => $attestation,
                'typeLabel' => self::TYPE_LABELS[$type] ?? $type,
                'qrDataUri' => $qrDataUri,
                'logoDataUri' => $this->logoDataUri($etablissement),
                'signatureDataUri' => $this->signatureDataUri($etablissement),
                'enteteDataUri' => $this->enteteDataUri($etablissement),
            ]);

            $chemin = "attestations/{$apprenant->etablissement_id}/{$numero}.pdf";
            Storage::disk('local')->put($chemin, $pdf->output());
            $attestation->update(['fichier_pdf' => $chemin]);

            return $attestation;
        });
    }
}
