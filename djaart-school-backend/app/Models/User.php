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
     * "Cet utilisateur appartient-il a cet etablissement" — le pivot fait
     * foi des qu'il en porte au moins une ligne (un acteur multi-etablissement
     * peut avoir bascule ailleurs, son etablissement_id actif n'est alors plus
     * fiable pour repondre a cette question). Repli sur etablissement_id
     * uniquement pour un utilisateur SANS aucune ligne de pivot (compte cree
     * en dehors des points d'entree applicatifs habituels, ex. tinker/import) :
     * evite qu'une donnee incomplete ne verrouille injustement un admin hors
     * de son propre etablissement.
     */
    public function appartientA(?int $etablissementId): bool
    {
        if (! $etablissementId) {
            return false;
        }

        if ($this->etablissementsGeres()->exists()) {
            return $this->etablissementsGeres()->where('etablissements.id', $etablissementId)->exists();
        }

        return $this->etablissement_id === $etablissementId;
    }

    /**
     * Meme repli pivot-avec-secours-sur-etablissement_id que appartientA(),
     * au niveau requete — utilise par UserController::index() pour lister
     * les acteurs de MON etablissement sans exclure ceux qui ont bascule
     * ailleurs, ni ceux dont le pivot n'a jamais ete peuple.
     */
    public function scopeAppartenantA($query, int $etablissementId)
    {
        return $query->where(function ($q) use ($etablissementId) {
            $q->whereHas('etablissementsGeres', fn ($q2) => $q2->where('etablissements.id', $etablissementId))
                ->orWhere(function ($q2) use ($etablissementId) {
                    $q2->doesntHave('etablissementsGeres')->where('etablissement_id', $etablissementId);
                });
        });
    }

    /**
     * Meme principe que scopeAppartenantA(), pour filtrer par role TENU DANS
     * cet etablissement precis (pas le role Spatie "en direct", qui reflete
     * l'etablissement actif du moment et peut donc etre tout autre chose).
     */
    public function scopeAvecRoleDans($query, int $etablissementId, string $role)
    {
        return $query->where(function ($q) use ($etablissementId, $role) {
            $q->whereHas('etablissementsGeres', fn ($q2) => $q2->where('etablissements.id', $etablissementId)->where('etablissement_user.role', $role))
                ->orWhere(function ($q2) use ($role) {
                    $q2->doesntHave('etablissementsGeres')->role($role);
                });
        });
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
