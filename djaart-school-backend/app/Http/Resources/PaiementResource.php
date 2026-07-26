<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaiementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'montant' => $this->montant,
            'mode_paiement' => $this->mode_paiement,
            'date_paiement' => $this->date_paiement?->toDateString(),
            'tranche' => $this->whenLoaded('tranche', fn () => [
                'id' => $this->tranche->id,
                'numero' => $this->tranche->numero,
            ]),
            'apprenant' => $this->whenLoaded('inscription', fn () => [
                'id' => $this->inscription->apprenant->id,
                'matricule' => $this->inscription->apprenant->matricule,
                'nom' => $this->inscription->apprenant->nom,
                'prenom' => $this->inscription->apprenant->prenom,
            ]),
            'classe' => $this->whenLoaded('inscription', fn () => $this->inscription->classe?->libelle),
            'recu' => new RecuResource($this->whenLoaded('recu')),
        ];
    }
}
