<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FraisScolarite extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'frais_scolarite';

    protected $fillable = [
        'etablissement_id',
        'niveau_id',
        'annee_academique_id',
        'montant_total',
        'nombre_tranches',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function tranches(): HasMany
    {
        return $this->hasMany(Tranche::class)->orderBy('numero');
    }
}
