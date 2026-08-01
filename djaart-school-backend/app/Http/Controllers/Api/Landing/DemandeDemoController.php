<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StoreDemandeDemoRequest;
use App\Http\Requests\Landing\ValiderDemandeDemoRequest;
use App\Http\Resources\DemandeDemoResource;
use App\Mail\DemandeDemoRecueMail;
use App\Mail\DemandeDemoValideeMail;
use App\Mail\NouvelEtablissementAjouteMail;
use App\Models\DemandeDemo;
use App\Models\Etablissement;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemandeDemoController extends Controller
{
    use ApiResponse;

    /**
     * Duree fixe d'un acces de demo — pas configurable par le super_admin,
     * contrairement a Commande::valider (acces payant, duree choisie en mois).
     */
    private const HEURES_ACCES_DEMO = 48;

    private const LIBELLES_TYPE = [
        'primaire' => 'Primaire',
        'secondaire' => 'Secondaire',
        'universitaire' => 'Universitaire',
        'centre_formation' => 'Centre de formation',
    ];

    public function index()
    {
        $demandes = DemandeDemo::with(['etablissement', 'traitePar'])
            ->orderByDesc('id')
            ->paginate(20);

        return DemandeDemoResource::collection($demandes)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreDemandeDemoRequest $request)
    {
        $demande = DemandeDemo::create($request->validated());

        // Un incident de livraison (destinataire rejete, panne SMTP...) ne
        // doit jamais faire echouer la soumission elle-meme, deja enregistree
        // ci-dessus — sinon un visiteur anonyme se voit repondre une erreur
        // serveur pour une demande pourtant bien recue.
        $emailsSuperAdmins = User::role('super_admin')->pluck('email');
        if ($emailsSuperAdmins->isNotEmpty()) {
            Mailer::envoyer(fn () => Mail::to($emailsSuperAdmins)->send(new DemandeDemoRecueMail($demande)));
        }

        return $this->success(new DemandeDemoResource($demande), 'Votre demande a bien été envoyée, notre équipe vous recontactera rapidement.', 201);
    }

    /**
     * Cree l'etablissement (+ son admin, ou rattache un admin_etablissement
     * deja existant si l'e-mail du prospect correspond deja a un compte de
     * ce role) avec un acces limite a 48h — pas de duree ni de droits
     * acces.xxx a choisir ici (cf. Commande::valider, l'acces payant), c'est
     * un bac a sable de decouverte, pas un deploiement reel.
     */
    public function valider(ValiderDemandeDemoRequest $request, DemandeDemo $demandeDemo)
    {
        if ($demandeDemo->statut !== 'en_attente') {
            throw ValidationException::withMessages([
                'statut' => 'Cette demande a déjà été traitée.',
            ]);
        }

        $data = $request->validated();

        return DB::transaction(function () use ($data, $demandeDemo, $request) {
            $expireLe = now()->addHours(self::HEURES_ACCES_DEMO);

            $etablissement = Etablissement::create([
                'nom' => $demandeDemo->nom_etablissement,
                'type_etablissement' => $data['type_etablissement'],
                'abonnement_expire_le' => $expireLe,
            ]);

            $etablissementSecondaire = null;
            if (! empty($data['type_etablissement_secondaire'])) {
                $etablissementSecondaire = Etablissement::create([
                    'nom' => $demandeDemo->nom_etablissement.' — '.self::LIBELLES_TYPE[$data['type_etablissement_secondaire']],
                    'type_etablissement' => $data['type_etablissement_secondaire'],
                    'abonnement_expire_le' => $expireLe,
                ]);
            }

            $etablissementsAAttacher = array_filter([$etablissement->id, $etablissementSecondaire?->id]);

            $adminExistant = User::where('email', $demandeDemo->email)->role('admin_etablissement')->first();

            if ($adminExistant) {
                foreach ($etablissementsAAttacher as $etablissementId) {
                    $adminExistant->etablissementsGeres()->syncWithoutDetaching([
                        $etablissementId => ['role' => 'admin_etablissement', 'permissions' => []],
                    ]);
                }
                $adminExistant->update(['etablissement_id' => $etablissement->id]);

                $envoye = Mailer::envoyer(fn () => Mail::to($adminExistant->email)->send(new NouvelEtablissementAjouteMail($adminExistant, $etablissement, 'admin_etablissement')));
                $message = $envoye
                    ? 'Demande validée.'
                    : "Demande validée, mais l'e-mail de notification n'a pas pu être envoyé à {$adminExistant->email}.";
            } else {
                $motDePasse = Str::password(14);

                $admin = User::create([
                    'name' => $demandeDemo->nom,
                    'email' => $demandeDemo->email,
                    'password' => $motDePasse,
                    'etablissement_id' => $etablissement->id,
                    'must_change_password' => true,
                ]);
                $admin->assignRole('admin_etablissement');
                foreach ($etablissementsAAttacher as $etablissementId) {
                    $admin->etablissementsGeres()->syncWithoutDetaching([
                        $etablissementId => ['role' => 'admin_etablissement', 'permissions' => []],
                    ]);
                }

                $envoye = Mailer::envoyer(fn () => Mail::to($admin->email)->send(new DemandeDemoValideeMail($admin, $motDePasse)));
                $message = $envoye
                    ? 'Demande validée.'
                    : "Demande validée, mais l'e-mail contenant les identifiants n'a pas pu être envoyé à {$admin->email}. Réinitialisez son mot de passe une fois le problème résolu.";
            }

            $demandeDemo->update([
                'statut' => 'validee',
                'etablissement_id' => $etablissement->id,
                'traite_par_id' => $request->user()->id,
                'traite_le' => now(),
            ]);

            return $this->success(new DemandeDemoResource($demandeDemo->fresh(['etablissement', 'traitePar'])), $message);
        });
    }
}
