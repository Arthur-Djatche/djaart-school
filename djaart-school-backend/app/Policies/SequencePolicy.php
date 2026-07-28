<?php

namespace App\Policies;

use App\Models\Sequence;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class SequencePolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        // Lecture seule ouverte a l'enseignant (selection de la sequence en cours
        // pour la saisie des notes) et a la secretaire (cloture des bulletins) ;
        // create/update/delete restent reserves aux admins.
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'enseignant', 'secretaire']);
    }

    public function view(User $user, Sequence $sequence): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $sequence);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, Sequence $sequence): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $sequence);
    }

    public function delete(User $user, Sequence $sequence): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $sequence);
    }
}
