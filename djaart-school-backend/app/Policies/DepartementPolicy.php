<?php

namespace App\Policies;

use App\Models\Departement;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class DepartementPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire']);
    }

    public function view(User $user, Departement $departement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $departement);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, Departement $departement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $departement);
    }

    public function delete(User $user, Departement $departement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $departement);
    }
}
