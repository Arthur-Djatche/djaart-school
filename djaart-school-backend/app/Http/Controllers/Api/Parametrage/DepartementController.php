<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreDepartementRequest;
use App\Http\Requests\Parametrage\UpdateDepartementRequest;
use App\Http\Resources\DepartementResource;
use App\Models\Departement;
use Illuminate\Http\Request;

class DepartementController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Departement::class);

        $departements = Departement::query()
            ->with('chefDepartement')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('nom', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->paginate(15);

        return DepartementResource::collection($departements)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreDepartementRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $departement = Departement::create($data);

        return $this->success(new DepartementResource($departement->load('chefDepartement')), 'Département créé.', 201);
    }

    public function update(UpdateDepartementRequest $request, Departement $departement)
    {
        $departement->update($request->validated());

        return $this->success(new DepartementResource($departement->load('chefDepartement')), 'Département mis à jour.');
    }

    public function destroy(Departement $departement)
    {
        $this->authorize('delete', $departement);

        $departement->delete();

        return $this->success(null, 'Département supprimé.');
    }
}
