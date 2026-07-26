<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscription extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'apprenant_id',
        'classe_id',
        'annee_academique_id',
        'frais_scolarite_id',
        'statut',
        'type_inscription',
        'date_inscription',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
        ];
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeAcademique(): BelongsTo
    {
        return $this->belongsTo(AnneeAcademique::class);
    }

    public function fraisScolarite(): BelongsTo
    {
        return $this->belongsTo(FraisScolarite::class);
    }
}
