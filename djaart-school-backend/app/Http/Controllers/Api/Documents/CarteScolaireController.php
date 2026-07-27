<?php

namespace App\Http\Controllers\Api\Documents;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Concerns\BuildsZipArchive;
use App\Http\Controllers\Controller;
use App\Http\Resources\CarteScolaireResource;
use App\Models\Apprenant;
use App\Models\CarteScolaire;
use App\Models\Classe;
use App\Services\CarteScolaireService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CarteScolaireController extends Controller
{
    use ApiResponse;
    use BuildsZipArchive;

    public function __construct(private readonly CarteScolaireService $carteScolaireService)
    {
    }

    public function index(Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $cartes = CarteScolaire::where('apprenant_id', $apprenant->id)->orderByDesc('id')->get();

        return $this->success(CarteScolaireResource::collection($cartes));
    }

    public function store(Apprenant $apprenant)
    {
        $this->authorize('gererDocuments', $apprenant);

        $carte = $this->carteScolaireService->generer($apprenant);

        return $this->success(new CarteScolaireResource($carte), 'Carte scolaire générée.', 201);
    }

    /**
     * Genere une carte scolaire pour plusieurs apprenants d'une classe en un
     * seul appel : un echec individuel (ex. pas de photo) est consigne ligne
     * par ligne sans interrompre le reste du lot.
     */
    public function storeMasse(Request $request, Classe $classe)
    {
        $this->autoriserGererDocuments($request, $classe);

        $validated = $request->validate([
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

        $resultats = $inscriptions->map(function ($inscription) {
            $apprenant = $inscription->apprenant;

            try {
                $carte = $this->carteScolaireService->generer($apprenant);

                return [
                    'apprenant_id' => $apprenant->id,
                    'nom' => "{$apprenant->prenom} {$apprenant->nom}",
                    'success' => true,
                    'carte_id' => $carte->id,
                    'message' => null,
                ];
            } catch (ValidationException $e) {
                return [
                    'apprenant_id' => $apprenant->id,
                    'nom' => "{$apprenant->prenom} {$apprenant->nom}",
                    'success' => false,
                    'carte_id' => null,
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

        $cartes = CarteScolaire::whereIn('id', $ids)->with('apprenant')->get();

        foreach ($cartes as $carte) {
            $this->authorize('gererDocuments', $carte->apprenant);
        }

        return $this->zipperDocuments(
            $cartes,
            fn (CarteScolaire $carte) => "carte-{$carte->numero}.pdf",
            'cartes-scolaires.zip',
        );
    }

    public function telecharger(CarteScolaire $carte)
    {
        $this->authorize('gererDocuments', $carte->apprenant);

        return Storage::disk('local')->download($carte->fichier_pdf, "carte-{$carte->numero}.pdf");
    }

    private function autoriserGererDocuments(Request $request, Classe $classe): void
    {
        $user = $request->user();

        $autorise = $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])
            && ($user->hasRole('super_admin') || $user->etablissement_id === $classe->etablissement_id);

        abort_unless($autorise, 403);
    }
}
