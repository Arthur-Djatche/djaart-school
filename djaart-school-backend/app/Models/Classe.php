<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'classes';

    protected $fillable = [
        'etablissement_id',
        'niveau_id',
        'annee_academique_id',
        'professeur_principal_id',
        'libelle',
        'effectif_max',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function professeurPrincipal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'professeur_principal_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }
}
