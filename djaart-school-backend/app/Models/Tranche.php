<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tranche extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'frais_scolarite_id',
        'numero',
        'montant',
        'date_echeance',
    ];

    protected function casts(): array
    {
        return [
            'date_echeance' => 'date',
        ];
    }

    public function fraisScolarite(): BelongsTo
    {
        return $this->belongsTo(FraisScolarite::class);
    }

    /**
     * Tous les paiements passés sur ce créneau de tranche, tous apprenants
     * confondus (une Tranche est un modèle de grille partagé par toutes les
     * inscriptions d'un même niveau+année). Ne PAS utiliser pour calculer
     * le solde d'un apprenant précis : toujours filtrer par inscription_id
     * (cf. app/Services/PaiementService.php et l'échéancier apprenant).
     */
    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }
}
