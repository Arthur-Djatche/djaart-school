<?php

namespace App\Policies;

use App\Models\ReleveDeNotes;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class ReleveDeNotesPolicy
{
    use ChecksEtablissementOwnership;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire']);
    }

    public function view(User $user, ReleveDeNotes $releve): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $releve);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire']);
    }
}
