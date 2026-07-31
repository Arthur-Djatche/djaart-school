<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Pas de Global Scope ici : résoudre l'utilisateur authentifié (Auth::user())
     * déclenche une requête sur ce modèle, ce qui recréerait une boucle infinie
     * si un scope global appelait lui-même Auth::user() (cf. EtablissementScope,
     * réservé aux modèles métier autres que User — filtrage fait explicitement
     * dans les contrôleurs pour User).
     */
    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }

    /**
     * Etablissements dans lesquels cet acteur intervient (n'importe quel
     * role, pas seulement admin_etablissement) et entre lesquels il peut
     * permuter sans se reconnecter — etablissement_id ci-dessus est celui
     * actuellement actif, pas necessairement le seul. role/permissions du
     * pivot sont propres a chaque etablissement, synchronises sur le role
     * et les droits "en direct" de l'utilisateur a chaque bascule (cf.
     * ProfilController::basculerEtablissement) — HasRoles/HasPermissions
     * de Spatie restent globaux par design, ce pivot est la source de
     * verite par etablissement.
     */
    public function etablissementsGeres(): BelongsToMany
    {
        return $this->belongsToMany(Etablissement::class)
            ->using(EtablissementUser::class)
            ->withPivot(['role', 'permissions'])
            ->withTimestamps();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'civilite',
        'photo',
        'email',
        'password',
        'etablissement_id',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }
}
