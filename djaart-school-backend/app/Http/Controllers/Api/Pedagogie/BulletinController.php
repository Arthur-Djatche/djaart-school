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
}
