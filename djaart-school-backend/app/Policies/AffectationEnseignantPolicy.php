<?php

namespace App\Policies;

use App\Models\AffectationEnseignant;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class AffectationEnseignantPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'enseignant']);
    }

    public function view(User $user, AffectationEnseignant $affectation): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $affectation);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function delete(User $user, AffectationEnseignant $affectation): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $affectation);
    }

    /**
     * Consulter la grille de notes de cette affectation : le titulaire
     * (l'enseignant affecte) ou un admin du meme etablissement, en lecture.
     */
    public function voirNotes(User $user, AffectationEnseignant $affectation): bool
    {
        if ($user->hasRole('enseignant')) {
            return (int) $affectation->enseignant_id === (int) $user->id;
        }

        return $this->sameEtablissementOrSuperAdmin($user, $affectation);
    }

    /**
     * Soumettre (verrouiller) les notes : reserve au titulaire de l'affectation.
     */
    public function soumettreNotes(User $user, AffectationEnseignant $affectation): bool
    {
        return (int) $affectation->enseignant_id === (int) $user->id;
    }

    public function deverrouillerNotes(User $user, AffectationEnseignant $affectation): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement'])
            && $this->sameEtablissementOrSuperAdmin($user, $affectation);
    }
}
