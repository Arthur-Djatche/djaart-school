<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreClasseRequest;
use App\Http\Requests\Parametrage\UpdateClasseRequest;
use App\Http\Resources\ClasseResource;
use App\Models\Classe;
use Illuminate\Http\Request;

class ClasseController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Classe::class);

        $classes = Classe::query()
            ->with(['niveau', 'anneeAcademique'])
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('libelle', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->orderBy('libelle')
            ->paginate(15);

        return ClasseResource::collection($classes)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreClasseRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $classe = Classe::create($data);

        return $this->success(new ClasseResource($classe->load(['niveau', 'anneeAcademique'])), 'Classe créée.', 201);
    }

    public function update(UpdateClasseRequest $request, Classe $classe)
    {
        $classe->update($request->validated());

        return $this->success(new ClasseResource($classe->load(['niveau', 'anneeAcademique'])), 'Classe mise à jour.');
    }

    public function destroy(Classe $classe)
    {
        $this->authorize('delete', $classe);

        $classe->delete();

        return $this->success(null, 'Classe supprimée.');
    }
}
