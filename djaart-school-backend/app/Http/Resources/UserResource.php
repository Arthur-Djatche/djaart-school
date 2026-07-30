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
            'permissions' => $this->getDirectPermissions()->pluck('name'),
            'must_change_password' => (bool) $this->must_change_password,
            'etablissement' => $this->whenLoaded('etablissement', fn () => $this->etablissement ? [
                'id' => $this->etablissement->id,
                'nom' => $this->etablissement->nom,
                'type_etablissement' => $this->etablissement->type_etablissement,
            ] : null),
            'etablissements_geres' => $this->when(
                $this->relationLoaded('etablissementsGeres') && $this->etablissementsGeres->count() > 1,
                fn () => $this->etablissementsGeres->map(fn ($e) => ['id' => $e->id, 'nom' => $e->nom])->values(),
            ),
        ];
    }
}
