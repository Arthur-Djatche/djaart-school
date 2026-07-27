<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConduiteReleve extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'conduites_releves';

    protected $fillable = [
        'etablissement_id',
        'inscription_id',
        'sequence_id',
        'absences',
        'absences_non_justifiees',
        'retards',
        'mention_travail',
        'mention_conduite',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class);
    }
}
