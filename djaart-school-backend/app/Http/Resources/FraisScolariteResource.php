<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FraisScolariteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'niveau_id' => $this->niveau_id,
            'annee_academique_id' => $this->annee_academique_id,
            'montant_total' => $this->montant_total,
            'frais_inscription' => $this->frais_inscription,
            'nombre_tranches' => $this->nombre_tranches,
            'mode' => $this->nombre_tranches > 1 ? 'tranches' : 'comptant',
            'niveau' => $this->whenLoaded('niveau', fn () => [
                'id' => $this->niveau->id,
                'libelle' => $this->niveau->libelle,
            ]),
            'annee_academique' => $this->whenLoaded('anneeAcademique', fn () => [
                'id' => $this->anneeAcademique->id,
                'libelle' => $this->anneeAcademique->libelle,
            ]),
            'tranches' => TrancheResource::collection($this->whenLoaded('tranches')),
        ];
    }
}
