<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    use ApiResponse;

    private const CIVILITES = ['M.', 'Mme', 'Mlle'];

    /**
     * Informations de profil modifiables par tout utilisateur pour lui-meme
     * (nom complet, civilite) — jamais le role ni l'etablissement.
     */
    public function mettreAJourProfil(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'civilite' => ['nullable', Rule::in(self::CIVILITES)],
        ]);

        $user = $request->user();
        $user->update($data);

        return $this->success(new UserResource($user->load(['etablissement', 'etablissementsGeres'])), 'Profil mis à jour.');
    }

    public function mettreAJourPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $chemin = $request->file('photo')->store('profils', 'public');
        $user->update(['photo' => $chemin]);

        return $this->success(new UserResource($user->load(['etablissement', 'etablissementsGeres'])), 'Photo de profil mise à jour.');
    }

    /**
     * Change son propre mot de passe — seul moyen de lever must_change_password
     * (cf. EnsureAccountUsable, qui bloque tout le reste tant que c'est vrai).
     */
    public function changerMotDePasse(Request $request)
    {
        $data = $request->validate([
            'mot_de_passe_actuel' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['mot_de_passe_actuel'], $user->password)) {
            throw ValidationException::withMessages([
                'mot_de_passe_actuel' => 'Mot de passe actuel incorrect.',
            ]);
        }

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        return $this->success(new UserResource($user->load(['etablissement', 'etablissementsGeres'])), 'Mot de passe mis à jour.');
    }

    /**
     * Change l'etablissement "actif" (users.etablissement_id) parmi ceux ou
     * cet acteur intervient (cf. User::etablissementsGeres) — permet de
     * permuter sans se reconnecter. Le role et les droits en direct de
     * l'utilisateur (Spatie, globaux par design) sont resynchronises sur
     * ceux stockes dans le pivot pour cet etablissement precis : un meme
     * compte peut ainsi etre secretaire ici et comptable ailleurs, avec des
     * droits acces.xxx distincts dans chaque etablissement.
     */
    public function basculerEtablissement(Request $request)
    {
        $data = $request->validate([
            'etablissement_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $lien = $user->etablissementsGeres()->where('etablissements.id', $data['etablissement_id'])->first();

        if (! $lien) {
            throw ValidationException::withMessages([
                'etablissement_id' => "Vous n'intervenez pas dans cet établissement.",
            ]);
        }

        $user->update(['etablissement_id' => $data['etablissement_id']]);

        if ($lien->pivot->role) {
            $user->syncRoles([$lien->pivot->role]);
        }
        $user->syncPermissions($lien->pivot->permissions ?? []);

        return $this->success(new UserResource($user->load(['etablissement', 'etablissementsGeres'])), 'Établissement actif changé.');
    }
}
