<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StoreCommandeRequest;
use App\Http\Requests\Landing\ValiderCommandeRequest;
use App\Http\Resources\CommandeResource;
use App\Mail\CommandeRecueMail;
use App\Mail\CommandeValideeMail;
use App\Mail\NouvelEtablissementAjouteMail;
use App\Models\Commande;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommandeController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $commandes = Commande::query()
            ->with(['etablissement', 'traitePar'])
            ->orderByDesc('id')
            ->paginate(20);

        return CommandeResource::collection($commandes)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreCommandeRequest $request)
    {
        $commande = Commande::create($request->validated());

        $emailsSuperAdmins = User::role('super_admin')->pluck('email');
        if ($emailsSuperAdmins->isNotEmpty()) {
            Mail::to($emailsSuperAdmins)->send(new CommandeRecueMail($commande));
        }

        return $this->success(new CommandeResource($commande), 'Votre commande a bien été envoyée, notre équipe vous contactera pour l\'activation.', 201);
    }

    /**
     * Cree l'etablissement + son admin (ou rattache un admin_etablissement
     * existant si l'e-mail de la commande correspond deja a un compte de ce
     * role) : durree d'acces (abonnement_expire_le), fonctionnalites
     * (droits acces.xxx accordes), type d'etablissement — tout choisi ici
     * par le super_admin, jamais par l'etablissement lui-meme.
     */
    public function valider(ValiderCommandeRequest $request, Commande $commande)
    {
        if ($commande->statut !== 'en_attente') {
            throw ValidationException::withMessages([
                'statut' => 'Cette commande a déjà été traitée.',
            ]);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($data, $commande, $request) {
            $etablissement = Etablissement::create([
                'nom' => $commande->nom_etablissement,
                'type_etablissement' => $data['type_etablissement'],
                'type_etablissement_secondaire' => $data['type_etablissement_secondaire'] ?? null,
                'abonnement_expire_le' => now()->addMonths($data['duree_mois'])->toDateString(),
            ]);

            $adminExistant = User::where('email', $commande->email)->role('admin_etablissement')->first();

            if ($adminExistant) {
                $adminExistant->givePermissionTo($data['permissions']);
                $adminExistant->etablissementsGeres()->syncWithoutDetaching([
                    $etablissement->id => ['role' => 'admin_etablissement', 'permissions' => $data['permissions']],
                ]);
                $adminExistant->update(['etablissement_id' => $etablissement->id]);

                Mail::to($adminExistant->email)->send(new NouvelEtablissementAjouteMail($adminExistant, $etablissement, 'admin_etablissement'));
            } else {
                $motDePasse = Str::password(14);

                $admin = User::create([
                    'name' => $commande->nom,
                    'email' => $commande->email,
                    'password' => $motDePasse,
                    'etablissement_id' => $etablissement->id,
                    'must_change_password' => true,
                ]);
                $admin->assignRole('admin_etablissement');
                $admin->syncPermissions($data['permissions']);
                $admin->etablissementsGeres()->syncWithoutDetaching([
                    $etablissement->id => ['role' => 'admin_etablissement', 'permissions' => $data['permissions']],
                ]);

                Mail::to($admin->email)->send(new CommandeValideeMail($admin, $motDePasse));
            }

            $commande->update([
                'statut' => 'validee',
                'etablissement_id' => $etablissement->id,
                'traite_par_id' => $request->user()->id,
                'traite_le' => now(),
            ]);

            return $this->success(new CommandeResource($commande->fresh(['etablissement', 'traitePar'])), 'Commande validée.');
        });
    }
}
