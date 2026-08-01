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
use App\Support\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommandeController extends Controller
{
    use ApiResponse;

    /**
     * Libelles utilises pour distinguer le nom du 2e etablissement cree
     * quand type_etablissement_secondaire est choisi (cf. valider()).
     */
    private const LIBELLES_TYPE = [
        'primaire' => 'Primaire',
        'secondaire' => 'Secondaire',
        'universitaire' => 'Universitaire',
        'centre_formation' => 'Centre de formation',
    ];

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

        // Un incident de livraison ne doit jamais faire echouer la
        // soumission elle-meme (cf. App\Support\Mailer).
        $emailsSuperAdmins = User::role('super_admin')->pluck('email');
        if ($emailsSuperAdmins->isNotEmpty()) {
            Mailer::envoyer(fn () => Mail::to($emailsSuperAdmins)->send(new CommandeRecueMail($commande)));
        }

        return $this->success(new CommandeResource($commande), 'Votre commande a bien été envoyée, notre équipe vous contactera pour l\'activation.', 201);
    }

    /**
     * Cree l'etablissement + son admin (ou rattache un admin_etablissement
     * existant si l'e-mail de la commande correspond deja a un compte de ce
     * role) : durree d'acces (abonnement_expire_le), fonctionnalites
     * (droits acces.xxx accordes), type d'etablissement — tout choisi ici
     * par le super_admin, jamais par l'etablissement lui-meme. Si un 2e type
     * est choisi, un 2e etablissement distinct est cree (pas un cumul sur le
     * meme etablissement) et le meme admin y est rattache — il pourra
     * basculer de l'un a l'autre depuis son tableau de bord.
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
                'abonnement_expire_le' => now()->addMonths($data['duree_mois']),
            ]);

            $etablissementSecondaire = null;
            if (! empty($data['type_etablissement_secondaire'])) {
                $etablissementSecondaire = Etablissement::create([
                    'nom' => $commande->nom_etablissement.' — '.self::LIBELLES_TYPE[$data['type_etablissement_secondaire']],
                    'type_etablissement' => $data['type_etablissement_secondaire'],
                    'abonnement_expire_le' => $etablissement->abonnement_expire_le,
                ]);
            }

            $etablissementsAAttacher = array_filter([$etablissement->id, $etablissementSecondaire?->id]);

            $adminExistant = User::where('email', $commande->email)->role('admin_etablissement')->first();

            // Un incident de livraison ne doit jamais annuler tout le travail
            // ci-dessus (on est dans une transaction : une exception non
            // rattrapee ferait "rollback" l'etablissement et l'admin deja
            // crees) — cf. App\Support\Mailer. Pour l'e-mail avec mot de
            // passe (compte cree), on prevdefinit l'admin dans le message de
            // reponse en cas d'echec, seul canal de communication du mot de
            // passe genere.
            if ($adminExistant) {
                $adminExistant->givePermissionTo($data['permissions']);
                foreach ($etablissementsAAttacher as $etablissementId) {
                    $adminExistant->etablissementsGeres()->syncWithoutDetaching([
                        $etablissementId => ['role' => 'admin_etablissement', 'permissions' => $data['permissions']],
                    ]);
                }
                $adminExistant->update(['etablissement_id' => $etablissement->id]);

                $envoye = Mailer::envoyer(fn () => Mail::to($adminExistant->email)->send(new NouvelEtablissementAjouteMail($adminExistant, $etablissement, 'admin_etablissement')));
                $message = $envoye
                    ? 'Commande validée.'
                    : "Commande validée, mais l'e-mail de notification n'a pas pu être envoyé à {$adminExistant->email}.";
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
                foreach ($etablissementsAAttacher as $etablissementId) {
                    $admin->etablissementsGeres()->syncWithoutDetaching([
                        $etablissementId => ['role' => 'admin_etablissement', 'permissions' => $data['permissions']],
                    ]);
                }

                $envoye = Mailer::envoyer(fn () => Mail::to($admin->email)->send(new CommandeValideeMail($admin, $motDePasse)));
                $message = $envoye
                    ? 'Commande validée.'
                    : "Commande validée, mais l'e-mail contenant les identifiants n'a pas pu être envoyé à {$admin->email}. Réinitialisez son mot de passe une fois le problème résolu.";
            }

            $commande->update([
                'statut' => 'validee',
                'etablissement_id' => $etablissement->id,
                'traite_par_id' => $request->user()->id,
                'traite_le' => now(),
            ]);

            return $this->success(new CommandeResource($commande->fresh(['etablissement', 'traitePar'])), $message);
        });
    }
}
