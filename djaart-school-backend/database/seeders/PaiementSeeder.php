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

        // Aïcha Traoré (6ème A, grille à 3 tranches de 50 000, frais d'inscription
        // paramétrés à 25 000) : paiement PARTIEL de la 1ère tranche (30 000 sur
        // 50 000) qui couvre néanmoins les frais d'inscription -> l'inscription
        // passe a "validee" alors meme que la tranche 1 reste "partielle".
        $aicha = Apprenant::where('matricule', 'LD00000001')->firstOrFail();
        $inscriptionAicha = $aicha->inscriptions()->firstOrFail();
        $premiereTranche = $inscriptionAicha->fraisScolarite->tranches()->where('numero', 1)->firstOrFail();

        $service->encaisser([
            'inscription_id' => $inscriptionAicha->id,
            'tranche_id' => $premiereTranche->id,
            'montant' => 30000,
            'mode_paiement' => 'especes',
        ], $caissier);

        // Moussa Koné (5ème A, comptant, frais d'inscription paramétrés à 20 000) :
        // paiement partiel (50 000 sur 140 000) qui couvre lui aussi les frais
        // d'inscription -> inscription validée, tranche unique encore "partielle".
        $moussa = Apprenant::where('matricule', 'LD00000002')->firstOrFail();
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
