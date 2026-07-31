<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Role et droits acces.xxx propres a un couple (etablissement, acteur),
 * cf. User::etablissementsGeres().
 */
class EtablissementUser extends Pivot
{
    protected $table = 'etablissement_user';

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }
}
