<?php

namespace App\Services;

use App\Models\Apprenant;
use App\Models\CarteScolaire;
use App\Models\Etablissement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CarteScolaireService
{
    public function __construct(private readonly QrCodeService $qrCodeService)
    {
    }

    public function generer(Apprenant $apprenant): CarteScolaire
    {
        return DB::transaction(function () use ($apprenant) {
            if (! $apprenant->photo) {
                throw ValidationException::withMessages([
                    'apprenant_id' => "Cet apprenant n'a pas de photo : téléversez-en une avant de générer la carte scolaire.",
                ]);
            }

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
            $numero = $etablissement->next_carte_sequence;
            $etablissement->update(['next_carte_sequence' => $numero + 1]);

            $numeroDuplicata = CarteScolaire::where('apprenant_id', $apprenant->id)->count();

            $carte = CarteScolaire::create([
                'etablissement_id' => $apprenant->etablissement_id,
                'apprenant_id' => $apprenant->id,
                'numero' => $numero,
                'numero_duplicata' => $numeroDuplicata,
                'date_emission' => now()->toDateString(),
                'date_expiration' => $inscription->anneeAcademique->date_fin,
                'fichier_pdf' => '',
            ]);

            $qrDataUri = $this->qrCodeService->genererDataUri(
                "DJAART-SCHOOL|CARTE|{$etablissement->sigle}-{$numero}|{$apprenant->matricule}",
            );

            $pdf = Pdf::loadView('pdf.carte-scolaire', [
                'etablissement' => $etablissement,
                'apprenant' => $apprenant,
                'classe' => $inscription->classe,
                'anneeAcademique' => $inscription->anneeAcademique,
                'carte' => $carte,
                'qrDataUri' => $qrDataUri,
                'photoDataUri' => $this->photoDataUri($apprenant->photo),
            ]);

            $chemin = "cartes/{$apprenant->etablissement_id}/{$numero}.pdf";
            Storage::disk('local')->put($chemin, $pdf->output());
            $carte->update(['fichier_pdf' => $chemin]);

            return $carte;
        });
    }

    private function photoDataUri(string $path): string
    {
        $contenu = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path);

        return "data:{$mime};base64,".base64_encode($contenu);
    }
}
