<?php

namespace App\Policies;

use App\Models\UniteEnseignement;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class UniteEnseignementPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        // Donnee de reference non sensible : lecture ouverte a tout
        // utilisateur authentifie de l'etablissement (cf. routes/api.php).
        return true;
    }

    public function view(User $user, UniteEnseignement $uniteEnseignement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $uniteEnseignement);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.parametrage_academique');
    }

    public function update(User $user, UniteEnseignement $uniteEnseignement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $uniteEnseignement);
    }

    public function delete(User $user, UniteEnseignement $uniteEnseignement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $uniteEnseignement);
    }
}
