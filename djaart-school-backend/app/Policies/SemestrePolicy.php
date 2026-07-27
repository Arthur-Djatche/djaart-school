<?php

namespace App\Policies;

use App\Models\Semestre;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class SemestrePolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        // Lecture seule ouverte a l'enseignant (selection du semestre en cours
        // pour la saisie CC/Session Normale) ; create/update/delete restent
        // reserves aux admins.
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'enseignant']);
    }

    public function view(User $user, Semestre $semestre): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $semestre);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, Semestre $semestre): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $semestre);
    }

    public function delete(User $user, Semestre $semestre): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $semestre);
    }
}
