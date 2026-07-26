<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreMatiereRequest;
use App\Http\Requests\Parametrage\UpdateMatiereRequest;
use App\Http\Resources\MatiereResource;
use App\Models\Matiere;
use Illuminate\Http\Request;

class MatiereController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Matiere::class);

        $matieres = Matiere::query()
            ->with('niveau')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('nom', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->orderBy('nom')
            ->paginate(15);

        return MatiereResource::collection($matieres)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreMatiereRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $matiere = Matiere::create($data);

        return $this->success(new MatiereResource($matiere->load('niveau')), 'Matière créée.', 201);
    }

    public function update(UpdateMatiereRequest $request, Matiere $matiere)
    {
        $matiere->update($request->validated());

        return $this->success(new MatiereResource($matiere->load('niveau')), 'Matière mise à jour.');
    }

    public function destroy(Matiere $matiere)
    {
        $this->authorize('delete', $matiere);

        $matiere->delete();

        return $this->success(null, 'Matière supprimée.');
    }
}
