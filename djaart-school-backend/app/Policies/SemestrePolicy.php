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
        // Donnee de reference non sensible : lecture ouverte a tout
        // utilisateur authentifie de l'etablissement (cf. routes/api.php).
        return true;
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
