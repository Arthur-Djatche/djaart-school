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
        // Lecture seule ouverte a la secretaire et au comptable (choix de classe
        // dans le formulaire d'inscription) ; creation/modification/suppression
        // restent reservees aux admins (cf. create/update/delete).
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire', 'comptable'])
            || $user->can('acces.inscriptions')
            || $user->can('acces.caisse')
            || $user->can('acces.frais_scolarite')
            || $user->can('acces.parametrage_academique');
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
