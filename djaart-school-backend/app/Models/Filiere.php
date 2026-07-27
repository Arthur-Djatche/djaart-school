<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Filiere extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'nom',
        'code',
        'chef_departement_id',
    ];

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class);
    }

    public function chefDepartement(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chef_departement_id');
    }
}
