<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function view(User $user, User $target): bool
    {
        return $this->sameTenantOrSuperAdmin($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin_etablissement']);
    }

    public function update(User $user, User $target): bool
    {
        if ($target->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        return $this->sameTenantOrSuperAdmin($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return false;
        }

        if ($target->hasRole('super_admin') && ! $user->hasRole('super_admin')) {
            return false;
        }

        return $this->sameTenantOrSuperAdmin($user, $target);
    }

    private function sameTenantOrSuperAdmin(User $user, User $target): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return $user->hasRole('admin_etablissement')
            && $user->etablissement_id !== null
            && $user->etablissement_id === $target->etablissement_id;
    }
}
