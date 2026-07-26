<?php

namespace App\Policies;

use App\Models\FraisScolarite;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class FraisScolaritePolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function view(User $user, FraisScolarite $fraisScolarite): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $fraisScolarite);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, FraisScolarite $fraisScolarite): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $fraisScolarite);
    }

    public function delete(User $user, FraisScolarite $fraisScolarite): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $fraisScolarite);
    }
}
