<?php

namespace App\Services;

use App\Models\Inscription;
use App\Models\Paiement;
use App\Models\Tranche;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    public function __construct(private readonly RecuService $recuService)
    {
    }

    public function encaisser(array $data, User $caissier): Paiement
    {
        return DB::transaction(function () use ($data, $caissier) {
            // Verrou sur la tranche : une Tranche est un créneau de grille partagé
            // par toutes les inscriptions d'un même niveau+année, mais le solde dû
            // est toujours calculé PAR INSCRIPTION (une même tranche numéro 1 peut
            // être payée par plusieurs apprenants différents, indépendamment).
            $tranche = Tranche::where('id', $data['tranche_id'])->lockForUpdate()->first();
            $inscription = Inscription::where('id', $data['inscription_id'])->lockForUpdate()->first();

            $montantDejaPaye = (float) Paiement::where('tranche_id', $tranche->id)
                ->where('inscription_id', $inscription->id)
                ->sum('montant');
            $solde = round($tranche->montant - $montantDejaPaye, 2);

            if ($solde <= 0) {
                throw ValidationException::withMessages([
                    'tranche_id' => 'Cette tranche est déjà intégralement payée pour cet apprenant.',
                ]);
            }

            $montant = (float) $data['montant'];

            if ($montant > $solde) {
                throw ValidationException::withMessages([
                    'montant' => "Le montant saisi ({$montant}) dépasse le solde dû sur cette tranche ({$solde}).",
                ]);
            }

            $paiement = Paiement::create([
                'etablissement_id' => $inscription->etablissement_id,
                'inscription_id' => $inscription->id,
                'tranche_id' => $tranche->id,
                'montant' => $montant,
                'mode_paiement' => $data['mode_paiement'],
                'caissier_id' => $caissier->id,
                'date_paiement' => now()->toDateString(),
            ]);

            $this->recuService->genererPour($paiement);

            $this->validerInscriptionSiFraisInscriptionCouverts($inscription);

            return $paiement->load(['tranche', 'inscription.apprenant', 'inscription.classe', 'recu']);
        });
    }

    /**
     * Les frais d'inscription sont un montant paramétré sur la grille de frais
     * (inclus dans la pension, pas un supplément), qui valide l'inscription dès
     * que le cumul des paiements de l'apprenant l'atteint — sans attendre que la
     * tranche en cours soit intégralement soldée.
     */
    private function validerInscriptionSiFraisInscriptionCouverts(Inscription $inscription): void
    {
        if ($inscription->statut !== 'en_cours') {
            return;
        }

        $fraisInscription = (float) $inscription->fraisScolarite->frais_inscription;
        $totalPaye = (float) Paiement::where('inscription_id', $inscription->id)->sum('montant');

        if ($totalPaye >= $fraisInscription) {
            $inscription->update(['statut' => 'validee']);
        }
    }
}
