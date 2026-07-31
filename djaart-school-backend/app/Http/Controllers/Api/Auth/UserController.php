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

    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['etablissement', 'roles'])
            ->when(! $request->user()->hasRole('super_admin'), function ($query) use ($request) {
                $query->where('etablissement_id', $request->user()->etablissement_id);
            })
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->string('role')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->role($request->string('role')->trim());
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
            $user->syncRoles([$data['role']]);

            // Le role modifie ici est celui de l'etablissement actif de
            // l'utilisateur cible : on garde le pivot en phase, sinon son
            // ancien role y reapparaitrait a la prochaine bascule (cf.
            // ProfilController::basculerEtablissement).
            if ($user->etablissement_id) {
                $user->etablissementsGeres()->syncWithoutDetaching([
                    $user->etablissement_id => ['role' => $data['role']],
                ]);
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

        if (! $acteur->hasRole('super_admin') && $acteur->etablissement_id !== $user->etablissement_id) {
            throw ValidationException::withMessages([
                'permissions' => "Vous ne pouvez modifier les droits que des acteurs de votre établissement.",
            ]);
        }

        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*' => [Rule::in(GrantablePermissions::cles())],
        ]);

        $user->syncPermissions($data['permissions']);

        // Meme logique que le role dans update() : ces droits s'appliquent
        // a l'etablissement actif de l'utilisateur cible, garde en phase
        // dans le pivot pour survivre a une bascule d'etablissement.
        if ($user->etablissement_id) {
            $user->etablissementsGeres()->syncWithoutDetaching([
                $user->etablissement_id => ['permissions' => $data['permissions']],
            ]);
        }

        Mail::to($user->email)->send(new DroitsAccesModifiesMail($user));

        return $this->success(new UserResource($user->load('etablissement')), 'Droits d\'accès mis à jour.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return $this->success(null, 'Utilisateur supprimé.');
    }
}
