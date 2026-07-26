<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StorePaiementRequest;
use App\Http\Resources\PaiementResource;
use App\Models\Paiement;
use App\Services\PaiementService;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PaiementService $paiementService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Paiement::class);

        $paiements = Paiement::query()
            ->with(['tranche', 'inscription.apprenant', 'inscription.classe', 'recu'])
            ->when($request->integer('inscription_id'), fn ($query, $id) => $query->where('inscription_id', $id))
            ->when($request->integer('apprenant_id'), function ($query) use ($request) {
                $query->whereHas('inscription', fn ($q) => $q->where('apprenant_id', $request->integer('apprenant_id')));
            })
            ->orderByDesc('id')
            ->paginate(15);

        return PaiementResource::collection($paiements)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StorePaiementRequest $request)
    {
        $paiement = $this->paiementService->encaisser($request->validated(), $request->user());

        return $this->success(new PaiementResource($paiement), 'Paiement enregistré.', 201);
    }
}
