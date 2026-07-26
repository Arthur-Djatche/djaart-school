<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Etablissement;
use App\Services\InscriptionService;
use Illuminate\Database\Seeder;

class InscriptionSeeder extends Seeder
{
    private const INSCRIPTIONS = [
        [
            'etablissement' => 'Lycée Démo DJAART',
            'classe' => '6ème A',
            'apprenant' => [
                'nom' => 'Traoré',
                'prenom' => 'Aïcha',
                'date_naissance' => '2013-05-14',
                'sexe' => 'F',
            ],
        ],
        [
            'etablissement' => 'Lycée Démo DJAART',
            'classe' => '5ème A',
            'apprenant' => [
                'nom' => 'Koné',
                'prenom' => 'Moussa',
                'date_naissance' => '2012-08-22',
                'sexe' => 'M',
            ],
        ],
        [
            'etablissement' => 'École Primaire Démo DJAART',
            'classe' => 'CP1 A',
            'apprenant' => [
                'nom' => 'Diallo',
                'prenom' => 'Fatoumata',
                'date_naissance' => '2019-02-10',
                'sexe' => 'F',
            ],
        ],
        [
            'etablissement' => 'Université Démo DJAART',
            'classe' => 'Licence 1 A',
            'apprenant' => [
                'nom' => 'Ouattara',
                'prenom' => 'Ibrahim',
                'date_naissance' => '2005-11-30',
                'sexe' => 'M',
            ],
        ],
    ];

    public function run(InscriptionService $service): void
    {
        foreach (self::INSCRIPTIONS as $data) {
            $etablissement = Etablissement::where('nom', $data['etablissement'])->firstOrFail();
            $classe = Classe::where('etablissement_id', $etablissement->id)
                ->where('libelle', $data['classe'])
                ->firstOrFail();

            $service->inscrire([
                'classe_id' => $classe->id,
                'apprenant' => $data['apprenant'],
            ]);
        }
    }
}
