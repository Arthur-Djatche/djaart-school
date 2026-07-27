<?php

namespace App\Http\Controllers\Api\Pedagogie;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pedagogie\StoreNoteRequest;
use App\Http\Resources\ApprenantResource;
use App\Models\AffectationEnseignant;
use App\Models\Note;
use App\Services\NoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly NoteService $noteService)
    {
    }

    public function show(Request $request, AffectationEnseignant $affectation)
    {
        $this->authorize('voirNotes', $affectation);

        $validated = $request->validate([
            'sequence_id' => ['required_without:semestre_id', 'nullable', 'integer'],
            'semestre_id' => ['required_without:sequence_id', 'nullable', 'integer'],
            'type_evaluation' => ['required', Rule::in(['sequence', 'cc', 'session_normale'])],
        ]);

        $sequenceId = $validated['sequence_id'] ?? null;
        $semestreId = $validated['semestre_id'] ?? null;

        $apprenants = $affectation->classe->inscriptions()
            ->where('statut', '!=', 'annulee')
            ->with('apprenant')
            ->get()
            ->pluck('apprenant');

        $notes = Note::where('affectation_id', $affectation->id)
            ->where('sequence_id', $sequenceId)
            ->where('semestre_id', $semestreId)
            ->where('type_evaluation', $validated['type_evaluation'])
            ->get()
            ->keyBy('apprenant_id');

        $verrouille = $notes->isNotEmpty() && $notes->first()->soumis_le !== null;

        $grille = $apprenants->map(fn ($apprenant) => [
            'apprenant' => new ApprenantResource($apprenant),
            'valeur' => $notes->get($apprenant->id)?->valeur,
            'absent' => (bool) ($notes->get($apprenant->id)?->absent ?? false),
        ]);

        return $this->success([
            'verrouille' => $verrouille,
            'notes' => $grille,
        ]);
    }

    public function store(StoreNoteRequest $request, AffectationEnseignant $affectation)
    {
        $this->noteService->soumettre($affectation, $request->validated());

        return $this->success(null, 'Notes soumises et verrouillées.', 201);
    }

    public function deverrouiller(Request $request, AffectationEnseignant $affectation)
    {
        $this->authorize('deverrouillerNotes', $affectation);

        $validated = $request->validate([
            'sequence_id' => ['required_without:semestre_id', 'nullable', 'integer'],
            'semestre_id' => ['required_without:sequence_id', 'nullable', 'integer'],
            'type_evaluation' => ['required', Rule::in(['sequence', 'cc', 'session_normale'])],
        ]);

        $this->noteService->deverrouiller($affectation, $validated);

        return $this->success(null, 'Saisie déverrouillée.');
    }
}
