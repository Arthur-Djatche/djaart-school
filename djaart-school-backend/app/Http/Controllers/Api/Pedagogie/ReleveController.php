<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ReleveDeNotesResource;
use App\Models\Classe;
use App\Models\ReleveDeNotes;
use App\Services\ReleveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReleveController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReleveService $releveService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ReleveDeNotes::class);

        $releves = ReleveDeNotes::query()
            ->with(['inscription.apprenant', 'semestre'])
            ->when($request->integer('semestre_id'), fn ($query, $semestreId) => $query->where('semestre_id', $semestreId))
            ->when($request->integer('classe_id'), function ($query) use ($request) {
                $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $request->integer('classe_id')));
            })
            ->orderByDesc('id')
            ->get();

        return ReleveDeNotesResource::collection($releves)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    private function assertOwnership(Request $request, Classe $classe): void
    {
        abort_unless(
            $request->user()->hasRole('super_admin') || $classe->etablissement_id === $request->user()->etablissement_id,
            403,
        );
    }

    public function storeAnnuel(Request $request, Classe $classe)
    {
        $this->authorize('create', ReleveDeNotes::class);
        $this->assertOwnership($request, $classe);

        $releves = $classe->niveau->type_systeme === 'lmd'
            ? $this->releveService->genererAnnuelLmd($classe)
            : $this->releveService->genererAnnuelClassique($classe);
        $releves->each->load(['inscription.apprenant']);

        return $this->success(
            ReleveDeNotesResource::collection($releves),
            'Relevés annuels générés.',
            201,
        );
    }

    public function telecharger(ReleveDeNotes $releve)
    {
        $this->authorize('view', $releve);

        return Storage::disk('local')->download($releve->fichier_pdf, "releve-{$releve->id}.pdf");
    }
}
