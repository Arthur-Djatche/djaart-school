<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_recu' => $this->numero_recu,
            'telechargement_url' => "/api/recus/{$this->id}/telecharger",
        ];
    }
}
