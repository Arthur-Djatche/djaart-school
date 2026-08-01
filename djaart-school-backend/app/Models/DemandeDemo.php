<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandeDemo extends Model
{
    use HasFactory;

    protected $table = 'demandes_demo';

    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'nom_etablissement',
        'effectif_estime',
        'message',
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
