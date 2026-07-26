<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Etablissement;
use App\Models\Niveau;
use App\Services\FraisScolariteService;
use Illuminate\Database\Seeder;

class FraisScolariteSeeder extends Seeder
{
    /** @var array<string, array<int, array{niveau: string, montant: float, mode: string, nombre_tranches?: int}>> */
    private const BAREMES = [
        'Lycée Démo DJAART' => [
            ['niveau' => '6ème', 'montant' => 150000, 'mode' => 'tranches', 'nombre_tranches' => 3],
            ['niveau' => '5ème', 'montant' => 140000, 'mode' => 'comptant'],
        ],
        'École Primaire Démo DJAART' => [
            ['niveau' => 'CP1', 'montant' => 100000, 'mode' => 'comptant'],
            ['niveau' => 'CP2', 'montant' => 120000, 'mode' => 'tranches', 'nombre_tranches' => 2],
        ],
        'Université Démo DJAART' => [
            ['niveau' => 'Licence 1', 'montant' => 300000, 'mode' => 'tranches', 'nombre_tranches' => 2],
            ['niveau' => 'Licence 2', 'montant' => 300000, 'mode' => 'comptant'],
        ],
        'Centre de Formation Démo DJAART' => [
            ['niveau' => 'CQP Électricien', 'montant' => 80000, 'mode' => 'comptant'],
            ['niveau' => 'DQP Électricien', 'montant' => 90000, 'mode' => 'tranches', 'nombre_tranches' => 2],
        ],
    ];

    public function run(FraisScolariteService $service): void
    {
        foreach (self::BAREMES as $etablissementNom => $baremes) {
            $etablissement = Etablissement::where('nom', $etablissementNom)->firstOrFail();
            $annee = AnneeAcademique::where('etablissement_id', $etablissement->id)
                ->where('libelle', '2025-2026')
                ->firstOrFail();

            foreach ($baremes as $bareme) {
                $niveau = Niveau::where('etablissement_id', $etablissement->id)
                    ->where('libelle', $bareme['niveau'])
                    ->firstOrFail();

                $data = [
                    'etablissement_id' => $etablissement->id,
                    'niveau_id' => $niveau->id,
                    'annee_academique_id' => $annee->id,
                    'montant_total' => $bareme['montant'],
                    'mode' => $bareme['mode'],
                ];

                if ($bareme['mode'] === 'tranches') {
                    $nombreTranches = $bareme['nombre_tranches'];
                    $montantParTranche = round($bareme['montant'] / $nombreTranches, 2);
                    $tranches = [];

                    for ($i = 1; $i <= $nombreTranches; $i++) {
                        $tranches[] = [
                            'numero' => $i,
                            'montant' => $i === $nombreTranches
                                ? $bareme['montant'] - $montantParTranche * ($nombreTranches - 1)
                                : $montantParTranche,
                            'date_echeance' => $annee->date_debut->copy()->addMonths($i - 1)->toDateString(),
                        ];
                    }

                    $data['tranches'] = $tranches;
                }

                $service->createWithTranches($data);
            }
        }
    }
}
