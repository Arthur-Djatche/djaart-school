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
use App\Models\ReleveDeNotes;
use App\Models\Semestre;
use App\Models\Sequence;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReleveTest extends TestCase
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

    private function makeInscription(Etablissement $etablissement, Classe $classe, AnneeAcademique $annee, string $matricule): Inscription
    {
        $fraisScolarite = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $classe->niveau_id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 15000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => $matricule,
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);

        return Inscription::create([
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

    /** @return array{0: Etablissement, 1: Classe, 2: Sequence, 3: Matiere, 4: AffectationEnseignant, 5: Inscription} */
    private function makeStructureClassique(): array
    {
        $etablissement = Etablissement::factory()->create();
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Filière', 'code' => 'F1']);
        $niveau = Niveau::create([
            'etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id,
            'libelle' => 'Niveau 1', 'ordre' => 1, 'type_systeme' => 'classique',
        ]);
        $annee = AnneeAcademique::create([
            'etablissement_id' => $etablissement->id, 'libelle' => '2025-2026',
            'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'libelle' => 'Classe A', 'effectif_max' => 30,
        ]);
        $matiere = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Mathématiques', 'coefficient' => 2,
        ]);
        $sequence = Sequence::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'numero' => 1, 'libelle' => 'Séquence 1',
        ]);
        $enseignant = $this->makeUser($etablissement, 'enseignant');
        $affectation = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiere->id, 'enseignant_id' => $enseignant->id,
            'annee_academique_id' => $annee->id,
        ]);
        $inscription = $this->makeInscription($etablissement, $classe, $annee, 'ETB-00001');

        return [$etablissement, $classe, $sequence, $matiere, $affectation, $inscription];
    }

    public function test_releve_classique_bloque_si_sequence_sans_bulletin(): void
    {
        [$etablissement, $classe] = $this->makeStructureClassique();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/releves/annuel");

        $response->assertStatus(422);
    }

    public function test_releve_classique_genere_avec_mention_correcte(): void
    {
        [$etablissement, $classe, $sequence, $matiere, $affectation, $inscription] = $this->makeStructureClassique();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        Note::create([
            'etablissement_id' => $etablissement->id, 'affectation_id' => $affectation->id,
            'apprenant_id' => $inscription->apprenant_id, 'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence', 'valeur' => 17, 'absent' => false, 'soumis_le' => now(),
        ]);
        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/releves/annuel");

        $response->assertCreated();
        $releve = ReleveDeNotes::where('inscription_id', $inscription->id)->first();
        $this->assertEquals(17, $releve->moyenne_generale);
        $this->assertSame('Excellent', $releve->mention);
        $this->assertNull($releve->semestre_id);
    }

    /** @return array{0: Etablissement, 1: Classe, 2: Semestre, 3: array{0: Matiere, 1: Matiere}, 4: array{0: AffectationEnseignant, 1: AffectationEnseignant}, 5: Inscription} */
    private function makeStructureLmd(): array
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
        $matiereAlgo = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Algorithmique', 'credits_ects' => 6, 'ponderation_cc' => 40, 'ponderation_session_normale' => 60,
        ]);
        $matiereReseaux = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Réseaux', 'credits_ects' => 4, 'ponderation_cc' => 40, 'ponderation_session_normale' => 60,
        ]);
        $semestre = Semestre::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'numero' => 1, 'libelle' => 'Semestre 1',
        ]);
        $enseignant1 = $this->makeUser($etablissement, 'enseignant');
        $enseignant2 = $this->makeUser($etablissement, 'enseignant');
        $affectationAlgo = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiereAlgo->id, 'enseignant_id' => $enseignant1->id,
            'annee_academique_id' => $annee->id,
        ]);
        $affectationReseaux = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiereReseaux->id, 'enseignant_id' => $enseignant2->id,
            'annee_academique_id' => $annee->id,
        ]);
        $inscription = $this->makeInscription($etablissement, $classe, $annee, 'ETB-00001');

        return [$etablissement, $classe, $semestre, [$matiereAlgo, $matiereReseaux], [$affectationAlgo, $affectationReseaux], $inscription];
    }

    private function soumettreNoteLmd(AffectationEnseignant $affectation, Semestre $semestre, int $apprenantId, string $type, float $valeur): void
    {
        Note::create([
            'etablissement_id' => $affectation->etablissement_id, 'affectation_id' => $affectation->id,
            'apprenant_id' => $apprenantId, 'semestre_id' => $semestre->id,
            'type_evaluation' => $type, 'valeur' => $valeur, 'absent' => false, 'soumis_le' => now(),
        ]);
    }

    public function test_releve_lmd_bloque_si_cc_ou_sn_manquant(): void
    {
        [$etablissement, $classe, $semestre, , $affectations, $inscription] = $this->makeStructureLmd();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        // Seul le CC est soumis pour Algorithmique ; Reseaux n'a rien du tout.
        $this->soumettreNoteLmd($affectations[0], $semestre, $inscription->apprenant_id, 'cc', 12);

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/semestres/{$semestre->id}/releves");

        $response->assertStatus(422);
    }

    public function test_releve_lmd_genere_avec_moyenne_ponderee_et_mention(): void
    {
        [$etablissement, $classe, $semestre, $matieres, $affectations, $inscription] = $this->makeStructureLmd();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        // Algorithmique (6 credits) : CC 12, SN 16 -> (12*40+16*60)/100 = 14.40 (validee)
        $this->soumettreNoteLmd($affectations[0], $semestre, $inscription->apprenant_id, 'cc', 12);
        $this->soumettreNoteLmd($affectations[0], $semestre, $inscription->apprenant_id, 'session_normale', 16);
        // Reseaux (4 credits) : CC 6, SN 4 -> (6*40+4*60)/100 = 4.80 (non validee)
        $this->soumettreNoteLmd($affectations[1], $semestre, $inscription->apprenant_id, 'cc', 6);
        $this->soumettreNoteLmd($affectations[1], $semestre, $inscription->apprenant_id, 'session_normale', 4);

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/semestres/{$semestre->id}/releves");

        $response->assertCreated();

        $releve = ReleveDeNotes::where('inscription_id', $inscription->id)->first();
        // (14.40*6 + 4.80*4) / 10 = 10.56
        $this->assertEquals(10.56, $releve->moyenne_generale);
        $this->assertSame('Admis', $releve->mention);
        $this->assertEquals($semestre->id, $releve->semestre_id);
    }

    public function test_secretaire_peut_generer_les_releves(): void
    {
        [$etablissement, $classe, $sequence, $matiere, $affectation, $inscription] = $this->makeStructureClassique();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        Note::create([
            'etablissement_id' => $etablissement->id, 'affectation_id' => $affectation->id,
            'apprenant_id' => $inscription->apprenant_id, 'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence', 'valeur' => 11, 'absent' => false, 'soumis_le' => now(),
        ]);
        $this->actingAs($secretaire)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $response = $this->actingAs($secretaire)->postJson("/api/classes/{$classe->id}/releves/annuel");

        $response->assertCreated();
    }

    public function test_comptable_ne_peut_pas_generer_les_releves(): void
    {
        [$etablissement, $classe] = $this->makeStructureClassique();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson("/api/classes/{$classe->id}/releves/annuel");

        $response->assertStatus(403);
    }
}
