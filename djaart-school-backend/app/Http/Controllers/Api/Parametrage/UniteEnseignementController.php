<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreUniteEnseignementRequest;
use App\Http\Requests\Parametrage\UpdateUniteEnseignementRequest;
use App\Http\Resources\UniteEnseignementResource;
use App\Models\Semestre;
use App\Models\UniteEnseignement;

class UniteEnseignementController extends Controller
{
    use ApiResponse;

    public function index(Semestre $semestre)
    {
        $this->authorize('view', $semestre);
        $this->authorize('viewAny', UniteEnseignement::class);

        $unites = $semestre->unitesEnseignement()->with('matieres')->orderBy('code')->get();

        return $this->success(UniteEnseignementResource::collection($unites));
    }

    public function store(StoreUniteEnseignementRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $uniteEnseignement = UniteEnseignement::create($data);

        return $this->success(new UniteEnseignementResource($uniteEnseignement), 'Unité d\'enseignement créée.', 201);
    }

    public function update(UpdateUniteEnseignementRequest $request, UniteEnseignement $uniteEnseignement)
    {
        $uniteEnseignement->update($request->validated());

        return $this->success(new UniteEnseignementResource($uniteEnseignement), 'Unité d\'enseignement mise à jour.');
    }

    public function destroy(UniteEnseignement $uniteEnseignement)
    {
        $this->authorize('delete', $uniteEnseignement);

        $uniteEnseignement->delete();

        return $this->success(null, 'Unité d\'enseignement supprimée.');
    }
}
