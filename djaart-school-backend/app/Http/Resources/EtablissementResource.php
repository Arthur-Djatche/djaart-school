<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EtablissementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type_etablissement' => $this->type_etablissement,
            'sigle' => $this->sigle,
            'adresse' => $this->adresse,
            'logo_url' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'signature_url' => $this->signature ? Storage::disk('public')->url($this->signature) : null,
            'signature_titre' => $this->signature_titre,
            'entete_url' => $this->entete ? Storage::disk('public')->url($this->entete) : null,
        ];
    }
}
