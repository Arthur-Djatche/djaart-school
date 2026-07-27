<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'apprenant_id' => $this->apprenant_id,
            'valeur' => $this->valeur,
            'absent' => $this->absent,
            'soumis_le' => $this->soumis_le?->toIso8601String(),
        ];
    }
}
