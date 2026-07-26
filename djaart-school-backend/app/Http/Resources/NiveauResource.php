<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NiveauResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'filiere_id' => $this->filiere_id,
            'libelle' => $this->libelle,
            'ordre' => $this->ordre,
            'type_systeme' => $this->type_systeme,
            'filiere' => $this->whenLoaded('filiere', fn () => [
                'id' => $this->filiere->id,
                'nom' => $this->filiere->nom,
            ]),
        ];
    }
}
