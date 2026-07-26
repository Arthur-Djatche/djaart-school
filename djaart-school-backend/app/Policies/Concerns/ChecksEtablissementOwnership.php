<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait ChecksEtablissementOwnership
{
    private function sameEtablissementOrSuperAdmin(User $user, Model $model): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin_etablissement')
            && $user->etablissement_id !== null
            && $user->etablissement_id === $model->etablissement_id;
    }
}
