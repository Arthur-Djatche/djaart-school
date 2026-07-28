<?php

namespace App\Services;

use App\Models\AffectationEnseignant;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Note;
use App\Models\ReleveDeNotes;
use App\Models\Semestre;
use App\Models\Sequence;
use App\Services\Concerns\EmbedsEtablissementBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReleveService
{
    use EmbedsEtablissementBranding;

    private const SEUIL_ADMISSION = 10.0;

    public function genererAnnuelClassique(Classe $classe): Collection
    {
        return DB::transaction(function () use ($classe) {
            $sequences = Sequence::where('niveau_id', $classe->niveau_id)
                ->where('annee_academique_id', $classe->annee_academique_id)
                ->orderBy('numero')
                ->get();

            if ($sequences->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => "Aucune séquence n'est configurée pour ce niveau et cette année académique.",
                ]);
            }

            $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

            if ($inscriptions->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Aucun apprenant actif dans cette classe.',
                ]);
            }

            if (ReleveDeNotes::whereIn('inscription_id', $inscriptions->pluck('id'))->whereNull('semestre_id')->exists()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Des relevés annuels existent déjà pour cette classe.',
                ]);
            }

            $manquants = [];
            $bulletinsParInscription = [];

            foreach ($inscriptions as $inscription) {
                $bulletins = Bulletin::where('inscription_id', $inscription->id)
                    ->whereIn('sequence_id', $sequences->pluck('id'))
                    ->get()
                    ->keyBy('sequence_id');

                if ($bulletins->count() < $sequences->count()) {
                    $manquants[] = "{$inscription->apprenant->prenom} {$inscription->apprenant->nom}";

                    continue;
                }

                $bulletinsParInscription[$inscription->id] = $bulletins;
            }

            if ($manquants !== []) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Toutes les séquences ne sont pas encore clôturées pour : '.implode(', ', $manquants).'.',
                ]);
            }

            $releves = collect();

            foreach ($inscriptions as $inscription) {
                $bulletins = $bulletinsParInscription[$inscription->id];
                $moyenneGenerale = round($bulletins->avg('moyenne_generale'), 2);
                $mention = $this->mentionClassique($moyenneGenerale);

                $releve = ReleveDeNotes::create([
                    'etablissement_id' => $classe->etablissement_id,
                    'inscription_id' => $inscription->id,
                    'semestre_id' => null,
                    'moyenne_generale' => $moyenneGenerale,
                    'mention' => $mention,
                    'fichier_pdf' => '',
                ]);

                $lignes = $sequences->map(fn ($sequence) => [
                    'sequence' => $sequence->libelle,
                    'moyenne' => $bulletins->get($sequence->id)->moyenne_generale,
                ])->all();

                $pdf = Pdf::loadView('pdf.releve', [
                    'estLmd' => false,
                    'etablissement' => $classe->etablissement,
                    'apprenant' => $inscription->apprenant,
                    'classe' => $classe,
                    'anneeAcademique' => $inscription->anneeAcademique,
                    'releve' => $releve,
                    'lignes' => $lignes,
                    'logoDataUri' => $this->logoDataUri($classe->etablissement),
                    'signatureDataUri' => $this->signatureDataUri($classe->etablissement),
                    'enteteDataUri' => $this->enteteDataUri($classe->etablissement),
                ]);

                $chemin = "releves/{$classe->etablissement_id}/annuel/{$inscription->id}.pdf";
                Storage::disk('local')->put($chemin, $pdf->output());
                $releve->update(['fichier_pdf' => $chemin]);

                $releves->push($releve);
            }

            return $releves;
        });
    }

    /**
     * Relevé LMD annuel unique couvrant les 2 semestres d'un niveau+annee sur
     * un seul document (remplace l'ancienne generation semestre par semestre) :
     * groupe par semestre -> UE (fondamentale/professionnelle/transversale,
     * simple etiquette d'affichage) -> EC, pondere par credits ECTS a chaque
     * niveau d'agregation (EC -> UE -> semestre -> annee).
     */
    public function genererAnnuelLmd(Classe $classe): Collection
    {
        return DB::transaction(function () use ($classe) {
            $semestres = Semestre::where('niveau_id', $classe->niveau_id)
                ->where('annee_academique_id', $classe->annee_academique_id)
                ->orderBy('numero')
                ->get();

            if ($semestres->count() < 2) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Les 2 semestres de ce niveau doivent être configurés avant de générer le relevé annuel.',
                ]);
            }

            $inscriptions = $classe->inscriptions()->where('statut', '!=', 'annulee')->with('apprenant')->get();

            if ($inscriptions->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Aucun apprenant actif dans cette classe.',
                ]);
            }

            if (ReleveDeNotes::whereIn('inscription_id', $inscriptions->pluck('id'))->whereNull('semestre_id')->exists()) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Des relevés annuels existent déjà pour cette classe.',
                ]);
            }

            $affectations = AffectationEnseignant::where('classe_id', $classe->id)
                ->with('matiere.uniteEnseignement')
                ->get();

            if ($affectations->isEmpty()) {
                throw ValidationException::withMessages([
                    'classe_id' => "Aucun EC n'est affecté à cette classe.",
                ]);
            }

            $manquants = [];
            $donneesParSemestre = [];

            foreach ($semestres as $semestre) {
                $affectationsSemestre = $affectations->filter(
                    fn ($affectation) => $affectation->matiere->semestre_id === $semestre->id,
                );

                if ($affectationsSemestre->isEmpty()) {
                    $manquants[] = "aucun EC affecté pour {$semestre->libelle}";

                    continue;
                }

                $donnees = [];

                foreach ($affectationsSemestre as $affectation) {
                    $notesCc = Note::where('affectation_id', $affectation->id)
                        ->where('semestre_id', $semestre->id)
                        ->where('type_evaluation', 'cc')
                        ->get()->keyBy('apprenant_id');
                    $notesSn = Note::where('affectation_id', $affectation->id)
                        ->where('semestre_id', $semestre->id)
                        ->where('type_evaluation', 'session_normale')
                        ->get()->keyBy('apprenant_id');
                    $notesRattrapage = Note::where('affectation_id', $affectation->id)
                        ->where('semestre_id', $semestre->id)
                        ->where('type_evaluation', 'rattrapage')
                        ->get()->keyBy('apprenant_id');

                    $ccVerrouille = $notesCc->isNotEmpty() && $notesCc->first()->soumis_le !== null;
                    $snVerrouille = $notesSn->isNotEmpty() && $notesSn->first()->soumis_le !== null;
                    $couvertureComplete = $inscriptions->every(
                        fn ($inscription) => $notesCc->has($inscription->apprenant_id) && $notesSn->has($inscription->apprenant_id),
                    );
                    $rattrapageVerrouille = $notesRattrapage->isNotEmpty() && $notesRattrapage->first()->soumis_le !== null;

                    if (! $ccVerrouille || ! $snVerrouille || ! $couvertureComplete) {
                        $manquants[] = "{$semestre->libelle} : {$affectation->matiere->nom}";

                        continue;
                    }

                    $donnees[] = [
                        'matiere' => $affectation->matiere,
                        'cc' => $notesCc,
                        'sn' => $notesSn,
                        'rattrapage' => $rattrapageVerrouille ? $notesRattrapage : collect(),
                    ];
                }

                $donneesParSemestre[$semestre->id] = $donnees;
            }

            if ($manquants !== []) {
                throw ValidationException::withMessages([
                    'classe_id' => 'Notes CC/Session Normale manquantes ou non verrouillées pour : '.implode(', ', $manquants).'.',
                ]);
            }

            $releves = collect();

            foreach ($inscriptions as $inscription) {
                $semestresData = [];
                $sommeMoyennesSemestres = 0.0;
                $sommeCreditsSemestres = 0.0;
                $creditsAnnuelsAcquis = 0.0;
                $creditsAnnuelsTotal = 0.0;

                foreach ($semestres as $semestre) {
                    $donneesSemestre = $donneesParSemestre[$semestre->id];
                    $unitesData = [];
                    $sommeMoyennesUe = 0.0;
                    $sommeCreditsUe = 0.0;

                    $parUe = collect($donneesSemestre)->groupBy(fn ($d) => $d['matiere']->unite_enseignement_id);

                    foreach ($parUe as $uniteEnseignementId => $donneesUe) {
                        $uniteEnseignement = $donneesUe->first()['matiere']->uniteEnseignement;
                        $matieresData = [];
                        $sommeMoyennesEc = 0.0;
                        $sommeCreditsEc = 0.0;

                        foreach ($donneesUe as $donnee) {
                            $matiere = $donnee['matiere'];
                            $cc = (float) $donnee['cc']->get($inscription->apprenant_id)->valeur;
                            $sn = (float) $donnee['sn']->get($inscription->apprenant_id)->valeur;
                            $moyenneBase = round(($cc * $matiere->ponderation_cc + $sn * $matiere->ponderation_session_normale) / 100, 2);

                            $moyenneFinale = $moyenneBase;
                            $session = 'Session Normale';
                            $rattrapageNote = $donnee['rattrapage']->get($inscription->apprenant_id);

                            if ($moyenneBase < self::SEUIL_ADMISSION && $rattrapageNote && $rattrapageNote->valeur !== null) {
                                $moyenneFinale = round(($cc * $matiere->ponderation_cc + (float) $rattrapageNote->valeur * $matiere->ponderation_session_normale) / 100, 2);
                                $session = 'Rattrapage';
                            }

                            $credits = (float) ($matiere->credits_ects ?? 1);
                            $sommeMoyennesEc += $moyenneFinale * $credits;
                            $sommeCreditsEc += $credits;

                            $matieresData[] = [
                                'code' => $matiere->code,
                                'nom' => $matiere->nom,
                                'credits_ects' => $matiere->credits_ects,
                                'note' => $moyenneFinale,
                                'mention_lettre' => $this->mentionLettre($moyenneFinale),
                                'session' => $session,
                            ];
                        }

                        $moyenneUe = $sommeCreditsEc > 0 ? round($sommeMoyennesEc / $sommeCreditsEc, 2) : 0.0;
                        $valideeUe = $moyenneUe >= self::SEUIL_ADMISSION;
                        $creditsUe = $sommeCreditsEc;

                        $sommeMoyennesUe += $moyenneUe * $creditsUe;
                        $sommeCreditsUe += $creditsUe;

                        $unitesData[] = [
                            'code' => $uniteEnseignement->code,
                            'nom' => $uniteEnseignement->nom,
                            'type' => $uniteEnseignement->type,
                            'moyenne' => $moyenneUe,
                            'credits' => $creditsUe,
                            'credits_acquis' => $valideeUe ? $creditsUe : 0,
                            'matieres' => $matieresData,
                        ];
                    }

                    $moyenneSemestre = $sommeCreditsUe > 0 ? round($sommeMoyennesUe / $sommeCreditsUe, 2) : 0.0;
                    $creditsSemestreAcquis = array_sum(array_column($unitesData, 'credits_acquis'));

                    $semestresData[] = [
                        'libelle' => $semestre->libelle,
                        'unites' => $unitesData,
                        'moyenne' => $moyenneSemestre,
                        'credits_acquis' => $creditsSemestreAcquis,
                        'credits_total' => $sommeCreditsUe,
                    ];

                    $sommeMoyennesSemestres += $moyenneSemestre * $sommeCreditsUe;
                    $sommeCreditsSemestres += $sommeCreditsUe;
                    $creditsAnnuelsAcquis += $creditsSemestreAcquis;
                    $creditsAnnuelsTotal += $sommeCreditsUe;
                }

                $moyenneAnnuelle = $sommeCreditsSemestres > 0 ? round($sommeMoyennesSemestres / $sommeCreditsSemestres, 2) : 0.0;
                $mention = $moyenneAnnuelle >= self::SEUIL_ADMISSION ? 'Admis' : 'Ajourné';

                $releve = ReleveDeNotes::create([
                    'etablissement_id' => $classe->etablissement_id,
                    'inscription_id' => $inscription->id,
                    'semestre_id' => null,
                    'moyenne_generale' => $moyenneAnnuelle,
                    'mention' => $mention,
                    'fichier_pdf' => '',
                ]);

                $chefDepartement = $classe->niveau->filiere->departement?->chefDepartement;

                $pdf = Pdf::loadView('pdf.releve', [
                    'estLmd' => true,
                    'etablissement' => $classe->etablissement,
                    'apprenant' => $inscription->apprenant,
                    'classe' => $classe,
                    'anneeAcademique' => $inscription->anneeAcademique,
                    'releve' => $releve,
                    'semestresData' => $semestresData,
                    'creditsAnnuelsAcquis' => $creditsAnnuelsAcquis,
                    'creditsAnnuelsTotal' => $creditsAnnuelsTotal,
                    'chefDepartementNom' => $chefDepartement?->name,
                    'logoDataUri' => $this->logoDataUri($classe->etablissement),
                    'signatureDataUri' => $this->signatureDataUri($classe->etablissement),
                    'enteteDataUri' => $this->enteteDataUri($classe->etablissement),
                ]);

                $chemin = "releves/{$classe->etablissement_id}/annuel/{$inscription->id}.pdf";
                Storage::disk('local')->put($chemin, $pdf->output());
                $releve->update(['fichier_pdf' => $chemin]);

                $releves->push($releve);
            }

            return $releves;
        });
    }

    private function mentionClassique(float $moyenne): string
    {
        return match (true) {
            $moyenne >= 16 => 'Excellent',
            $moyenne >= 14 => 'Bien',
            $moyenne >= 12 => 'Assez Bien',
            $moyenne >= self::SEUIL_ADMISSION => 'Passable',
            default => 'Insuffisant',
        };
    }

    /**
     * Mention lettree par EC (relevé LMD) — memes seuils que le bareme
     * francais deja en place sur le bulletin secondaire
     * (BulletinService::appreciation), simplement relabellises en lettres.
     */
    private function mentionLettre(float $valeur): string
    {
        return match (true) {
            $valeur < 5 => 'F',
            $valeur < 8 => 'E',
            $valeur < 9.5 => 'D',
            $valeur < self::SEUIL_ADMISSION => 'D+',
            $valeur < 12 => 'C',
            $valeur < 14 => 'B',
            $valeur < 16 => 'B+',
            default => 'A',
        };
    }
}
