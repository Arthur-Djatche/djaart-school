<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FiliereResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'nom' => $this->nom,
            'code' => $this->code,
            'departement_id' => $this->departement_id,
            'departement' => $this->whenLoaded('departement', fn () => $this->departement ? [
                'id' => $this->departement->id,
                'nom' => $this->departement->nom,
                'chef_departement' => $this->departement->chefDepartement ? [
                    'id' => $this->departement->chefDepartement->id,
                    'name' => $this->departement->chefDepartement->name,
                ] : null,
            ] : null),
        ];
    }
}
