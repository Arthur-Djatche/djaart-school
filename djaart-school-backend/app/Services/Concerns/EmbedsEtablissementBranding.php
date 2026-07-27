<?php

namespace App\Services\Concerns;

use App\Models\Etablissement;
use Illuminate\Support\Facades\Storage;

/**
 * Convertit le logo/la signature d'un etablissement en data URI base64,
 * seul format que dompdf sait afficher de maniere fiable (il ne resout pas
 * les URLs relatives de l'application) — meme technique que la photo
 * d'apprenant sur la carte scolaire (Phase 8).
 */
trait EmbedsEtablissementBranding
{
    private function logoDataUri(Etablissement $etablissement): ?string
    {
        return $this->fichierVersDataUri($etablissement->logo);
    }

    private function signatureDataUri(Etablissement $etablissement): ?string
    {
        return $this->fichierVersDataUri($etablissement->signature);
    }

    private function enteteDataUri(Etablissement $etablissement): ?string
    {
        return $this->fichierVersDataUri($etablissement->entete);
    }

    private function fichierVersDataUri(?string $chemin): ?string
    {
        if (! $chemin || ! Storage::disk('public')->exists($chemin)) {
            return null;
        }

        $contenu = Storage::disk('public')->get($chemin);
        $mime = Storage::disk('public')->mimeType($chemin);

        return "data:{$mime};base64,".base64_encode($contenu);
    }
}
