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
        // pour la saisie CC/Session Normale) et a la secretaire (generation des
        // releves) ; create/update/delete restent reserves aux admins.
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'enseignant', 'secretaire'])
            || $user->can('acces.sequences')
            || $user->can('acces.semestres')
            || $user->can('acces.notes')
            || $user->can('acces.conduite')
            || $user->can('acces.bulletins')
            || $user->can('acces.releves');
    }

    public function view(User $user, Semestre $semestre): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $semestre);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.semestres');
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
