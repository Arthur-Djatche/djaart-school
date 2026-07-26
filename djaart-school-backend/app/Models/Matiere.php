<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matiere extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'niveau_id',
        'nom',
        'coefficient',
        'credits_ects',
    ];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }
}
