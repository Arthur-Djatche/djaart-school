<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Classe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PhotoMasseController extends Controller
{
    use ApiResponse;

    /**
     * Importe un lot de photos deja prises dans l'ordre de la classe (seance
     * photo) et les attribue par position : photos[i] <-> effectif trie par
     * (nom, prenom) a l'index i. Le mapping n'est JAMAIS confie au client
     * (aucun apprenant_id envoye par le lot) — seul le rang dans le tableau
     * envoye compte, apparie a l'effectif recalcule cote serveur dans le
     * meme ordre que la liste de classe (cf. PhotoSeanceService).
     */
    public function store(Request $request, Classe $classe)
    {
        $this->autoriserGererDocuments($request, $classe);

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $photos = array_values($validated['photos']);

        $roster = $classe->inscriptions()
            ->where('statut', '!=', 'annulee')
            ->with('apprenant')
            ->get()
            ->sortBy([['apprenant.nom', 'asc'], ['apprenant.prenom', 'asc']])
            ->values();

        if (count($photos) !== $roster->count()) {
            throw ValidationException::withMessages([
                'photos' => "Le nombre de photos importées (".count($photos).") doit être égal au nombre d'apprenants actifs de la classe ({$roster->count()}).",
            ]);
        }

        $resultats = $roster->values()->map(function ($inscription, $index) use ($photos, $classe) {
            $apprenant = $inscription->apprenant;

            try {
                if ($apprenant->photo) {
                    Storage::disk('public')->delete($apprenant->photo);
                }

                $chemin = $photos[$index]->store("apprenants/{$classe->etablissement_id}", 'public');
                $apprenant->update(['photo' => $chemin]);

                return [
                    'apprenant_id' => $apprenant->id,
                    'nom' => "{$apprenant->prenom} {$apprenant->nom}",
                    'success' => true,
                    'message' => null,
                ];
            } catch (\Throwable $e) {
                return [
                    'apprenant_id' => $apprenant->id,
                    'nom' => "{$apprenant->prenom} {$apprenant->nom}",
                    'success' => false,
                    'message' => "Échec du téléversement.",
                ];
            }
        });

        return $this->success($resultats, 'Photos importées.');
    }

    private function autoriserGererDocuments(Request $request, Classe $classe): void
    {
        $user = $request->user();

        $autorise = $user->hasAnyRole(['super_admin', 'admin_etablissement', 'secretaire'])
            && ($user->hasRole('super_admin') || $user->etablissement_id === $classe->etablissement_id);

        abort_unless($autorise, 403);
    }
}
