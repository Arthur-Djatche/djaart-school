<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DemandeDemoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'nom_etablissement' => $this->nom_etablissement,
            'effectif_estime' => $this->effectif_estime,
            'message' => $this->message,
            'statut' => $this->statut,
            'etablissement' => $this->whenLoaded('etablissement', fn () => $this->etablissement ? [
                'id' => $this->etablissement->id,
                'nom' => $this->etablissement->nom,
                'abonnement_expire_le' => $this->etablissement->abonnement_expire_le,
            ] : null),
            'traite_par' => $this->whenLoaded('traitePar', fn () => $this->traitePar ? [
                'id' => $this->traitePar->id,
                'name' => $this->traitePar->name,
            ] : null),
            'traite_le' => $this->traite_le,
            'created_at' => $this->created_at,
        ];
    }
}
