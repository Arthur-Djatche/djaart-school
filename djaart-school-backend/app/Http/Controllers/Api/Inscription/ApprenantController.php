<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApprenantResource;
use App\Models\Apprenant;
use App\Services\EcheancierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApprenantController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly EcheancierService $echeancierService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Apprenant::class);

        $apprenants = Apprenant::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $search = $request->string('search')->trim();
                $query->where(function ($query) use ($search) {
                    $query->where('nom', 'like', "%{$search}%")
                        ->orWhere('prenom', 'like', "%{$search}%")
                        ->orWhere('matricule', 'like', "%{$search}%");
                });
            })
            ->orderBy('nom')
            ->limit(20)
            ->get();

        return $this->success(ApprenantResource::collection($apprenants));
    }

    public function show(Apprenant $apprenant)
    {
        $this->authorize('view', $apprenant);

        return $this->success(new ApprenantResource($apprenant));
    }

    public function echeancier(Apprenant $apprenant)
    {
        $this->authorize('view', $apprenant);

        $inscriptions = $apprenant->inscriptions()
            ->where('statut', '!=', 'annulee')
            ->with(['classe', 'anneeAcademique', 'fraisScolarite.tranches', 'paiements'])
            ->get();

        $data = $inscriptions->map(fn ($inscription) => [
            'id' => $inscription->id,
            'statut' => $inscription->statut,
            'type_inscription' => $inscription->type_inscription,
            'classe' => ['id' => $inscription->classe->id, 'libelle' => $inscription->classe->libelle],
            'annee_academique' => ['id' => $inscription->anneeAcademique->id, 'libelle' => $inscription->anneeAcademique->libelle],
            'solde_total_pension' => $this->echeancierService->soldeTotalPension($inscription),
            'tranches' => $this->echeancierService->calculerTranches($inscription),
        ]);

        return $this->success([
            'apprenant' => new ApprenantResource($apprenant),
            'inscriptions' => $data,
        ]);
    }

    public function updatePhoto(Request $request, Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        if ($apprenant->photo) {
            Storage::disk('public')->delete($apprenant->photo);
        }

        $chemin = $request->file('photo')->store("apprenants/{$apprenant->etablissement_id}", 'public');
        $apprenant->update(['photo' => $chemin]);

        return $this->success(new ApprenantResource($apprenant), 'Photo mise à jour.');
    }
}
