<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Paiement;
use App\Models\Sequence;
use App\Models\Semestre;
use App\Models\User;

class DashboardService
{
    private const ROLES_STAFF = ['super_admin', 'admin_etablissement', 'secretaire', 'comptable', 'enseignant', 'apprenant'];

    public function __construct(private readonly RapportService $rapportService)
    {
    }

    public function pourSuperAdmin(): array
    {
        $debutMois = now()->startOfMonth();

        $parEtablissement = Etablissement::all()->map(fn (Etablissement $etablissement) => [
            'etablissement' => $etablissement->nom,
            'effectif' => Inscription::where('etablissement_id', $etablissement->id)->where('statut', '!=', 'annulee')->count(),
            'encaisse_mois' => (float) Paiement::where('etablissement_id', $etablissement->id)
                ->where('date_paiement', '>=', $debutMois)->sum('montant'),
        ]);

        return [
            'nombre_etablissements' => Etablissement::count(),
            'total_apprenants' => Inscription::where('statut', '!=', 'annulee')->count(),
            'utilisateurs_par_role' => collect(self::ROLES_STAFF)
                ->mapWithKeys(fn (string $role) => [$role => User::role($role)->count()]),
            'total_encaisse_mois' => (float) Paiement::where('date_paiement', '>=', $debutMois)->sum('montant'),
            'par_etablissement' => $parEtablissement,
        ];
    }

    public function pourAdminEtablissement(User $user): array
    {
        $etablissementId = $user->etablissement_id;

        $effectifsParClasse = Classe::where('etablissement_id', $etablissementId)
            ->withCount(['inscriptions' => fn ($query) => $query->where('statut', '!=', 'annulee')])
            ->get()
            ->map(fn (Classe $classe) => ['classe' => $classe->libelle, 'effectif' => $classe->inscriptions_count]);

        return [
            'effectifs_par_classe' => $effectifsParClasse,
            'taux_recouvrement' => $this->tauxRecouvrement($etablissementId),
            'affectations_incompletes' => $this->affectationsIncompletes($etablissementId),
            'top_impayes' => $this->rapportService->listerImpayes($etablissementId)->take(5)->values(),
        ];
    }

    public function pourComptable(User $user): array
    {
        $etablissementId = $user->etablissement_id;
        $aujourdHui = now()->toDateString();
        $debutMois = now()->startOfMonth();

        return [
            'encaisse_aujourdhui' => (float) Paiement::where('etablissement_id', $etablissementId)
                ->whereDate('date_paiement', $aujourdHui)->sum('montant'),
            'encaisse_mois' => (float) Paiement::where('etablissement_id', $etablissementId)
                ->where('date_paiement', '>=', $debutMois)->sum('montant'),
            'impayes' => $this->rapportService->listerImpayes($etablissementId),
        ];
    }

    public function pourEnseignant(User $user): array
    {
        $affectations = AffectationEnseignant::where('enseignant_id', $user->id)
            ->with(['classe.niveau', 'matiere'])
            ->get();

        $lignes = $affectations->map(function (AffectationEnseignant $affectation) {
            $info = $this->derniereSaisieInfo($affectation);

            return [
                'classe' => $affectation->classe->libelle,
                'matiere' => $affectation->matiere->nom,
                'periode' => $info['periode'],
                'soumis' => $info['soumis'],
            ];
        });

        return ['affectations' => $lignes];
    }

    private function tauxRecouvrement(int $etablissementId): float
    {
        $inscriptions = Inscription::where('etablissement_id', $etablissementId)
            ->where('statut', '!=', 'annulee')
            ->with('fraisScolarite')
            ->get();

        $attendu = $inscriptions->sum(fn (Inscription $inscription) => (float) $inscription->fraisScolarite->montant_total);

        if ($attendu <= 0) {
            return 0.0;
        }

        $encaisse = (float) Paiement::where('etablissement_id', $etablissementId)
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->sum('montant');

        return round(($encaisse / $attendu) * 100, 1);
    }

    /**
     * Affectations dont la sequence/le semestre le plus recent (par niveau+annee)
     * n'a pas encore de saisie verrouillee — meme logique de verification que
     * BulletinService/ReleveService, reutilisee ici en lecture seule.
     */
    private function affectationsIncompletes(int $etablissementId): array
    {
        $affectations = AffectationEnseignant::where('etablissement_id', $etablissementId)
            ->with(['classe.niveau', 'matiere', 'enseignant'])
            ->get();

        $incompletes = [];

        foreach ($affectations as $affectation) {
            $info = $this->derniereSaisieInfo($affectation);

            if ($info['periode'] !== null && ! $info['soumis']) {
                $incompletes[] = [
                    'classe' => $affectation->classe->libelle,
                    'matiere' => $affectation->matiere->nom,
                    'enseignant' => $affectation->enseignant->name,
                    'periode' => $info['periode'],
                ];
            }
        }

        return $incompletes;
    }

    private function derniereSaisieInfo(AffectationEnseignant $affectation): array
    {
        $niveau = $affectation->classe->niveau;

        if ($niveau->type_systeme === 'lmd') {
            $periode = Semestre::where('niveau_id', $niveau->id)
                ->where('annee_academique_id', $affectation->annee_academique_id)
                ->orderByDesc('numero')->first();

            $soumis = $periode !== null && Note::where('affectation_id', $affectation->id)
                ->where('semestre_id', $periode->id)
                ->whereNotNull('soumis_le')->exists();
        } else {
            $periode = Sequence::where('niveau_id', $niveau->id)
                ->where('annee_academique_id', $affectation->annee_academique_id)
                ->orderByDesc('numero')->first();

            $soumis = $periode !== null && Note::where('affectation_id', $affectation->id)
                ->where('sequence_id', $periode->id)
                ->whereNotNull('soumis_le')->exists();
        }

        return ['periode' => $periode?->libelle, 'soumis' => (bool) $soumis];
    }
}
