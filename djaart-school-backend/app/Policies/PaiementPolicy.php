<?php

namespace App\Policies;

use App\Models\Paiement;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class PaiementPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'comptable'])
            || $user->can('acces.caisse');
    }

    public function view(User $user, Paiement $paiement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $paiement);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'comptable'])
            || $user->can('acces.caisse');
    }
}
