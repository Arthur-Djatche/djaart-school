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
        // Lecture seule ouverte a l'enseignant/secretaire pour la meme raison
        // que SemestrePolicy (selection lors de la saisie de notes / parametrage
        // des matieres) ; create/update/delete restent reserves aux admins.
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'enseignant', 'secretaire'])
            || $user->can('acces.inscriptions')
            || $user->can('acces.caisse')
            || $user->can('acces.frais_scolarite')
            || $user->can('acces.parametrage_academique');
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
