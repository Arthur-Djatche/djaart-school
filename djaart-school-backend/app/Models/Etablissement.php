<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Etablissement extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type_etablissement',
        'abonnement_expire_le',
        'sigle',
        'adresse',
        'logo',
        'signature',
        'signature_titre',
        'entete',
        'next_matricule_sequence',
        'next_recu_sequence',
        'next_attestation_sequence',
        'next_carte_sequence',
    ];

    protected function casts(): array
    {
        return [
            'abonnement_expire_le' => 'date',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Admins geant cet etablissement (au sens large : accede/peut y basculer),
     * cf. User::etablissementsGeres().
     */
    public function adminsGerants(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function anneesAcademiques(): HasMany
    {
        return $this->hasMany(AnneeAcademique::class);
    }

    public function filieres(): HasMany
    {
        return $this->hasMany(Filiere::class);
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class);
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function matieres(): HasMany
    {
        return $this->hasMany(Matiere::class);
    }
}
