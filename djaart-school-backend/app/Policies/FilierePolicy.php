<?php

namespace App\Policies;

use App\Models\Filiere;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class FilierePolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function view(User $user, Filiere $filiere): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $filiere);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, Filiere $filiere): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $filiere);
    }

    public function delete(User $user, Filiere $filiere): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $filiere);
    }
}
