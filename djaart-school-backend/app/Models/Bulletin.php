<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'inscription_id',
        'sequence_id',
        'moyenne_generale',
        'rang',
        'details_groupes',
        'moyenne_classe',
        'taux_reussite',
        'moyenne_max',
        'moyenne_min',
        'absences',
        'absences_non_justifiees',
        'retards',
        'retards_non_justifies',
        'mention_travail',
        'mention_conduite',
        'tableau_honneur',
        'fichier_pdf',
    ];

    protected function casts(): array
    {
        return [
            'details_groupes' => 'array',
            'tableau_honneur' => 'boolean',
        ];
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }
}
