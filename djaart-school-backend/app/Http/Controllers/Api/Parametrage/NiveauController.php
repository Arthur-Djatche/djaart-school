<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreNiveauRequest;
use App\Http\Requests\Parametrage\UpdateNiveauRequest;
use App\Http\Resources\NiveauResource;
use App\Models\Filiere;
use App\Models\Niveau;

class NiveauController extends Controller
{
    use ApiResponse;

    public function index(Filiere $filiere)
    {
        $this->authorize('view', $filiere);
        $this->authorize('viewAny', Niveau::class);

        $niveaux = $filiere->niveaux()->with('filiere')->orderBy('ordre')->get();

        return $this->success(NiveauResource::collection($niveaux));
    }

    public function store(StoreNiveauRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $niveau = Niveau::create($data);

        return $this->success(new NiveauResource($niveau->load('filiere')), 'Niveau créé.', 201);
    }

    public function update(UpdateNiveauRequest $request, Niveau $niveau)
    {
        $niveau->update($request->validated());

        return $this->success(new NiveauResource($niveau->load('filiere')), 'Niveau mis à jour.');
    }

    public function destroy(Niveau $niveau)
    {
        $this->authorize('delete', $niveau);

        $niveau->delete();

        return $this->success(null, 'Niveau supprimé.');
    }
}
