<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreEtablissementRequest;
use App\Http\Requests\Parametrage\UpdateEtablissementRequest;
use App\Http\Resources\EtablissementResource;
use App\Models\Etablissement;
use Illuminate\Http\Request;

class EtablissementController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Etablissement::class);

        $etablissements = Etablissement::query()
            ->when(! $request->user()->hasRole('super_admin'), function ($query) use ($request) {
                $query->where('id', $request->user()->etablissement_id);
            })
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('nom', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->orderBy('nom')
            ->paginate(15);

        return EtablissementResource::collection($etablissements)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreEtablissementRequest $request)
    {
        $etablissement = Etablissement::create($request->validated());

        return $this->success(new EtablissementResource($etablissement), 'Établissement créé.', 201);
    }

    public function update(UpdateEtablissementRequest $request, Etablissement $etablissement)
    {
        $etablissement->update($request->validated());

        return $this->success(new EtablissementResource($etablissement), 'Établissement mis à jour.');
    }

    public function destroy(Etablissement $etablissement)
    {
        $this->authorize('delete', $etablissement);

        $etablissement->delete();

        return $this->success(null, 'Établissement supprimé.');
    }
}
