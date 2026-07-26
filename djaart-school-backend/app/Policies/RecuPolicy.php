<?php

namespace App\Policies;

use App\Models\Recu;
use App\Models\User;
use App\Policies\Concerns\ChecksEtablissementOwnership;

class RecuPolicy
{
    use ChecksEtablissementOwnership;

    public function view(User $user, Recu $recu): bool
    {
        return $this->sameEtablissementOrSuperAdmin($user, $recu);
    }
}
