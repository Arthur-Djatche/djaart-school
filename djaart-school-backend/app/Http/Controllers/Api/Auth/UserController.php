<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Mail\CompteCreeMail;
use App\Mail\DroitsAccesModifiesMail;
use App\Mail\MotDePasseReinitialiseMail;
use App\Mail\NouvelEtablissementAjouteMail;
use App\Models\Etablissement;
use App\Models\User;
use App\Support\GrantablePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Liste les acteurs de MON etablissement — au sens du pivot
     * etablissement_user (appartenance), pas de users.etablissement_id (qui
     * ne reflete que l'etablissement ACTIF du moment de chacun, potentiellement
     * un tout autre etablissement pour un acteur partage). Filtrer sur cette
     * colonne exclurait a tort les acteurs qui geres plusieurs etablissements
     * mais ont actuellement bascule ailleurs.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $acteur = $request->user();
        $etablissementId = $acteur->hasRole('super_admin') ? null : $acteur->etablissement_id;

        $users = User::query()
            ->with(['etablissement', 'roles'])
            ->when($etablissementId, function ($query) use ($etablissementId) {
                $query->appartenantA($etablissementId)
                    ->with(['etablissementsGeres' => function ($q) use ($etablissementId) {
                        $q->where('etablissements.id', $etablissementId);
                    }]);
            })
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->string('role')->trim()->isNotEmpty(), function ($query) use ($request, $etablissementId) {
                $role = $request->string('role')->trim();
                // Le role recherche doit etre celui tenu DANS cet etablissement,
                // pas le role global "actif" du moment (meme raison que ci-dessus).
                if ($etablissementId) {
                    $query->avecRoleDans($etablissementId, $role);
                } else {
                    $query->role($role);
                }
            })
            ->orderBy('name')
            ->paginate(15);

        return UserResource::collection($users)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    /**
     * Si l'e-mail correspond deja a un compte existant (dans un autre
     * etablissement), on le rattache au lieu de bloquer ou de dupliquer le
     * compte : un meme acteur peut intervenir dans plusieurs etablissements,
     * avec un role propre a chacun (cf. User::etablissementsGeres).
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();
        $etablissementId = $request->user()->hasRole('super_admin')
            ? ($data['etablissement_id'] ?? null)
            : $request->user()->etablissement_id;

        $existant = User::where('email', $data['email'])->first();

        if ($existant) {
            if ($etablissementId && $existant->etablissementsGeres()->where('etablissements.id', $etablissementId)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'Cet acteur intervient déjà dans cet établissement.',
                ]);
            }

            if ($etablissementId) {
                $existant->etablissementsGeres()->syncWithoutDetaching([
                    $etablissementId => ['role' => $data['role'], 'permissions' => []],
                ]);

                Mail::to($existant->email)->send(new NouvelEtablissementAjouteMail($existant, Etablissement::findOrFail($etablissementId), $data['role']));
            }

            return $this->success(new UserResource($existant->load(['etablissement', 'etablissementsGeres'])), 'Acteur existant rattaché à cet établissement.', 201);
        }

        $motDePasse = Str::password(14);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $motDePasse,
            'must_change_password' => true,
            'etablissement_id' => $etablissementId,
        ]);

        $user->assignRole($data['role']);

        if ($etablissementId) {
            $user->etablissementsGeres()->syncWithoutDetaching([
                $etablissementId => ['role' => $data['role'], 'permissions' => []],
            ]);
        }

        Mail::to($user->email)->send(new CompteCreeMail($user, $motDePasse));

        return $this->success(new UserResource($user->load('etablissement')), 'Utilisateur créé.', 201);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $acteur = $request->user();

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ]);

        if (! empty($data['reinitialiser_mot_de_passe'])) {
            $motDePasse = Str::password(14);
            $user->password = $motDePasse;
            $user->must_change_password = true;
        }

        $user->save();

        if (! empty($data['role'])) {
            // Le role modifie ici s'applique a l'etablissement DE L'ACTEUR (celui
            // dans lequel il gere cette cible), pas necessairement l'etablissement
            // actif de la cible qui peut differer si elle est partagee avec
            // d'autres etablissements — sinon on ecraserait par erreur son role
            // dans un etablissement totalement etranger a cette action. Pour un
            // super_admin (non scope a un etablissement), on garde l'etablissement
            // actif de la cible comme avant.
            $etablissementId = $acteur->hasRole('super_admin') ? $user->etablissement_id : $acteur->etablissement_id;

            if ($etablissementId) {
                $user->etablissementsGeres()->syncWithoutDetaching([
                    $etablissementId => ['role' => $data['role']],
                ]);
            }

            // Le role "en direct" (Spatie, global) ne doit refleter ce
            // changement que si c'est bien l'etablissement actif de la cible
            // en ce moment — sinon elle le verra a sa prochaine bascule vers
            // cet etablissement (cf. ProfilController::basculerEtablissement).
            if ($etablissementId && $user->etablissement_id === $etablissementId) {
                $user->syncRoles([$data['role']]);
            }
        }

        if (isset($motDePasse)) {
            Mail::to($user->email)->send(new MotDePasseReinitialiseMail($user, $motDePasse));
        }

        return $this->success(new UserResource($user->load('etablissement')), 'Utilisateur mis à jour.');
    }

    /**
     * Droits supplementaires accordes individuellement, en complement du
     * role de l'utilisateur cible (cf. App\Support\GrantablePermissions).
     * "Comptes utilisateurs" et "Demandes de demo" restent hors catalogue :
     * jamais synchronisables ici.
     */
    public function updatePermissions(Request $request, User $user)
    {
        $acteur = $request->user();

        if ($acteur->is($user)) {
            throw ValidationException::withMessages([
                'permissions' => "Vous ne pouvez pas modifier vos propres droits d'accès.",
            ]);
        }

        // Meme logique que UserPolicy::sameTenantOrSuperAdmin (cf. User::appartientA).
        if (! $acteur->hasRole('super_admin') && ! $user->appartientA($acteur->etablissement_id)) {
            throw ValidationException::withMessages([
                'permissions' => "Vous ne pouvez modifier les droits que des acteurs de votre établissement.",
            ]);
        }

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => [Rule::in(GrantablePermissions::cles())],
        ]);

        // Ces droits s'appliquent a l'etablissement DE L'ACTEUR (cf. update()) ;
        // le pivot garde la trace pour chaque etablissement, le role/droits "en
        // direct" (Spatie) ne sont resynchronises que si c'est bien l'etablissement
        // actif de la cible en ce moment.
        $etablissementId = $acteur->hasRole('super_admin') ? $user->etablissement_id : $acteur->etablissement_id;

        if ($etablissementId) {
            $user->etablissementsGeres()->syncWithoutDetaching([
                $etablissementId => ['permissions' => $data['permissions']],
            ]);
        }

        if ($etablissementId && $user->etablissement_id === $etablissementId) {
            $user->syncPermissions($data['permissions']);
        }

        Mail::to($user->email)->send(new DroitsAccesModifiesMail($user));

        return $this->success(new UserResource($user->load('etablissement')), 'Droits d\'accès mis à jour.');
    }

    /**
     * "Supprimer" un acteur ne doit retirer son compte entier que s'il
     * n'intervient plus nulle part ailleurs — un acteur partage avec
     * d'autres etablissements ne doit pas voir son compte detruit par un
     * seul des admins qui le gerent : on le detache seulement de MON
     * etablissement (le sien s'il n'en gerait qu'un, ce qui revient au
     * meme resultat qu'avant pour le cas non partage).
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $acteur = $request->user();
        $etablissementId = $acteur->hasRole('super_admin') ? $user->etablissement_id : $acteur->etablissement_id;

        if ($etablissementId) {
            $user->etablissementsGeres()->detach($etablissementId);
        }

        $etablissementRestant = $user->etablissementsGeres()->first();

        if (! $etablissementRestant) {
            $user->delete();

            return $this->success(null, 'Utilisateur supprimé.');
        }

        if ($user->etablissement_id === $etablissementId) {
            $user->update(['etablissement_id' => $etablissementRestant->id]);
            $user->syncRoles([$etablissementRestant->pivot->role]);
            $user->syncPermissions($etablissementRestant->pivot->permissions ?? []);
        }

        return $this->success(null, 'Utilisateur retiré de cet établissement (reste actif ailleurs).');
    }
}
