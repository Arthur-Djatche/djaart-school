<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreEtablissementRequest;
use App\Http\Requests\Parametrage\UpdateEtablissementRequest;
use App\Http\Resources\EtablissementResource;
use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function updateLogo(Request $request, Etablissement $etablissement)
    {
        $this->authorize('update', $etablissement);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($etablissement->logo) {
            Storage::disk('public')->delete($etablissement->logo);
        }

        $chemin = $request->file('logo')->store("etablissements/{$etablissement->id}", 'public');
        $etablissement->update(['logo' => $chemin]);

        return $this->success(new EtablissementResource($etablissement), 'Logo mis à jour.');
    }

    public function updateSignature(Request $request, Etablissement $etablissement)
    {
        $this->authorize('update', $etablissement);

        $request->validate([
            'signature' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($etablissement->signature) {
            Storage::disk('public')->delete($etablissement->signature);
        }

        $chemin = $request->file('signature')->store("etablissements/{$etablissement->id}", 'public');
        $etablissement->update(['signature' => $chemin]);

        return $this->success(new EtablissementResource($etablissement), 'Signature mise à jour.');
    }
}
