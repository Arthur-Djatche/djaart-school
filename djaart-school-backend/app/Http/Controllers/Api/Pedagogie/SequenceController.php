<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pedagogie\StoreSequenceRequest;
use App\Http\Requests\Pedagogie\UpdateSequenceRequest;
use App\Http\Resources\SequenceResource;
use App\Models\Sequence;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Sequence::class);

        $sequences = Sequence::query()
            ->with(['niveau', 'anneeAcademique'])
            ->when($request->integer('niveau_id'), fn ($query, $niveauId) => $query->where('niveau_id', $niveauId))
            ->when($request->integer('annee_academique_id'), fn ($query, $anneeId) => $query->where('annee_academique_id', $anneeId))
            ->orderBy('numero')
            ->paginate(15);

        return SequenceResource::collection($sequences)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreSequenceRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $sequence = Sequence::create($data);

        return $this->success(new SequenceResource($sequence), 'Séquence créée.', 201);
    }

    public function update(UpdateSequenceRequest $request, Sequence $sequence)
    {
        $sequence->update($request->validated());

        return $this->success(new SequenceResource($sequence), 'Séquence mise à jour.');
    }

    public function destroy(Sequence $sequence)
    {
        $this->authorize('delete', $sequence);

        $sequence->delete();

        return $this->success(null, 'Séquence supprimée.');
    }
}
