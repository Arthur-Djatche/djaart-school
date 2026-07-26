<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InscriptionService
{
    public function __construct(private readonly ApprenantService $apprenantService)
    {
    }

    public function inscrire(array $data): Inscription
    {
        return DB::transaction(function () use ($data) {
            $classe = Classe::findOrFail($data['classe_id']);
            $etablissement = $classe->etablissement;

            $this->assertClasseADeLaPlace($classe);

            $fraisScolarite = FraisScolarite::where('niveau_id', $classe->niveau_id)
                ->where('annee_academique_id', $classe->annee_academique_id)
                ->first();

            if (! $fraisScolarite) {
                throw ValidationException::withMessages([
                    'classe_id' => "Aucune grille de frais de scolarité n'est configurée pour ce niveau et cette année académique.",
                ]);
            }

            $apprenant = $this->apprenantService->findOrCreate($data, $etablissement);

            $typeInscription = $apprenant->inscriptions()->exists() ? 'reinscription' : 'nouvelle';

            return Inscription::create([
                'etablissement_id' => $etablissement->id,
                'apprenant_id' => $apprenant->id,
                'classe_id' => $classe->id,
                'annee_academique_id' => $classe->annee_academique_id,
                'frais_scolarite_id' => $fraisScolarite->id,
                'statut' => 'en_cours',
                'type_inscription' => $typeInscription,
                'date_inscription' => now()->toDateString(),
            ])->load(['apprenant', 'classe', 'anneeAcademique', 'fraisScolarite.tranches']);
        });
    }

    public function annuler(Inscription $inscription): Inscription
    {
        $inscription->update(['statut' => 'annulee']);

        return $inscription;
    }

    private function assertClasseADeLaPlace(Classe $classe): void
    {
        $inscritsActifs = $classe->inscriptions()->where('statut', '!=', 'annulee')->count();

        if ($inscritsActifs >= $classe->effectif_max) {
            throw ValidationException::withMessages([
                'classe_id' => 'Cette classe a atteint son effectif maximum.',
            ]);
        }
    }
}
