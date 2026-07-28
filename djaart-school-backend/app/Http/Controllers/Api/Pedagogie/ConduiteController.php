<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pedagogie\StoreConduiteRequest;
use App\Http\Resources\ApprenantResource;
use App\Models\Classe;
use App\Models\Sequence;
use App\Services\ConduiteReleveService;
use Illuminate\Http\Request;

class ConduiteController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ConduiteReleveService $conduiteReleveService)
    {
    }

    public function show(Request $request, Classe $classe, Sequence $sequence)
    {
        $this->autoriser($request, $classe);

        $grille = $this->conduiteReleveService->chargerGrille($classe, $sequence)->map(fn ($ligne) => [
            ...$ligne,
            'apprenant' => new ApprenantResource($ligne['apprenant']),
        ]);

        return $this->success($grille);
    }

    public function store(StoreConduiteRequest $request, Classe $classe, Sequence $sequence)
    {
        $this->autoriser($request, $classe);

        $this->conduiteReleveService->enregistrer($classe, $sequence, $request->validated()['lignes']);

        return $this->success(null, 'Conduite enregistrée.');
    }

    private function autoriser(Request $request, Classe $classe): void
    {
        $user = $request->user();

        $autorise = $user->hasRole('super_admin')
            || (($user->hasAnyRole(['admin_etablissement', 'secretaire']) || $user->can('acces.conduite')) && $classe->etablissement_id === $user->etablissement_id)
            || ((int) $classe->professeur_principal_id === (int) $user->id);

        abort_unless($autorise, 403);
    }
}
