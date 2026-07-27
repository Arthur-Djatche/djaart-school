<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClasseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'niveau_id' => $this->niveau_id,
            'annee_academique_id' => $this->annee_academique_id,
            'professeur_principal_id' => $this->professeur_principal_id,
            'libelle' => $this->libelle,
            'effectif_max' => $this->effectif_max,
            'niveau' => $this->whenLoaded('niveau', fn () => [
                'id' => $this->niveau->id,
                'libelle' => $this->niveau->libelle,
                'type_systeme' => $this->niveau->type_systeme,
            ]),
            'annee_academique' => $this->whenLoaded('anneeAcademique', fn () => [
                'id' => $this->anneeAcademique->id,
                'libelle' => $this->anneeAcademique->libelle,
            ]),
            'professeur_principal' => $this->whenLoaded('professeurPrincipal', fn () => $this->professeurPrincipal ? [
                'id' => $this->professeurPrincipal->id,
                'name' => $this->professeurPrincipal->name,
            ] : null),
        ];
    }
}
