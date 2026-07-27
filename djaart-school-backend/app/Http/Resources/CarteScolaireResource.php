<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarteScolaireResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'numero_duplicata' => $this->numero_duplicata,
            'date_emission' => $this->date_emission?->toDateString(),
            'date_expiration' => $this->date_expiration?->toDateString(),
            'telechargement_url' => "/api/cartes-scolaires/{$this->id}/telecharger",
        ];
    }
}
