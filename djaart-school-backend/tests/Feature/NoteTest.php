<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\Semestre;
use App\Models\Sequence;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(Etablissement $etablissement, string $role): User
    {
        $user = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{0: Etablissement, 1: AffectationEnseignant, 2: Sequence, 3: Apprenant, 4: Apprenant} */
    private function makeStructureClassique(): array
    {
        $etablissement = Etablissement::factory()->create();
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Filière', 'code' => 'F1']);
        $niveau = Niveau::create([
            'etablissement_id' => $etablissement->id,
            'filiere_id' => $filiere->id,
            'libelle' => 'Niveau 1',
            'ordre' => 1,
            'type_systeme' => 'classique',
        ]);
        $annee = AnneeAcademique::create([
            'etablissement_id' => $etablissement->id,
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'libelle' => 'Classe A',
            'effectif_max' => 30,
        ]);
        $matiere = Matiere::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'nom' => 'Mathématiques',
            'coefficient' => 2,
        ]);
        $enseignant = $this->makeUser($etablissement, 'enseignant');
        $affectation = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $enseignant->id,
            'annee_academique_id' => $annee->id,
        ]);
        $sequence = Sequence::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Séquence 1',
        ]);
        $fraisScolarite = FraisScolarite::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'frais_inscription' => 15000,
            'nombre_tranches' => 1,
        ]);

        $apprenant1 = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        $apprenant2 = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00002',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);
        foreach ([$apprenant1, $apprenant2] as $apprenant) {
            Inscription::create([
                'etablissement_id' => $etablissement->id,
                'apprenant_id' => $apprenant->id,
                'classe_id' => $classe->id,
                'annee_academique_id' => $annee->id,
                'frais_scolarite_id' => $fraisScolarite->id,
                'statut' => 'en_cours',
                'type_inscription' => 'nouvelle',
                'date_inscription' => now()->toDateString(),
            ]);
        }

        return [$etablissement, $affectation, $sequence, $apprenant1, $apprenant2];
    }

    private function notesPayload(Sequence $sequence, Apprenant $a1, Apprenant $a2): array
    {
        return [
            'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence',
            'notes' => [
                ['apprenant_id' => $a1->id, 'valeur' => 15.5],
                ['apprenant_id' => $a2->id, 'absent' => true],
            ],
        ];
    }

    public function test_enseignant_titulaire_soumet_et_verrouille_les_notes(): void
    {
        [$etablissement, $affectation, $sequence, $a1, $a2] = $this->makeStructureClassique();
        $enseignant = $affectation->enseignant;

        $response = $this->actingAs($enseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            $this->notesPayload($sequence, $a1, $a2),
        );

        $response->assertCreated();

        $noteA1 = Note::where('affectation_id', $affectation->id)->where('apprenant_id', $a1->id)->first();
        $noteA2 = Note::where('affectation_id', $affectation->id)->where('apprenant_id', $a2->id)->first();

        $this->assertEquals(15.5, $noteA1->valeur);
        $this->assertNotNull($noteA1->soumis_le);
        $this->assertTrue((bool) $noteA2->absent);
        $this->assertNull($noteA2->valeur);
    }

    public function test_resoumission_bloquee_tant_que_non_deverrouillee(): void
    {
        [$etablissement, $affectation, $sequence, $a1, $a2] = $this->makeStructureClassique();
        $enseignant = $affectation->enseignant;

        $this->actingAs($enseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            $this->notesPayload($sequence, $a1, $a2),
        )->assertCreated();

        $response = $this->actingAs($enseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            $this->notesPayload($sequence, $a1, $a2),
        );

        $response->assertStatus(422);
    }

    public function test_enseignant_non_titulaire_ne_peut_pas_soumettre(): void
    {
        [$etablissement, $affectation, $sequence, $a1, $a2] = $this->makeStructureClassique();
        $autreEnseignant = $this->makeUser($etablissement, 'enseignant');

        $response = $this->actingAs($autreEnseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            $this->notesPayload($sequence, $a1, $a2),
        );

        $response->assertStatus(403);
    }

    public function test_admin_deverrouille_puis_resoumission_acceptee(): void
    {
        [$etablissement, $affectation, $sequence, $a1, $a2] = $this->makeStructureClassique();
        $enseignant = $affectation->enseignant;
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $this->actingAs($enseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            $this->notesPayload($sequence, $a1, $a2),
        )->assertCreated();

        $this->actingAs($admin)->postJson("/api/affectations/{$affectation->id}/notes/deverrouiller", [
            'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence',
        ])->assertOk();

        $response = $this->actingAs($enseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            [
                'sequence_id' => $sequence->id,
                'type_evaluation' => 'sequence',
                'notes' => [
                    ['apprenant_id' => $a1->id, 'valeur' => 18],
                    ['apprenant_id' => $a2->id, 'valeur' => 10],
                ],
            ],
        );

        $response->assertCreated();
        $noteA1 = Note::where('affectation_id', $affectation->id)->where('apprenant_id', $a1->id)->first();
        $this->assertEquals(18, $noteA1->valeur);
    }

    public function test_note_valeur_superieure_a_20_est_rejetee(): void
    {
        [$etablissement, $affectation, $sequence, $a1, $a2] = $this->makeStructureClassique();
        $enseignant = $affectation->enseignant;

        $response = $this->actingAs($enseignant)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            [
                'sequence_id' => $sequence->id,
                'type_evaluation' => 'sequence',
                'notes' => [
                    ['apprenant_id' => $a1->id, 'valeur' => 25],
                    ['apprenant_id' => $a2->id, 'valeur' => 10],
                ],
            ],
        );

        $response->assertStatus(422);
    }

    public function test_cc_et_session_normale_sont_des_verrous_independants(): void
    {
        $etablissement = Etablissement::factory()->create();
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Informatique', 'code' => 'INFO']);
        $niveau = Niveau::create([
            'etablissement_id' => $etablissement->id,
            'filiere_id' => $filiere->id,
            'libelle' => 'Licence 1',
            'ordre' => 1,
            'type_systeme' => 'lmd',
        ]);
        $annee = AnneeAcademique::create([
            'etablissement_id' => $etablissement->id,
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'libelle' => 'L1 A',
            'effectif_max' => 30,
        ]);
        $matiere = Matiere::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'nom' => 'Algorithmique',
            'credits_ects' => 6,
        ]);
        $enseignant = $this->makeUser($etablissement, 'enseignant');
        $affectation = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $enseignant->id,
            'annee_academique_id' => $annee->id,
        ]);
        $semestre = Semestre::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Semestre 1',
        ]);
        $fraisScolarite = FraisScolarite::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 300000,
            'frais_inscription' => 30000,
            'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Diallo', 'prenom' => 'Kadiatou', 'date_naissance' => '2005-01-01', 'sexe' => 'F',
        ]);
        Inscription::create([
            'etablissement_id' => $etablissement->id,
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $fraisScolarite->id,
            'statut' => 'en_cours',
            'type_inscription' => 'nouvelle',
            'date_inscription' => now()->toDateString(),
        ]);

        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectation->id}/notes", [
            'semestre_id' => $semestre->id,
            'type_evaluation' => 'cc',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 14]],
        ])->assertCreated();

        // Meme affectation + semestre, mais type d'evaluation different (SN) : doit reussir
        // independamment du verrou pose sur le CC.
        $response = $this->actingAs($enseignant)->postJson("/api/affectations/{$affectation->id}/notes", [
            'semestre_id' => $semestre->id,
            'type_evaluation' => 'session_normale',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 12]],
        ]);

        $response->assertCreated();

        $noteCc = Note::where('affectation_id', $affectation->id)->where('type_evaluation', 'cc')->first();
        $noteSn = Note::where('affectation_id', $affectation->id)->where('type_evaluation', 'session_normale')->first();
        $this->assertEquals(14, $noteCc->valeur);
        $this->assertEquals(12, $noteSn->valeur);
    }

    /** @return array{0: Etablissement, 1: AffectationEnseignant, 2: array{0: Semestre, 1: Semestre}, 3: Apprenant} */
    private function makeStructureLmdDeuxSemestres(): array
    {
        $etablissement = Etablissement::factory()->create();
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Informatique', 'code' => 'INFO']);
        $niveau = Niveau::create([
            'etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id,
            'libelle' => 'Licence 1', 'ordre' => 1, 'type_systeme' => 'lmd',
        ]);
        $annee = AnneeAcademique::create([
            'etablissement_id' => $etablissement->id, 'libelle' => '2025-2026',
            'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'libelle' => 'L1 A', 'effectif_max' => 30,
        ]);
        $semestre1 = Semestre::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'numero' => 1, 'libelle' => 'Semestre 1',
        ]);
        $semestre2 = Semestre::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'numero' => 2, 'libelle' => 'Semestre 2',
        ]);
        $matiereS1 = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'semestre_id' => $semestre1->id,
            'nom' => 'Algorithmique', 'credits_ects' => 6, 'ponderation_cc' => 40, 'ponderation_session_normale' => 60,
        ]);
        $matiereS2 = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'semestre_id' => $semestre2->id,
            'nom' => 'Bases de données', 'credits_ects' => 6, 'ponderation_cc' => 40, 'ponderation_session_normale' => 60,
        ]);
        $enseignant = $this->makeUser($etablissement, 'enseignant');
        $affectationS1 = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiereS1->id, 'enseignant_id' => $enseignant->id, 'annee_academique_id' => $annee->id,
        ]);
        $affectationS2 = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiereS2->id, 'enseignant_id' => $enseignant->id, 'annee_academique_id' => $annee->id,
        ]);
        $fraisScolarite = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 300000,
            'frais_inscription' => 30000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Diallo', 'prenom' => 'Kadiatou', 'date_naissance' => '2005-01-01', 'sexe' => 'F',
        ]);
        Inscription::create([
            'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $fraisScolarite->id, 'statut' => 'en_cours',
            'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
        ]);

        return [$etablissement, $affectationS1, [$semestre1, $semestre2], $apprenant, $affectationS2];
    }

    public function test_semestre_2_bloque_tant_que_le_semestre_1_nest_pas_complet(): void
    {
        [, $affectationS1, $semestres, $apprenant, $affectationS2] = $this->makeStructureLmdDeuxSemestres();
        $enseignant = $affectationS1->enseignant;

        // Semestre 1 incomplet (rien soumis) : la saisie du Semestre 2 doit etre bloquee.
        $response = $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS2->id}/notes", [
            'semestre_id' => $semestres[1]->id,
            'type_evaluation' => 'cc',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 10]],
        ]);

        $response->assertStatus(422);
    }

    public function test_semestre_2_accepte_une_fois_le_semestre_1_complet(): void
    {
        [, $affectationS1, $semestres, $apprenant, $affectationS2] = $this->makeStructureLmdDeuxSemestres();
        $enseignant = $affectationS1->enseignant;

        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'cc',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 14]],
        ])->assertCreated();
        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'session_normale',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 15]],
        ])->assertCreated();

        $response = $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS2->id}/notes", [
            'semestre_id' => $semestres[1]->id,
            'type_evaluation' => 'cc',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 10]],
        ]);

        $response->assertCreated();
    }

    public function test_rattrapage_rejete_si_lec_est_deja_validee(): void
    {
        [, $affectationS1, $semestres, $apprenant] = $this->makeStructureLmdDeuxSemestres();
        $enseignant = $affectationS1->enseignant;

        // CC 14, SN 16 -> (14*40+16*60)/100 = 15.20, validee.
        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'cc',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 14]],
        ])->assertCreated();
        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'session_normale',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 16]],
        ])->assertCreated();

        $response = $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'rattrapage',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 18]],
        ]);

        $response->assertStatus(422);
    }

    public function test_rattrapage_accepte_si_lec_nest_pas_validee(): void
    {
        [, $affectationS1, $semestres, $apprenant] = $this->makeStructureLmdDeuxSemestres();
        $enseignant = $affectationS1->enseignant;

        // CC 6, SN 5 -> (6*40+5*60)/100 = 5.40, non validee.
        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'cc',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 6]],
        ])->assertCreated();
        $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'session_normale',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 5]],
        ])->assertCreated();

        $response = $this->actingAs($enseignant)->postJson("/api/affectations/{$affectationS1->id}/notes", [
            'semestre_id' => $semestres[0]->id, 'type_evaluation' => 'rattrapage',
            'notes' => [['apprenant_id' => $apprenant->id, 'valeur' => 14]],
        ]);

        $response->assertCreated();
        $noteRattrapage = Note::where('affectation_id', $affectationS1->id)->where('type_evaluation', 'rattrapage')->first();
        $this->assertEquals(14, $noteRattrapage->valeur);
    }

    public function test_secretaire_ne_peut_pas_acceder_aux_notes(): void
    {
        [$etablissement, $affectation, $sequence, $a1, $a2] = $this->makeStructureClassique();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson(
            "/api/affectations/{$affectation->id}/notes",
            $this->notesPayload($sequence, $a1, $a2),
        );

        $response->assertStatus(403);
    }
}
