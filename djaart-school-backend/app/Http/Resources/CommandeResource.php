<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'ville' => $this->ville,
            'nombre_apprenants' => $this->nombre_apprenants,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'nom_etablissement' => $this->nom_etablissement,
            'statut' => $this->statut,
            'etablissement' => $this->whenLoaded('etablissement', fn () => $this->etablissement ? [
                'id' => $this->etablissement->id,
                'nom' => $this->etablissement->nom,
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
