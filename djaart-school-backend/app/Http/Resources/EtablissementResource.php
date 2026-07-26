<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtablissementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type_etablissement' => $this->type_etablissement,
            'sigle' => $this->sigle,
            'adresse' => $this->adresse,
        ];
    }
}
