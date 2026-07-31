<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landing\StoreDemandeDemoRequest;
use App\Http\Resources\DemandeDemoResource;
use App\Mail\DemandeDemoRecueMail;
use App\Models\DemandeDemo;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Support\Facades\Mail;

class DemandeDemoController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $demandes = DemandeDemo::orderByDesc('id')->paginate(20);

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
}
