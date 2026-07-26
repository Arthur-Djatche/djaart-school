<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inscription\StoreInscriptionRequest;
use App\Http\Resources\InscriptionResource;
use App\Models\Inscription;
use App\Services\InscriptionService;
use Illuminate\Http\Request;

class InscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly InscriptionService $inscriptionService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Inscription::class);

        $inscriptions = Inscription::query()
            ->with(['apprenant', 'classe', 'anneeAcademique', 'fraisScolarite.tranches'])
            ->when($request->integer('classe_id'), fn ($query, $classeId) => $query->where('classe_id', $classeId))
            ->when($request->string('statut')->trim()->isNotEmpty(), fn ($query) => $query->where('statut', $request->string('statut')))
            ->when($request->integer('annee_academique_id'), fn ($query, $anneeId) => $query->where('annee_academique_id', $anneeId))
            ->orderByDesc('id')
            ->paginate(15);

        return InscriptionResource::collection($inscriptions)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreInscriptionRequest $request)
    {
        $inscription = $this->inscriptionService->inscrire($request->validated());

        return $this->success(new InscriptionResource($inscription), 'Inscription enregistrée.', 201);
    }

    public function cancel(Inscription $inscription)
    {
        $this->authorize('update', $inscription);

        $inscription = $this->inscriptionService->annuler($inscription);

        return $this->success(new InscriptionResource($inscription->load(['apprenant', 'classe', 'anneeAcademique', 'fraisScolarite.tranches'])), 'Inscription annulée.');
    }
}
