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
        'type_etablissement_secondaire',
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
     * Type(s) cumules de l'etablissement (1 ou 2), pour toute logique devant
     * traiter les deux uniformement (ex. visibilite des menus par type).
     */
    public function typesEtablissement(): array
    {
        return array_values(array_filter([$this->type_etablissement, $this->type_etablissement_secondaire]));
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
