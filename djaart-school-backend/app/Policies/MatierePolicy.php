<?php

namespace App\Policies;

use App\Models\Matiere;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class MatierePolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.parametrage_academique');
    }

    public function view(User $user, Matiere $matiere): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $matiere);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.parametrage_academique');
    }

    public function update(User $user, Matiere $matiere): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $matiere);
    }

    public function delete(User $user, Matiere $matiere): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $matiere);
    }
}
