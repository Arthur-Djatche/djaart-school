<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pedagogie\StoreSemestreRequest;
use App\Http\Requests\Pedagogie\UpdateSemestreRequest;
use App\Http\Resources\SemestreResource;
use App\Models\Semestre;
use Illuminate\Http\Request;

class SemestreController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Semestre::class);

        $semestres = Semestre::query()
            ->with(['niveau', 'anneeAcademique'])
            ->when($request->integer('niveau_id'), fn ($query, $niveauId) => $query->where('niveau_id', $niveauId))
            ->when($request->integer('annee_academique_id'), fn ($query, $anneeId) => $query->where('annee_academique_id', $anneeId))
            ->orderBy('numero')
            ->paginate(15);

        return SemestreResource::collection($semestres)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreSemestreRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $semestre = Semestre::create($data);

        return $this->success(new SemestreResource($semestre), 'Semestre créé.', 201);
    }

    public function update(UpdateSemestreRequest $request, Semestre $semestre)
    {
        $semestre->update($request->validated());

        return $this->success(new SemestreResource($semestre), 'Semestre mis à jour.');
    }

    public function destroy(Semestre $semestre)
    {
        $this->authorize('delete', $semestre);

        $semestre->delete();

        return $this->success(null, 'Semestre supprimé.');
    }
}
