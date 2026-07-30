<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BulletinResource;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Sequence;
use App\Services\BulletinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BulletinController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly BulletinService $bulletinService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Bulletin::class);

        $bulletins = Bulletin::query()
            ->with(['inscription.apprenant', 'sequence'])
            ->when($request->integer('sequence_id'), fn ($query, $sequenceId) => $query->where('sequence_id', $sequenceId))
            ->when($request->integer('classe_id'), function ($query) use ($request) {
                $query->whereHas('inscription', fn ($q) => $q->where('classe_id', $request->integer('classe_id')));
            })
            ->orderBy('rang')
            ->get();

        return BulletinResource::collection($bulletins)->additional([
            'message' => '',
            'errors' => null,
        ]);
    }

    public function store(Request $request, Classe $classe, Sequence $sequence)
    {
        $this->authorize('create', Bulletin::class);
        abort_unless(
            $request->user()->hasRole('super_admin') || $classe->etablissement_id === $request->user()->etablissement_id,
            403,
        );

        $bulletins = $this->bulletinService->cloturerSequence($classe, $sequence);
        $bulletins->each->load(['inscription.apprenant', 'sequence']);

        return $this->success(
            BulletinResource::collection($bulletins),
            'Séquence clôturée, bulletins générés.',
            201,
        );
    }

    public function telecharger(Bulletin $bulletin)
    {
        $this->authorize('view', $bulletin);

        return Storage::disk('local')->download($bulletin->fichier_pdf, "bulletin-{$bulletin->id}.pdf");
    }

    /**
     * Bulletin jumele (ce bulletin + celui de sa sequence paire fixe,
     * 1<->2/3<->4...) sur une seule page — genere a la volee, non persiste.
     */
    public function telechargerJumele(Bulletin $bulletin)
    {
        $this->authorize('view', $bulletin);

        $pdf = $this->bulletinService->genererBulletinJumele($bulletin);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"bulletin-jumele-{$bulletin->id}.pdf\"",
        ]);
    }

    /**
     * Bulletin annuel detaille de toute la classe (une page par apprenant,
     * matiere x sequence) — genere a la volee, non persiste.
     */
    public function telechargerAnnuelDetaille(Request $request, Classe $classe)
    {
        $this->authorize('viewAny', Bulletin::class);
        abort_unless(
            $request->user()->hasRole('super_admin') || $classe->etablissement_id === $request->user()->etablissement_id,
            403,
        );

        $pdf = $this->bulletinService->genererBulletinAnnuelDetaille($classe);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"bulletin-annuel-detaille-{$classe->id}.pdf\"",
        ]);
    }
}
