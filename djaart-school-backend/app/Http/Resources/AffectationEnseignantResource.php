<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffectationEnseignantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'annee_academique_id' => $this->annee_academique_id,
            'classe' => $this->whenLoaded('classe', fn () => [
                'id' => $this->classe->id,
                'libelle' => $this->classe->libelle,
                'niveau_id' => $this->classe->niveau_id,
                'type_systeme' => $this->classe->relationLoaded('niveau') ? $this->classe->niveau->type_systeme : null,
            ]),
            'matiere' => $this->whenLoaded('matiere', fn () => [
                'id' => $this->matiere->id,
                'nom' => $this->matiere->nom,
            ]),
            'enseignant' => $this->whenLoaded('enseignant', fn () => [
                'id' => $this->enseignant->id,
                'name' => $this->enseignant->name,
            ]),
        ];
    }
}
