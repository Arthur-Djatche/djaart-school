<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatiereResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'etablissement_id' => $this->etablissement_id,
            'niveau_id' => $this->niveau_id,
            'nom' => $this->nom,
            'groupe' => $this->groupe,
            'coefficient' => $this->coefficient,
            'credits_ects' => $this->credits_ects,
            'ponderation_cc' => $this->ponderation_cc,
            'ponderation_session_normale' => $this->ponderation_session_normale,
            'niveau' => $this->whenLoaded('niveau', fn () => [
                'id' => $this->niveau->id,
                'libelle' => $this->niveau->libelle,
            ]),
        ];
    }
}
