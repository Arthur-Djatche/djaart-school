<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffectationEnseignant extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'affectations_enseignant';

    protected $fillable = [
        'etablissement_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'annee_academique_id',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enseignant_id');
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'affectation_id');
    }
}
