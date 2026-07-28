<?php

namespace App\Services;

use App\Models\Classe;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Liste de classe pour la seance photo : feuille de suivi imprimee que le
 * photographe garde sous les yeux pendant la prise de vue, dans le meme
 * ordre (nom, prenom) que celui utilise par l'import en masse des photos
 * (PhotoMasseController) — genere a la volee, jamais persiste, comme les
 * rapports (RapportService).
 */
class PhotoSeanceService
{
    public function genererListePdf(Classe $classe): string
    {
        $roster = $classe->inscriptions()
            ->where('statut', '!=', 'annulee')
            ->with('apprenant')
            ->get()
            ->sortBy([['apprenant.nom', 'asc'], ['apprenant.prenom', 'asc']])
            ->values();

        $lignes = $roster->map(fn ($inscription, $index) => [
            'ordre' => $index + 1,
            'nom' => $inscription->apprenant->nom,
            'prenom' => $inscription->apprenant->prenom,
            'date_naissance' => $inscription->apprenant->date_naissance,
            'matricule' => $inscription->apprenant->matricule,
        ]);

        return Pdf::loadView('pdf.liste-photos', [
            'etablissement' => $classe->etablissement,
            'classe' => $classe,
            'lignes' => $lignes,
        ])->output();
    }
}
