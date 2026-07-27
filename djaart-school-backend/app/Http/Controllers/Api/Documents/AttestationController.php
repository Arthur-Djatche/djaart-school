<?php

namespace App\Http\Controllers\Api\Documents;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\BuildsZipArchive;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttestationResource;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Classe;
use App\Services\AttestationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttestationController extends Controller
{
    use ApiResponse;
    use BuildsZipArchive;

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

    /**
     * Genere une attestation pour plusieurs apprenants d'une classe en un
     * seul appel : un echec individuel (ex. inscription non validee) est
     * consigne ligne par ligne sans interrompre le reste du lot.
     */
    public function storeMasse(Request $request, Classe $classe)
    {
        $this->autoriserGererDocuments($request, $classe);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['scolarite', 'frequentation', 'reussite'])],
            'apprenant_ids' => ['sometimes', 'array'],
            'apprenant_ids.*' => ['integer'],
        ]);

        $inscriptions = $classe->inscriptions()
            ->where('statut', '!=', 'annulee')
            ->when(
                ! empty($validated['apprenant_ids']),
                fn ($query) => $query->whereIn('apprenant_id', $validated['apprenant_ids']),
            )
            ->with('apprenant')
            ->get();

        $resultats = $inscriptions->map(function ($inscription) use ($validated) {
            $apprenant = $inscription->apprenant;

            try {
                $attestation = $this->attestationService->generer($apprenant, $validated['type']);

                return [
                    'apprenant_id' => $apprenant->id,
                    'nom' => "{$apprenant->prenom} {$apprenant->nom}",
                    'success' => true,
                    'attestation_id' => $attestation->id,
                    'message' => null,
                ];
            } catch (ValidationException $e) {
                return [
                    'apprenant_id' => $apprenant->id,
                    'nom' => "{$apprenant->prenom} {$apprenant->nom}",
                    'success' => false,
                    'attestation_id' => null,
                    'message' => collect($e->errors())->flatten()->first(),
                ];
            }
        });

        return $this->success($resultats);
    }

    public function zip(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->filter()
            ->map(fn ($id) => (int) $id);

        $attestations = Attestation::whereIn('id', $ids)->with('apprenant')->get();

        foreach ($attestations as $attestation) {
            $this->authorize('gererDocuments', $attestation->apprenant);
        }

        return $this->zipperDocuments(
            $attestations,
            fn (Attestation $attestation) => "attestation-{$attestation->numero}.pdf",
            'attestations.zip',
        );
    }

    public function telecharger(Attestation $attestation)
    {
        $this->authorize('gererDocuments', $attestation->apprenant);

        return Storage::disk('local')->download($attestation->fichier_pdf, "attestation-{$attestation->numero}.pdf");
    }

    private function autoriserGererDocuments(Request $request, Classe $classe): void
    {
        $user = $request->user();

        $autorise = $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])
            && ($user->hasRole('super_admin') || $user->etablissement_id === $classe->etablissement_id);

        abort_unless($autorise, 403);
    }
}
