<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'type_inscription' => $this->type_inscription,
            'date_inscription' => $this->date_inscription?->toDateString(),
            'apprenant' => new ApprenantResource($this->whenLoaded('apprenant')),
            'classe' => $this->whenLoaded('classe', fn () => [
                'id' => $this->classe->id,
                'libelle' => $this->classe->libelle,
            ]),
            'annee_academique' => $this->whenLoaded('anneeAcademique', fn () => [
                'id' => $this->anneeAcademique->id,
                'libelle' => $this->anneeAcademique->libelle,
            ]),
            'frais_scolarite' => new FraisScolariteResource($this->whenLoaded('fraisScolarite')),
        ];
    }
}
