<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniteEnseignement extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'unites_enseignement';

    protected $fillable = [
        'etablissement_id',
        'semestre_id',
        'code',
        'nom',
        'type',
    ];

    public function semestre(): BelongsTo
    {
        return $this->belongsTo(Semestre::class);
    }

    public function matieres(): HasMany
    {
        return $this->hasMany(Matiere::class, 'unite_enseignement_id');
    }

    /**
     * Somme des credits de ses EC — jamais stockee, toujours derivee (meme
     * principe que le statut de tranche calcule a la volee dans
     * EcheancierService, pour eviter tout risque de desynchronisation).
     */
    public function creditsEcts(): int
    {
        return (int) $this->matieres->sum('credits_ects');
    }
}
