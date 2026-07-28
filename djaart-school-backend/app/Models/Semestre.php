<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semestre extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'niveau_id',
        'annee_academique_id',
        'numero',
        'libelle',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function unitesEnseignement(): HasMany
    {
        return $this->hasMany(UniteEnseignement::class, 'semestre_id');
    }

    public function matieres(): HasMany
    {
        return $this->hasMany(Matiere::class, 'semestre_id');
    }
}
