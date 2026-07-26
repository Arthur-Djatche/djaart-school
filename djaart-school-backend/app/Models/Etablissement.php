<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etablissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type_etablissement',
        'sigle',
        'adresse',
        'next_matricule_sequence',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function anneesAcademiques(): HasMany
    {
        return $this->hasMany(AnneeAcademique::class);
    }

    public function filieres(): HasMany
    {
        return $this->hasMany(Filiere::class);
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function matieres(): HasMany
    {
        return $this->hasMany(Matiere::class);
    }
}
