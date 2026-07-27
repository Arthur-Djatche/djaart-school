<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleveDeNotes extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'releves_notes';

    protected $fillable = [
        'etablissement_id',
        'inscription_id',
        'semestre_id',
        'moyenne_generale',
        'mention',
        'fichier_pdf',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }
}
