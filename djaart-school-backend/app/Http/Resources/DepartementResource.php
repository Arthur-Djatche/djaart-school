<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'nom' => $this->nom,
            'code' => $this->code,
            'chef_departement_id' => $this->chef_departement_id,
            'chef_departement' => $this->whenLoaded('chefDepartement', fn () => $this->chefDepartement ? [
                'id' => $this->chefDepartement->id,
                'name' => $this->chefDepartement->name,
            ] : null),
        ];
    }
}
