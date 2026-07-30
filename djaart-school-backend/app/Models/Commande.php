<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commande extends Model
{
    protected $fillable = [
        'nom',
        'ville',
        'nombre_apprenants',
        'telephone',
        'email',
        'nom_etablissement',
        'statut',
        'etablissement_id',
        'traite_par_id',
        'traite_le',
    ];

    protected function casts(): array
    {
        return [
            'traite_le' => 'datetime',
        ];
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    public function traitePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'traite_par_id');
    }
}
