<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleveDeNotesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'moyenne_generale' => $this->moyenne_generale,
            'mention' => $this->mention,
            'apprenant' => $this->whenLoaded('inscription', fn () => [
                'id' => $this->inscription->apprenant->id,
                'matricule' => $this->inscription->apprenant->matricule,
                'nom' => $this->inscription->apprenant->nom,
                'prenom' => $this->inscription->apprenant->prenom,
            ]),
            'semestre' => $this->whenLoaded('semestre', fn () => $this->semestre ? [
                'id' => $this->semestre->id,
                'libelle' => $this->semestre->libelle,
            ] : null),
            'telechargement_url' => "/api/releves/{$this->id}/telecharger",
        ];
    }
}
