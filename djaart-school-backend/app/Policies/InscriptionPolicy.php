<?php

namespace App\Policies;

use App\Models\Inscription;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class InscriptionPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        // acces.seance_photo/acces.documents_masse en plus : ces deux ecrans
        // reconstituent l'effectif d'une classe via GET /inscriptions.
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire', 'comptable'])
            || $user->can('acces.inscriptions')
            || $user->can('acces.seance_photo')
            || $user->can('acces.documents_masse');
    }

    public function view(User $user, Inscription $inscription): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $inscription);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire', 'comptable'])
            || $user->can('acces.inscriptions');
    }

    public function update(User $user, Inscription $inscription): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $inscription);
    }
}
