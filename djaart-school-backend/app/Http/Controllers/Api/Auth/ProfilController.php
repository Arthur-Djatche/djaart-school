<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfilController extends Controller
{
    use ApiResponse;

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
     * Change l'etablissement "actif" (users.etablissement_id) parmi ceux que
     * cet admin_etablissement gere (cf. User::etablissementsGeres) — permet
     * de permuter la gestion de plusieurs etablissements sans se reconnecter.
     */
    public function basculerEtablissement(Request $request)
    {
        $data = $request->validate([
            'etablissement_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $autorise = $user->etablissementsGeres()->where('etablissements.id', $data['etablissement_id'])->exists();

        if (! $autorise) {
            throw ValidationException::withMessages([
                'etablissement_id' => "Vous ne gérez pas cet établissement.",
            ]);
        }

        $user->update(['etablissement_id' => $data['etablissement_id']]);

        return $this->success(new UserResource($user->load(['etablissement', 'etablissementsGeres'])), 'Établissement actif changé.');
    }
}
