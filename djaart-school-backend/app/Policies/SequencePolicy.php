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
        // Donnee de reference non sensible : lecture ouverte a tout
        // utilisateur authentifie de l'etablissement (cf. routes/api.php).
        return true;
    }

    public function view(User $user, Sequence $sequence): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $sequence);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.sequences');
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
