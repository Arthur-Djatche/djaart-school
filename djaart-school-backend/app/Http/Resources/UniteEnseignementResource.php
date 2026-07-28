<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniteEnseignementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'semestre_id' => $this->semestre_id,
            'code' => $this->code,
            'nom' => $this->nom,
            'type' => $this->type,
            'credits_ects' => $this->when($this->relationLoaded('matieres'), fn () => $this->creditsEcts()),
        ];
    }
}
