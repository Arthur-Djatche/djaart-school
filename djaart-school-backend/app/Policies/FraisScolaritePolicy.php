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
        // Lecture seule ouverte a la secretaire et au comptable (apercu de
        // l'echeancier dans le formulaire d'inscription) ; create/update/delete
        // restent reserves aux admins.
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire', 'comptable']);
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
