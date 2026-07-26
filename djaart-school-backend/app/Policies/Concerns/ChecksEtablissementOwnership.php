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

        // Les méthodes viewAny/create de chaque policy restreignent déjà les
        // rôles autorisés à atteindre ce point ; ici on ne vérifie que
        // l'appartenance au même établissement (peu importe le rôle exact :
        // admin_etablissement, secretaire, comptable, enseignant...).
        return $user->etablissement_id !== null
            && $user->etablissement_id === $model->etablissement_id;
    }
}
