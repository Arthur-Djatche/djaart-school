<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SequenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'niveau_id' => $this->niveau_id,
            'annee_academique_id' => $this->annee_academique_id,
            'numero' => $this->numero,
            'libelle' => $this->libelle,
            'niveau' => $this->whenLoaded('niveau', fn () => [
                'id' => $this->niveau->id,
                'libelle' => $this->niveau->libelle,
            ]),
            'annee_academique' => $this->whenLoaded('anneeAcademique', fn () => [
                'id' => $this->anneeAcademique->id,
                'libelle' => $this->anneeAcademique->libelle,
            ]),
        ];
    }
}
