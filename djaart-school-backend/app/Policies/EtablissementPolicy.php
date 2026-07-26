<?php

namespace App\Policies;

use App\Models\Etablissement;
use App\Models\User;

class EtablissementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function view(User $user, Etablissement $etablissement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $etablissement);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, Etablissement $etablissement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $etablissement);
    }

    public function delete(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    private function sameEtablissementOrSuperAdmin(User $user, Etablissement $etablissement): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin_etablissement') && $user->etablissement_id === $etablissement->id;
    }
}
