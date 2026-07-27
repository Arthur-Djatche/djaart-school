<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttestationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'numero' => $this->numero,
            'created_at' => $this->created_at?->toIso8601String(),
            'telechargement_url' => "/api/attestations/{$this->id}/telecharger",
        ];
    }
}
