<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->getRoleNames(),
            'etablissement' => $this->whenLoaded('etablissement', fn () => [
                'id' => $this->etablissement->id,
                'nom' => $this->etablissement->nom,
            ]),
        ];
    }
}
