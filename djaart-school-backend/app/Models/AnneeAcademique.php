<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeAcademique extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'annees_academiques';

    protected $fillable = [
        'etablissement_id',
        'libelle',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
}
