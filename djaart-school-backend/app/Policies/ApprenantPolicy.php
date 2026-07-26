<?php

namespace App\Policies;

use App\Models\Apprenant;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class ApprenantPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire']);
    }

    public function view(User $user, Apprenant $apprenant): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $apprenant);
    }
}
