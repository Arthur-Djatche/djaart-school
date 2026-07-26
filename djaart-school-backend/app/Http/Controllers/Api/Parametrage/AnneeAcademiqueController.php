<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreAnneeAcademiqueRequest;
use App\Http\Requests\Parametrage\UpdateAnneeAcademiqueRequest;
use App\Http\Resources\AnneeAcademiqueResource;
use App\Models\AnneeAcademique;
use Illuminate\Http\Request;

class AnneeAcademiqueController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $this->authorize('viewAny', AnneeAcademique::class);

        $annees = AnneeAcademique::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where('libelle', 'like', '%'.$request->string('search')->trim().'%');
            })
            ->orderByDesc('date_debut')
            ->paginate(15);

        return AnneeAcademiqueResource::collection($annees)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(StoreAnneeAcademiqueRequest $request)
    {
        $data = $request->validated();
        $data['etablissement_id'] = $request->user()->hasRole('super_admin')
            ? $data['etablissement_id']
            : $request->user()->etablissement_id;

        $anneeAcademique = AnneeAcademique::create($data);

        return $this->success(new AnneeAcademiqueResource($anneeAcademique), 'Année académique créée.', 201);
    }

    public function update(UpdateAnneeAcademiqueRequest $request, AnneeAcademique $anneeAcademique)
    {
        $anneeAcademique->update($request->validated());

        return $this->success(new AnneeAcademiqueResource($anneeAcademique), 'Année académique mise à jour.');
    }

    public function destroy(AnneeAcademique $anneeAcademique)
    {
        $this->authorize('delete', $anneeAcademique);

        $anneeAcademique->delete();

        return $this->success(null, 'Année académique supprimée.');
    }
}
