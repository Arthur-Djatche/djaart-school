<?php

namespace Database\Seeders;

use App\Models\Apprenant;
use App\Models\User;
use App\Services\PaiementService;
use Illuminate\Database\Seeder;

class PaiementSeeder extends Seeder
{
    public function run(PaiementService $service): void
    {
        $caissier = User::where('email', 'comptable@djaart.school')->firstOrFail();

        // Aïcha Traoré (6ème A) : paiement total de la 1ère tranche -> l'inscription
        // passe automatiquement en "validee".
        $aicha = Apprenant::where('matricule', 'LDD-00001')->firstOrFail();
        $inscriptionAicha = $aicha->inscriptions()->firstOrFail();
        $premiereTranche = $inscriptionAicha->fraisScolarite->tranches()->where('numero', 1)->firstOrFail();

        $service->encaisser([
            'inscription_id' => $inscriptionAicha->id,
            'tranche_id' => $premiereTranche->id,
            'montant' => $premiereTranche->montant,
            'mode_paiement' => 'especes',
        ], $caissier);

        // Moussa Koné (5ème A, comptant) : paiement partiel -> tranche "partielle",
        // inscription reste "en_cours".
        $moussa = Apprenant::where('matricule', 'LDD-00002')->firstOrFail();
        $inscriptionMoussa = $moussa->inscriptions()->firstOrFail();
        $trancheMoussa = $inscriptionMoussa->fraisScolarite->tranches()->where('numero', 1)->firstOrFail();

        $service->encaisser([
            'inscription_id' => $inscriptionMoussa->id,
            'tranche_id' => $trancheMoussa->id,
            'montant' => 50000,
            'mode_paiement' => 'mobile_money',
        ], $caissier);
    }
}
