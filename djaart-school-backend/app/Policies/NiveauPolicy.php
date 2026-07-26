<?php

namespace App\Policies;

use App\Models\Niveau;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class NiveauPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function view(User $user, Niveau $niveau): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $niveau);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, Niveau $niveau): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $niveau);
    }

    public function delete(User $user, Niveau $niveau): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $niveau);
    }
}
