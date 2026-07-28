<?php

namespace App\Policies;

use App\Models\Classe;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class ClassePolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        // Donnee de reference non sensible (cf. commentaire routes/api.php) :
        // lecture ouverte a tout utilisateur authentifie de l'etablissement ;
        // creation/modification/suppression restent reservees (cf. create/update/delete).
        return true;
    }

    public function view(User $user, Classe $classe): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $classe);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.parametrage_academique');
    }

    public function update(User $user, Classe $classe): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $classe);
    }

    public function delete(User $user, Classe $classe): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $classe);
    }
}
