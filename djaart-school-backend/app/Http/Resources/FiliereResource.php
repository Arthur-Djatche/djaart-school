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
        ];
    }
}
