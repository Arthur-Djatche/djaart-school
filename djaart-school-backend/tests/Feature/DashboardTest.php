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
use App\Models\Paiement;
use App\Models\Sequence;
use App\Models\Tranche;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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
     *     0: Etablissement, 1: Classe, 2: Inscription, 3: Tranche, 4: Tranche, 5: Apprenant
     * }
     */
    private function makeStructureAvecImpaye(): array
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
            'statut' => 'en_cours',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'libelle' => 'Classe A',
            'effectif_max' => 30,
        ]);
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'frais_inscription' => 20000,
            'nombre_tranches' => 2,
        ]);
        $trancheEnRetard = Tranche::create([
            'etablissement_id' => $etablissement->id,
            'frais_scolarite_id' => $frais->id,
            'numero' => 1,
            'montant' => 50000,
            'date_echeance' => '2020-01-01',
        ]);
        $trancheAVenir = Tranche::create([
            'etablissement_id' => $etablissement->id,
            'frais_scolarite_id' => $frais->id,
            'numero' => 2,
            'montant' => 50000,
            'date_echeance' => '2030-01-01',
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id,
            'matricule' => 'ETB-00001',
            'nom' => 'Traoré',
            'prenom' => 'Aïcha',
            'date_naissance' => '2013-05-14',
            'sexe' => 'F',
        ]);
        $inscription = Inscription::create([
            'etablissement_id' => $etablissement->id,
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id,
            'statut' => 'en_cours',
            'type_inscription' => 'nouvelle',
            'date_inscription' => now()->toDateString(),
        ]);
        $caissier = $this->makeUser($etablissement, 'comptable');
        Paiement::create([
            'etablissement_id' => $etablissement->id,
            'inscription_id' => $inscription->id,
            'tranche_id' => $trancheEnRetard->id,
            'montant' => 10000,
            'mode_paiement' => 'especes',
            'caissier_id' => $caissier->id,
            'date_paiement' => now()->toDateString(),
        ]);

        return [$etablissement, $classe, $inscription, $trancheEnRetard, $trancheAVenir, $apprenant];
    }

    public function test_super_admin_recoit_les_agregats_globaux(): void
    {
        [$etablissement1] = $this->makeStructureAvecImpaye();
        [$etablissement2] = $this->makeStructureAvecImpaye();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertSame('super_admin', $response->json('data.role'));
        $this->assertSame(2, $response->json('data.data.nombre_etablissements'));
        $this->assertSame(2, $response->json('data.data.total_apprenants'));
        $this->assertCount(2, $response->json('data.data.par_etablissement'));

        $noms = collect($response->json('data.data.par_etablissement'))->pluck('etablissement');
        $this->assertTrue($noms->contains($etablissement1->nom));
    }

    public function test_admin_etablissement_recoit_le_taux_de_recouvrement_et_les_impayes_de_son_etablissement(): void
    {
        [$etablissement, $classe, , $trancheEnRetard] = $this->makeStructureAvecImpaye();
        // Bruit d'un autre etablissement : ne doit jamais apparaitre dans la reponse.
        $this->makeStructureAvecImpaye();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $response = $this->actingAs($admin)->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertSame('admin_etablissement', $response->json('data.role'));
        // 10 000 encaisses / 100 000 attendus (montant_total de la grille) = 10%.
        $this->assertEquals(10.0, $response->json('data.data.taux_recouvrement'));

        $effectifs = collect($response->json('data.data.effectifs_par_classe'));
        $this->assertSame(1, $effectifs->firstWhere('classe', $classe->libelle)['effectif']);

        $impayes = collect($response->json('data.data.top_impayes'));
        $this->assertCount(1, $impayes);
        $this->assertSame($trancheEnRetard->numero, $impayes->first()['tranche_numero']);
        $this->assertEquals(40000, $impayes->first()['solde']);
    }

    public function test_comptable_recoit_ses_encaissements_du_jour_et_les_impayes(): void
    {
        [$etablissement] = $this->makeStructureAvecImpaye();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertSame('comptable', $response->json('data.role'));
        $this->assertEquals(10000, $response->json('data.data.encaisse_aujourdhui'));
        $this->assertEquals(10000, $response->json('data.data.encaisse_mois'));
        $this->assertCount(1, $response->json('data.data.impayes'));
    }

    public function test_enseignant_recoit_ses_affectations_avec_le_statut_de_saisie(): void
    {
        [$etablissement, $classe] = $this->makeStructureAvecImpaye();
        $niveau = $classe->niveau;
        $annee = $classe->anneeAcademique;
        $matiere = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Mathématiques', 'coefficient' => 3,
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
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00002',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);
        Note::create([
            'etablissement_id' => $etablissement->id, 'affectation_id' => $affectation->id,
            'apprenant_id' => $apprenant->id, 'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence', 'valeur' => 15, 'absent' => false, 'soumis_le' => now(),
        ]);

        $response = $this->actingAs($enseignant)->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertSame('enseignant', $response->json('data.role'));
        $affectations = collect($response->json('data.data.affectations'));
        $this->assertCount(1, $affectations);
        $this->assertTrue($affectations->first()['soumis']);
        $this->assertSame('Séquence 1', $affectations->first()['periode']);
    }

    public function test_admin_etablissement_ne_voit_pas_les_affectations_incompletes_dun_autre_etablissement(): void
    {
        [$etablissementA, $classeA] = $this->makeStructureAvecImpaye();
        $niveauA = $classeA->niveau;
        $matiereA = Matiere::create([
            'etablissement_id' => $etablissementA->id, 'niveau_id' => $niveauA->id,
            'nom' => 'Mathématiques', 'coefficient' => 3,
        ]);
        Sequence::create([
            'etablissement_id' => $etablissementA->id, 'niveau_id' => $niveauA->id,
            'annee_academique_id' => $classeA->annee_academique_id, 'numero' => 1, 'libelle' => 'Séquence 1',
        ]);
        $enseignantA = $this->makeUser($etablissementA, 'enseignant');
        AffectationEnseignant::create([
            'etablissement_id' => $etablissementA->id, 'classe_id' => $classeA->id,
            'matiere_id' => $matiereA->id, 'enseignant_id' => $enseignantA->id,
            'annee_academique_id' => $classeA->annee_academique_id,
        ]);

        [$etablissementB] = $this->makeStructureAvecImpaye();
        $adminB = $this->makeUser($etablissementB, 'admin_etablissement');

        $response = $this->actingAs($adminB)->getJson('/api/dashboard');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.data.affectations_incompletes'));
    }
}
