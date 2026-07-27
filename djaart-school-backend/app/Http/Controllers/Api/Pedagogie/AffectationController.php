<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pedagogie\StoreAffectationRequest;
use App\Http\Resources\AffectationEnseignantResource;
use App\Models\AffectationEnseignant;
use App\Models\Classe;
use Illuminate\Http\Request;

class AffectationController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', AffectationEnseignant::class);

        $affectations = AffectationEnseignant::query()
            ->with(['classe.niveau', 'matiere', 'enseignant'])
            ->when(
                $request->user()->hasRole('enseignant'),
                fn ($query) => $query->where('enseignant_id', $request->user()->id),
            )
            ->orderByDesc('id')
            ->paginate(15);

        return AffectationEnseignantResource::collection($affectations)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreAffectationRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $classe = Classe::findOrFail($data['classe_id']);
        $data['annee_academique_id'] = $classe->annee_academique_id;

        $affectation = AffectationEnseignant::create($data);

        return $this->success(
            new AffectationEnseignantResource($affectation->load(['classe.niveau', 'matiere', 'enseignant'])),
            'Affectation créée.',
            201,
        );
    }

    public function destroy(AffectationEnseignant $affectation)
    {
        $this->authorize('delete', $affectation);

        $affectation->delete();

        return $this->success(null, 'Affectation supprimée.');
    }
}
