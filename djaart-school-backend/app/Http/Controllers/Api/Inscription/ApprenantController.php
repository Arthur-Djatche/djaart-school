<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApprenantResource;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class ApprenantController extends Controller
{
    use ApiResponse;

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

        $data = $inscriptions->map(function ($inscription) {
            $paiementsParTranche = $inscription->paiements->groupBy('tranche_id');

            return [
                'id' => $inscription->id,
                'statut' => $inscription->statut,
                'type_inscription' => $inscription->type_inscription,
                'classe' => ['id' => $inscription->classe->id, 'libelle' => $inscription->classe->libelle],
                'annee_academique' => ['id' => $inscription->anneeAcademique->id, 'libelle' => $inscription->anneeAcademique->libelle],
                'tranches' => $inscription->fraisScolarite->tranches->map(function ($tranche) use ($paiementsParTranche) {
                    $montantPaye = (float) ($paiementsParTranche->get($tranche->id, collect())->sum('montant'));
                    $solde = round($tranche->montant - $montantPaye, 2);
                    $statut = $solde <= 0
                        ? 'payee'
                        : ($montantPaye > 0
                            ? 'partielle'
                            : ($tranche->date_echeance->isPast() ? 'en_retard' : 'en_attente'));

                    return [
                        'id' => $tranche->id,
                        'numero' => $tranche->numero,
                        'montant' => $tranche->montant,
                        'date_echeance' => $tranche->date_echeance->toDateString(),
                        'montant_paye' => $montantPaye,
                        'solde' => $solde,
                        'statut' => $statut,
                    ];
                }),
            ];
        });

        return $this->success([
            'apprenant' => new ApprenantResource($apprenant),
            'inscriptions' => $data,
        ]);
    }
}
