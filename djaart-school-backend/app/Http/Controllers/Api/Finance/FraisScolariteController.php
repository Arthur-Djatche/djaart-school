<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFraisScolariteRequest;
use App\Http\Requests\Finance\UpdateFraisScolariteRequest;
use App\Http\Resources\FraisScolariteResource;
use App\Models\FraisScolarite;
use App\Services\FraisScolariteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FraisScolariteController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly FraisScolariteService $fraisScolariteService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', FraisScolarite::class);

        $fraisScolarite = FraisScolarite::query()
            ->with(['niveau', 'anneeAcademique', 'tranches'])
            ->when($request->integer('niveau_id'), fn ($query, $niveauId) => $query->where('niveau_id', $niveauId))
            ->when($request->integer('annee_academique_id'), fn ($query, $anneeId) => $query->where('annee_academique_id', $anneeId))
            ->orderByDesc('id')
            ->paginate(15);

        return FraisScolariteResource::collection($fraisScolarite)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreFraisScolariteRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $fraisScolarite = $this->fraisScolariteService->createWithTranches($data);

        return $this->success(new FraisScolariteResource($fraisScolarite), 'Frais de scolarité créés.', 201);
    }

    public function update(UpdateFraisScolariteRequest $request, FraisScolarite $fraisScolarite)
    {
        $fraisScolarite = $this->fraisScolariteService->replaceTranches($fraisScolarite, $request->validated());

        return $this->success(new FraisScolariteResource($fraisScolarite), 'Frais de scolarité mis à jour.');
    }

    public function destroy(FraisScolarite $fraisScolarite)
    {
        $this->authorize('delete', $fraisScolarite);

        if ($fraisScolarite->tranches()->whereHas('paiements')->exists()) {
            throw ValidationException::withMessages([
                'id' => 'Cette grille a déjà des paiements enregistrés : elle ne peut plus être supprimée (protection des données financières).',
            ]);
        }

        $fraisScolarite->delete();

        return $this->success(null, 'Frais de scolarité supprimés.');
    }
}
