<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulletinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'moyenne_generale' => $this->moyenne_generale,
            'rang' => $this->rang,
            'apprenant' => $this->whenLoaded('inscription', fn () => [
                'id' => $this->inscription->apprenant->id,
                'matricule' => $this->inscription->apprenant->matricule,
                'nom' => $this->inscription->apprenant->nom,
                'prenom' => $this->inscription->apprenant->prenom,
            ]),
            'sequence' => $this->whenLoaded('sequence', fn () => [
                'id' => $this->sequence->id,
                'libelle' => $this->sequence->libelle,
            ]),
            'telechargement_url' => "/api/bulletins/{$this->id}/telecharger",
        ];
    }
}
