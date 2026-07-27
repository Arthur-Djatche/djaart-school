<?php

namespace App\Http\Controllers\Api\Documents;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttestationResource;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Services\AttestationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttestationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AttestationService $attestationService)
    {
    }

    public function index(Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $attestations = Attestation::where('apprenant_id', $apprenant->id)->orderByDesc('id')->get();

        return $this->success(AttestationResource::collection($attestations));
    }

    public function store(Request $request, Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['scolarite', 'frequentation', 'reussite'])],
        ]);

        $attestation = $this->attestationService->generer($apprenant, $validated['type']);

        return $this->success(new AttestationResource($attestation), 'Attestation générée.', 201);
    }

    public function telecharger(Attestation $attestation)
    {
        $this->authorize('gererDocuments', $attestation->apprenant);

        return Storage::disk('local')->download($attestation->fichier_pdf, "attestation-{$attestation->numero}.pdf");
    }
}
