<?php

namespace App\Services;

use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\ReleveDeNotes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RapportService
{
    private const MENTIONS_CLASSIQUE = ['Excellent', 'Bien', 'Assez Bien', 'Passable', 'Insuffisant'];
    private const MENTIONS_LMD = ['Admis', 'Ajourné'];

    public function __construct(private readonly EcheancierService $echeancierService)
    {
    }

    /**
     * Tranches en retard (echeance depassee, solde encore positif), agregees
     * par apprenant plutot que calculees une inscription a la fois — le detail
     * par tranche est desormais derive via EcheancierService (cascade sur le
     * total paye, cf. correctif encaissement flexible), plus calcule a partir
     * des paiements groupes par tranche_id (devenu non fiable, tranche_id est
     * NULL sur les nouveaux paiements).
     */
    public function listerImpayes(?int $etablissementId): Collection
    {
        $inscriptions = Inscription::query()
            ->where('statut', '!=', 'annulee')
            ->when($etablissementId, fn ($query) => $query->where('etablissement_id', $etablissementId))
            ->with(['apprenant', 'classe', 'fraisScolarite.tranches', 'paiements'])
            ->get();

        $lignes = collect();

        foreach ($inscriptions as $inscription) {
            $tranches = $this->echeancierService->calculerTranches($inscription);

            foreach ($tranches as $tranche) {
                $dateEcheance = Carbon::parse($tranche['date_echeance']);

                // En retard = echeance depassee ET solde encore positif, que la
                // tranche soit "partielle" ou "en_attente" (un paiement partiel
                // ne l'exempte pas d'etre en retard sur le reste).
                if ($tranche['solde'] > 0 && $dateEcheance->isPast()) {
                    $lignes->push([
                        'apprenant' => "{$inscription->apprenant->nom} {$inscription->apprenant->prenom}",
                        'matricule' => $inscription->apprenant->matricule,
                        'classe' => $inscription->classe->libelle,
                        'tranche_numero' => $tranche['numero'],
                        'solde' => $tranche['solde'],
                        'jours_retard' => (int) $dateEcheance->diffInDays(now()),
                    ]);
                }
            }
        }

        return $lignes->sortByDesc('jours_retard')->values();
    }

    /**
     * Repartition des mentions deja attribuees (ReleveDeNotes) : c'est ce
     * document qui porte la mention (classique : Excellent..Insuffisant,
     * LMD : Admis/Ajourne) — les Bulletins par sequence n'ont qu'une
     * moyenne et un rang, pas de mention.
     */
    public function statistiquesReussite(?int $etablissementId): array
    {
        $releves = ReleveDeNotes::query()
            ->when($etablissementId, fn ($query) => $query->where('etablissement_id', $etablissementId))
            ->get();

        $repartition = $releves->groupBy('mention')->map->count();

        $ordre = array_merge(self::MENTIONS_CLASSIQUE, self::MENTIONS_LMD);

        return collect($ordre)
            ->mapWithKeys(fn ($mention) => [$mention => $repartition->get($mention, 0)])
            ->filter(fn ($effectif, $mention) => $releves->contains('mention', $mention))
            ->all();
    }

    public function genererImpayesPdf(?Etablissement $etablissement): string
    {
        $lignes = $this->listerImpayes($etablissement?->id);

        return Pdf::loadView('pdf.rapport-impayes', [
            'etablissement' => $etablissement,
            'lignes' => $lignes,
        ])->output();
    }

    public function genererStatistiquesReussitePdf(?Etablissement $etablissement): string
    {
        $repartition = $this->statistiquesReussite($etablissement?->id);
        $total = array_sum($repartition);

        return Pdf::loadView('pdf.rapport-statistiques-reussite', [
            'etablissement' => $etablissement,
            'repartition' => $repartition,
            'total' => $total,
        ])->output();
    }
}
