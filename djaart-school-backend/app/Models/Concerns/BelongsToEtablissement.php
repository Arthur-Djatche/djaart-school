<?php

namespace App\Models\Concerns;

use App\Models\Etablissement;
use App\Models\Scopes\EtablissementScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEtablissement
{
    public static function bootBelongsToEtablissement(): void
    {
        static::addGlobalScope(new EtablissementScope);
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }
}
