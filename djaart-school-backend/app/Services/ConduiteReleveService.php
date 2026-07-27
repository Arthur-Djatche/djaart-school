<?php

namespace App\Services;

use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\ConduiteReleve;
use App\Models\Sequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConduiteReleveService
{
    public function chargerGrille(Classe $classe, Sequence $sequence): Collection
    {
        $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

        $conduites = ConduiteReleve::where('sequence_id', $sequence->id)
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->get()
            ->keyBy('inscription_id');

        return $inscriptions->map(function ($inscription) use ($conduites) {
            $conduite = $conduites->get($inscription->id);

            return [
                'inscription_id' => $inscription->id,
                'apprenant' => $inscription->apprenant,
                'absences' => $conduite->absences ?? 0,
                'absences_non_justifiees' => $conduite->absences_non_justifiees ?? 0,
                'retards' => $conduite->retards ?? 0,
                'mention_travail' => $conduite->mention_travail ?? null,
                'mention_conduite' => $conduite->mention_conduite ?? null,
            ];
        });
    }

    public function enregistrer(Classe $classe, Sequence $sequence, array $lignes): void
    {
        if (Bulletin::whereIn('inscription_id', $classe->inscriptions()->pluck('id'))->where('sequence_id', $sequence->id)->exists()) {
            throw ValidationException::withMessages([
                'sequence_id' => 'Cette séquence est déjà clôturée : la conduite ne peut plus être modifiée.',
            ]);
        }

        DB::transaction(function () use ($classe, $sequence, $lignes) {
            foreach ($lignes as $ligne) {
                ConduiteReleve::updateOrCreate(
                    ['inscription_id' => $ligne['inscription_id'], 'sequence_id' => $sequence->id],
                    [
                        'etablissement_id' => $classe->etablissement_id,
                        'absences' => $ligne['absences'] ?? 0,
                        'absences_non_justifiees' => $ligne['absences_non_justifiees'] ?? 0,
                        'retards' => $ligne['retards'] ?? 0,
                        'mention_travail' => $ligne['mention_travail'] ?? null,
                        'mention_conduite' => $ligne['mention_conduite'] ?? null,
                    ],
                );
            }
        });
    }
}
