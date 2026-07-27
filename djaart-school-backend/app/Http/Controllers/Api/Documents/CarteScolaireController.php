<?php

namespace App\Http\Controllers\Api\Documents;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CarteScolaireResource;
use App\Models\Apprenant;
use App\Models\CarteScolaire;
use App\Services\CarteScolaireService;
use Illuminate\Support\Facades\Storage;

class CarteScolaireController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CarteScolaireService $carteScolaireService)
    {
    }

    public function index(Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $cartes = CarteScolaire::where('apprenant_id', $apprenant->id)->orderByDesc('id')->get();

        return $this->success(CarteScolaireResource::collection($cartes));
    }

    public function store(Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $carte = $this->carteScolaireService->generer($apprenant);

        return $this->success(new CarteScolaireResource($carte), 'Carte scolaire générée.', 201);
    }

    public function telecharger(CarteScolaire $carte)
    {
        $this->authorize('gererDocuments', $carte->apprenant);

        return Storage::disk('local')->download($carte->fichier_pdf, "carte-{$carte->numero}.pdf");
    }
}
