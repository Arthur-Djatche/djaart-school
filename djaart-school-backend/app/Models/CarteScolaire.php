<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarteScolaire extends Model
{
    use BelongsToEtablissement, HasFactory;

    protected $table = 'cartes_scolaires';

    protected $fillable = [
        'etablissement_id',
        'apprenant_id',
        'numero',
        'numero_duplicata',
        'date_emission',
        'date_expiration',
        'fichier_pdf',
    ];

    protected function casts(): array
    {
        return [
            'date_emission' => 'date',
            'date_expiration' => 'date',
        ];
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class);
    }
}
