<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\ConduiteReleve;
use App\Models\Note;
use App\Models\Sequence;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BulletinTest extends TestCase
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

    /**
     * @return array{
     *     0: Etablissement, 1: Classe, 2: Sequence,
     *     3: array{0: Matiere, 1: Matiere}, 4: array{0: Apprenant, 1: Apprenant},
     *     5: array{0: Inscription, 1: Inscription}
     * }
     */
    private function makeStructure(): array
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
        $matiereMaths = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Mathématiques', 'coefficient' => 3,
        ]);
        $matiereFrancais = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Français', 'coefficient' => 2,
        ]);
        $sequence = Sequence::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Séquence 1',
        ]);

        $enseignantMaths = $this->makeUser($etablissement, 'enseignant');
        $enseignantFrancais = $this->makeUser($etablissement, 'enseignant');
        $affectationMaths = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiereMaths->id, 'enseignant_id' => $enseignantMaths->id,
            'annee_academique_id' => $annee->id,
        ]);
        $affectationFrancais = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiereFrancais->id, 'enseignant_id' => $enseignantFrancais->id,
            'annee_academique_id' => $annee->id,
        ]);

        $fraisScolarite = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 15000, 'nombre_tranches' => 1,
        ]);

        $apprenant1 = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        $apprenant2 = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00002',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);

        $inscriptions = [];
        foreach ([$apprenant1, $apprenant2] as $apprenant) {
            $inscriptions[] = Inscription::create([
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

        return [
            $etablissement, $classe, $sequence,
            [$matiereMaths, $matiereFrancais],
            [$apprenant1, $apprenant2],
            $inscriptions,
            [$affectationMaths, $affectationFrancais],
        ];
    }

    private function soumettreNote(AffectationEnseignant $affectation, Sequence $sequence, Apprenant $apprenant, float $valeur): void
    {
        Note::create([
            'etablissement_id' => $affectation->etablissement_id,
            'affectation_id' => $affectation->id,
            'apprenant_id' => $apprenant->id,
            'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence',
            'valeur' => $valeur,
            'absent' => false,
            'soumis_le' => now(),
        ]);
    }

    public function test_cloture_bloquee_si_notes_manquantes(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, , $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        // Seule la matiere Maths a des notes soumises ; Francais est incomplet.
        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 15);
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer");

        $response->assertStatus(422);
        $this->assertStringContainsString('Français', $response->json('message') ?? implode(',', $response->json('errors.sequence_id') ?? []));
    }

    public function test_cloture_genere_bulletins_avec_moyenne_et_rang_corrects(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        // Aïcha : Maths 18 (coef 3), Français 12 (coef 2) -> (18*3+12*2)/5 = 15.60
        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 12);
        // Moussa : Maths 10 (coef 3), Français 8 (coef 2) -> (10*3+8*2)/5 = 9.20
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer");

        $response->assertCreated();
        $this->assertCount(2, $response->json('data'));

        $bulletinAicha = Bulletin::where('inscription_id', $inscriptions[0]->id)->first();
        $bulletinMoussa = Bulletin::where('inscription_id', $inscriptions[1]->id)->first();

        $this->assertEquals(15.6, $bulletinAicha->moyenne_generale);
        $this->assertEquals(1, $bulletinAicha->rang);
        $this->assertEquals(9.2, $bulletinMoussa->moyenne_generale);
        $this->assertEquals(2, $bulletinMoussa->rang);
    }

    public function test_recloture_est_bloquee(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, , $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 12);
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer");

        $response->assertStatus(422);
        $this->assertSame(2, Bulletin::count());
    }

    public function test_note_absent_est_comptee_comme_zero(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        Note::create([
            'etablissement_id' => $etablissement->id, 'affectation_id' => $affectations[0]->id,
            'apprenant_id' => $apprenants[0]->id, 'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence', 'valeur' => null, 'absent' => true, 'soumis_le' => now(),
        ]);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 10);
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 10);

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        // Aïcha : Maths absent (0, coef 3) + Français 10 (coef 2) -> (0*3+10*2)/5 = 4.00
        $bulletinAicha = Bulletin::where('inscription_id', $inscriptions[0]->id)->first();
        $this->assertEquals(4.0, $bulletinAicha->moyenne_generale);
    }

    public function test_cloture_calcule_les_sous_totaux_par_groupe_et_les_statistiques_de_classe(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');
        $matieres[0]->update(['groupe' => 'Groupe I']);
        $matieres[1]->update(['groupe' => 'Groupe II']);

        // Aïcha : Maths 18 (coef 3), Français 12 (coef 2) -> moyenne 15.60
        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 12);
        // Moussa : Maths 10 (coef 3), Français 8 (coef 2) -> moyenne 9.20
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $bulletinAicha = Bulletin::where('inscription_id', $inscriptions[0]->id)->first();

        $groupes = collect($bulletinAicha->details_groupes)->keyBy('libelle');
        $this->assertEquals(3.0, $groupes['Groupe I']['total_coefficient']);
        $this->assertEquals(18.0, $groupes['Groupe I']['moyenne']);
        $this->assertEquals(12.0, $groupes['Groupe II']['moyenne']);

        // Classe : moyennes 15.60 et 9.20 -> moyenne classe 12.40, taux de reussite 50%, max 15.60, min 9.20.
        $this->assertEquals(12.4, $bulletinAicha->moyenne_classe);
        $this->assertEquals(50.0, $bulletinAicha->taux_reussite);
        $this->assertEquals(15.6, $bulletinAicha->moyenne_max);
        $this->assertEquals(9.2, $bulletinAicha->moyenne_min);
    }

    public function test_cloture_fige_la_conduite_deja_saisie_et_met_des_valeurs_par_defaut_sinon(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        ConduiteReleve::create([
            'etablissement_id' => $etablissement->id,
            'inscription_id' => $inscriptions[0]->id,
            'sequence_id' => $sequence->id,
            'absences' => 4,
            'absences_non_justifiees' => 2,
            'retards' => 1,
            'retards_non_justifies' => 1,
            'mention_travail' => 'encouragements',
            'mention_conduite' => 'encouragements',
        ]);

        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 12);
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $bulletinAicha = Bulletin::where('inscription_id', $inscriptions[0]->id)->first();
        $bulletinMoussa = Bulletin::where('inscription_id', $inscriptions[1]->id)->first();

        $this->assertSame(4, $bulletinAicha->absences);
        $this->assertSame(1, $bulletinAicha->retards_non_justifies);
        $this->assertSame('encouragements', $bulletinAicha->mention_travail);

        // Aucune conduite saisie pour Moussa -> valeurs par defaut, non bloquant.
        $this->assertSame(0, $bulletinMoussa->absences);
        $this->assertSame(0, $bulletinMoussa->retards_non_justifies);
        $this->assertNull($bulletinMoussa->mention_travail);
    }

    public function test_tableau_dhonneur_est_automatique_a_partir_de_12_de_moyenne(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        // Aïcha : Maths 18 (coef 3), Français 8.25 (coef 2) -> (18*3+8.25*2)/5 = 14.10 (>= 12).
        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 8.25);
        // Moussa : Maths 10 (coef 3), Français 8 (coef 2) -> (10*3+8*2)/5 = 9.20 (< 12).
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $bulletinAicha = Bulletin::where('inscription_id', $inscriptions[0]->id)->first();
        $bulletinMoussa = Bulletin::where('inscription_id', $inscriptions[1]->id)->first();

        $this->assertEquals(14.1, $bulletinAicha->moyenne_generale);
        $this->assertTrue((bool) $bulletinAicha->tableau_honneur);
        $this->assertFalse((bool) $bulletinMoussa->tableau_honneur);
    }

    #[DataProvider('appreciationsDuSpecimen')]
    public function test_appreciation_par_matiere_suit_le_bareme_du_specimen(float $valeur, string $attendu): void
    {
        $service = new \App\Services\BulletinService();
        $methode = new \ReflectionMethod($service, 'appreciation');
        $methode->setAccessible(true);

        $this->assertSame($attendu, $methode->invoke($service, $valeur));
    }

    public static function appreciationsDuSpecimen(): array
    {
        // Couples (note, appreciation) verifies un par un sur le specimen
        // bulletin_annuel_secondaire.jpeg fourni par l'utilisateur.
        return [
            'très faible' => [4.00, 'Très Faible'],
            'faible bas' => [5.17, 'Faible'],
            'faible haut' => [7.42, 'Faible'],
            'insuffisant' => [8.83, 'Insuffisant'],
            'à peine passable bas' => [9.67, 'À peine passable'],
            'à peine passable haut' => [9.83, 'À peine passable'],
            'passable bas' => [10.00, 'Passable'],
            'passable haut (anglais specimen)' => [11.75, 'Passable'],
            'assez bien bas' => [12.06, 'Assez Bien'],
            'assez bien haut (eps specimen)' => [13.50, 'Assez Bien'],
            'bien bas' => [14.79, 'Bien'],
            'bien haut' => [15.88, 'Bien'],
            'excellent' => [18.00, 'Excellent'],
        ];
    }

    public function test_enseignant_ne_peut_pas_declencher_la_cloture(): void
    {
        [$etablissement, $classe, $sequence, , , , $affectations] = $this->makeStructure();
        $enseignant = $affectations[0]->enseignant;

        $response = $this->actingAs($enseignant)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer");

        $response->assertStatus(403);
    }

    public function test_bulletin_jumele_regroupe_deux_sequences_sur_une_page(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        // Sequence 2 (paire fixe de la sequence 1 cree par makeStructure), pas encore cloturee.
        $sequence2 = Sequence::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $sequence->niveau_id,
            'annee_academique_id' => $sequence->annee_academique_id,
            'numero' => 2,
            'libelle' => 'Séquence 2',
        ]);

        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 12);
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);
        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $bulletinAicha = Bulletin::where('inscription_id', $inscriptions[0]->id)->where('sequence_id', $sequence->id)->first();

        // Avant la cloture de la sequence 2 : le jumelage reste tout de meme telechargeable
        // (l'emplacement de la sequence paire affiche "non disponible").
        $reponseAvant = $this->actingAs($admin)->get("/api/bulletins/{$bulletinAicha->id}/telecharger-jumele");
        $reponseAvant->assertOk();
        $reponseAvant->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $reponseAvant->getContent());

        $this->soumettreNote($affectations[0], $sequence2, $apprenants[0], 16);
        $this->soumettreNote($affectations[1], $sequence2, $apprenants[0], 14);
        $this->soumettreNote($affectations[0], $sequence2, $apprenants[1], 9);
        $this->soumettreNote($affectations[1], $sequence2, $apprenants[1], 7);
        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence2->id}/cloturer")->assertCreated();

        $reponseApres = $this->actingAs($admin)->get("/api/bulletins/{$bulletinAicha->id}/telecharger-jumele");
        $reponseApres->assertOk();
        $this->assertStringStartsWith('%PDF', $reponseApres->getContent());
    }

    public function test_bulletin_annuel_detaille_calcule_la_moyenne_par_matiere_et_generale(): void
    {
        [$etablissement, $classe, $sequence, $matieres, $apprenants, $inscriptions, $affectations] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $sequence2 = Sequence::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $sequence->niveau_id,
            'annee_academique_id' => $sequence->annee_academique_id,
            'numero' => 2,
            'libelle' => 'Séquence 2',
        ]);

        // Aicha : Maths 18 puis 16 (moyenne annuelle 17.00) ; Francais 12 puis 14 (moyenne 13.00).
        $this->soumettreNote($affectations[0], $sequence, $apprenants[0], 18);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[0], 12);
        $this->soumettreNote($affectations[0], $sequence, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence, $apprenants[1], 8);
        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        $this->soumettreNote($affectations[0], $sequence2, $apprenants[0], 16);
        $this->soumettreNote($affectations[1], $sequence2, $apprenants[0], 14);
        $this->soumettreNote($affectations[0], $sequence2, $apprenants[1], 10);
        $this->soumettreNote($affectations[1], $sequence2, $apprenants[1], 8);
        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence2->id}/cloturer")->assertCreated();

        $response = $this->actingAs($admin)->get("/api/classes/{$classe->id}/bulletin-annuel-detaille");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_admin_ne_peut_pas_cloturer_une_classe_dun_autre_etablissement(): void
    {
        [, $classe, $sequence] = $this->makeStructure();
        $autreEtablissement = Etablissement::factory()->create();
        $autreAdmin = $this->makeUser($autreEtablissement, 'admin_etablissement');

        // Le scope multi-etablissement filtre la Classe au niveau du route-model-binding
        // avant meme d'atteindre le controleur : isolation -> 404 (et non 403), cohérent
        // avec le reste du codebase (cf. Phase 5, telechargement de recu).
        $response = $this->actingAs($autreAdmin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer");

        $response->assertStatus(404);
    }
}
