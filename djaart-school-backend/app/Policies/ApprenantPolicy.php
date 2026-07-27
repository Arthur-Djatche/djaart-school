<?php

namespace App\Policies;

use App\Models\Apprenant;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class ApprenantPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire', 'comptable']);
    }

    public function view(User $user, Apprenant $apprenant): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $apprenant);
    }

    /**
     * Televerser la photo, generer une attestation ou une carte scolaire :
     * reserve au secretariat/admins (pas le comptable, hors de son ressort
     * financier, cf. principe de separation des roles de l'analyse UML).
     */
    public function gererDocuments(User $user, Apprenant $apprenant): bool
    {
        if (! $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])) {
            return false;
        }

        return $this->sameEtablissementOrSuperAdmin($user, $apprenant);
    }
}
