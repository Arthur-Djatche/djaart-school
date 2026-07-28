<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
    ];
}
