<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreFiliereRequest;
use App\Http\Requests\Parametrage\UpdateFiliereRequest;
use App\Http\Resources\FiliereResource;
use App\Models\Filiere;
use Illuminate\Http\Request;

class FiliereController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Filiere::class);

        $filieres = Filiere::query()
            ->with('chefDepartement')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('nom', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->paginate(15);

        return FiliereResource::collection($filieres)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreFiliereRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $filiere = Filiere::create($data);

        return $this->success(new FiliereResource($filiere->load('chefDepartement')), 'Filière créée.', 201);
    }

    public function update(UpdateFiliereRequest $request, Filiere $filiere)
    {
        $filiere->update($request->validated());

        return $this->success(new FiliereResource($filiere->load('chefDepartement')), 'Filière mise à jour.');
    }

    public function destroy(Filiere $filiere)
    {
        $this->authorize('delete', $filiere);

        $filiere->delete();

        return $this->success(null, 'Filière supprimée.');
    }
}
