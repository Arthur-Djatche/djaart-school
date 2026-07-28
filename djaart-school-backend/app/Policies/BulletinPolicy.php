<?php

namespace App\Policies;

use App\Models\Bulletin;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class BulletinPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])
            || $user->can('acces.bulletins');
    }

    public function view(User $user, Bulletin $bulletin): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $bulletin);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])
            || $user->can('acces.bulletins');
    }
}
