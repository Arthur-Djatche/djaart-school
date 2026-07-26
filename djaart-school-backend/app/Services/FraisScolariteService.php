<?php

namespace App\Services;

use App\Models\AnneeAcademique;
use App\Models\FraisScolarite;
use Illuminate\Support\Facades\DB;

class FraisScolariteService
{
    public function createWithTranches(array $data): FraisScolarite
    {
        return DB::transaction(function () use ($data) {
            $tranches = $this->resolveTranches($data);

            $fraisScolarite = FraisScolarite::create([
                'etablissement_id' => $data['etablissement_id'],
                'niveau_id' => $data['niveau_id'],
                'annee_academique_id' => $data['annee_academique_id'],
                'montant_total' => $data['montant_total'],
                'nombre_tranches' => count($tranches),
            ]);

            $fraisScolarite->tranches()->createMany($tranches);

            return $fraisScolarite->load('tranches', 'niveau', 'anneeAcademique');
        });
    }

    public function replaceTranches(FraisScolarite $fraisScolarite, array $data): FraisScolarite
    {
        return DB::transaction(function () use ($fraisScolarite, $data) {
            $tranches = $this->resolveTranches($data + [
                'etablissement_id' => $fraisScolarite->etablissement_id,
                'annee_academique_id' => $fraisScolarite->annee_academique_id,
            ]);

            $fraisScolarite->update([
                'montant_total' => $data['montant_total'],
                'nombre_tranches' => count($tranches),
            ]);

            $fraisScolarite->tranches()->delete();
            $fraisScolarite->tranches()->createMany($tranches);

            return $fraisScolarite->load('tranches', 'niveau', 'anneeAcademique');
        });
    }

    private function resolveTranches(array $data): array
    {
        if ($data['mode'] === 'comptant') {
            $anneeAcademique = AnneeAcademique::findOrFail($data['annee_academique_id']);

            return [[
                'etablissement_id' => $data['etablissement_id'],
                'numero' => 1,
                'montant' => $data['montant_total'],
                'date_echeance' => $anneeAcademique->date_debut,
            ]];
        }

        return collect($data['tranches'])
            ->map(fn (array $tranche) => [
                'etablissement_id' => $data['etablissement_id'],
                'numero' => $tranche['numero'],
                'montant' => $tranche['montant'],
                'date_echeance' => $tranche['date_echeance'],
            ])
            ->all();
    }
}
