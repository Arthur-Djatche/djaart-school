<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeDemoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'nom_etablissement' => $this->nom_etablissement,
            'effectif_estime' => $this->effectif_estime,
            'message' => $this->message,
            'created_at' => $this->created_at,
        ];
    }
}
