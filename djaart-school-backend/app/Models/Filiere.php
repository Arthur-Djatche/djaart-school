<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'nom',
        'code',
    ];

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class);
    }
}
