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
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire', 'comptable'])
            || $user->can('acces.inscriptions')
            || $user->can('acces.caisse')
            || $user->can('acces.frais_scolarite')
            || $user->can('acces.parametrage_academique');
    }

    public function view(User $user, FraisScolarite $fraisScolarite): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $fraisScolarite);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            || $user->can('acces.frais_scolarite');
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
