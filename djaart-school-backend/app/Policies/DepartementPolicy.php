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
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])
            || $user->can('acces.inscriptions')
            || $user->can('acces.caisse')
            || $user->can('acces.frais_scolarite')
            || $user->can('acces.parametrage_academique');
    }

    public function view(User $user, Departement $departement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $departement);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.parametrage_academique');
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
