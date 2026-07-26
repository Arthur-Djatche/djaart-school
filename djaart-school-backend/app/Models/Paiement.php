<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paiement extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $fillable = [
        'etablissement_id',
        'inscription_id',
        'tranche_id',
        'montant',
        'mode_paiement',
        'caissier_id',
        'date_paiement',
    ];

    protected function casts(): array
    {
        return [
            'date_paiement' => 'date',
        ];
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function tranche(): BelongsTo
    {
        return $this->belongsTo(Tranche::class);
    }

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function recu(): HasOne
    {
        return $this->hasOne(Recu::class);
    }
}
