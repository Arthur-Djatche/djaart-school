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
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'enseignant', 'secretaire']);
    }

    public function view(User $user, UniteEnseignement $uniteEnseignement): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $uniteEnseignement);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
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
