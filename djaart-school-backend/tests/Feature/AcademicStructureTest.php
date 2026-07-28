<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Departement;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeAdmin(Etablissement $etablissement): User
    {
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        return $admin;
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_admin_etablissement_can_create_annee_academique_auto_scoped(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/annees-academiques', [
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
        ]);

        $response->assertCreated()->assertJsonPath('data.etablissement_id', $etablissement->id);
    }

    public function test_super_admin_must_specify_etablissement_id(): void
    {
        $etablissement = Etablissement::factory()->create();
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->postJson('/api/annees-academiques', [
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
            'etablissement_id' => $etablissement->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.etablissement_id', $etablissement->id);
    }

    public function test_date_fin_before_date_debut_is_rejected(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/annees-academiques', [
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2025-01-01',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_etablissement_cannot_reference_niveau_from_another_etablissement(): void
    {
        $etablissementA = Etablissement::factory()->create();
        $etablissementB = Etablissement::factory()->create();
        $adminA = $this->makeAdmin($etablissementA);

        $filiereB = Filiere::create(['etablissement_id' => $etablissementB->id, 'nom' => 'Filière B', 'code' => 'B1']);
        $niveauB = Niveau::create([
            'etablissement_id' => $etablissementB->id,
            'filiere_id' => $filiereB->id,
            'libelle' => 'Niveau B',
            'ordre' => 1,
            'type_systeme' => 'classique',
        ]);
        $anneeA = AnneeAcademique::create([
            'etablissement_id' => $etablissementA->id,
            'libelle' => '2025-2026',
            'date_debut' => '2025-09-01',
            'date_fin' => '2026-07-31',
        ]);

        $response = $this->actingAs($adminA)->postJson('/api/classes', [
            'niveau_id' => $niveauB->id,
            'annee_academique_id' => $anneeA->id,
            'libelle' => 'Classe test',
        ]);

        $response->assertStatus(422);
    }

    public function test_index_isolates_filieres_between_etablissements(): void
    {
        $etablissementA = Etablissement::factory()->create();
        $etablissementB = Etablissement::factory()->create();
        $adminA = $this->makeAdmin($etablissementA);

        Filiere::create(['etablissement_id' => $etablissementA->id, 'nom' => 'Filière A', 'code' => 'A1']);
        Filiere::create(['etablissement_id' => $etablissementB->id, 'nom' => 'Filière B', 'code' => 'B1']);

        $response = $this->actingAs($adminA)->getJson('/api/filieres');

        $response->assertOk();
        $codes = collect($response->json('data'))->pluck('code');
        $this->assertContains('A1', $codes);
        $this->assertNotContains('B1', $codes);
    }

    public function test_filiere_code_unique_per_etablissement_but_allowed_across_etablissements(): void
    {
        // Type fixe (non universitaire) : evite que le type aleatoire du
        // factory ne tombe sur 'universitaire' et exige un departement_id,
        // non pertinent pour ce test (unicite du code de filiere).
        $etablissementA = Etablissement::factory()->create(['type_etablissement' => 'secondaire']);
        $etablissementB = Etablissement::factory()->create(['type_etablissement' => 'secondaire']);
        $adminA = $this->makeAdmin($etablissementA);
        $adminB = $this->makeAdmin($etablissementB);

        Filiere::create(['etablissement_id' => $etablissementA->id, 'nom' => 'Filière A', 'code' => 'GEN']);

        $duplicate = $this->actingAs($adminA)->postJson('/api/filieres', ['nom' => 'Autre', 'code' => 'GEN']);
        $duplicate->assertStatus(422);

        $otherEtablissement = $this->actingAs($adminB)->postJson('/api/filieres', ['nom' => 'Général', 'code' => 'GEN']);
        $otherEtablissement->assertCreated();
    }

    public function test_departement_peut_avoir_un_chef_enseignant(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'universitaire']);
        $admin = $this->makeAdmin($etablissement);
        $enseignant = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $enseignant->assignRole('enseignant');

        $response = $this->actingAs($admin)->postJson('/api/departements', [
            'nom' => 'GTIC', 'code' => 'GTIC', 'chef_departement_id' => $enseignant->id,
        ]);

        $response->assertCreated();
        $this->assertSame($enseignant->id, $response->json('data.chef_departement_id'));
        $this->assertSame($enseignant->name, $response->json('data.chef_departement.name'));
    }

    public function test_chef_de_departement_doit_avoir_le_role_enseignant(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'universitaire']);
        $admin = $this->makeAdmin($etablissement);
        $comptable = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $comptable->assignRole('comptable');

        $response = $this->actingAs($admin)->postJson('/api/departements', [
            'nom' => 'GTIC', 'code' => 'GTIC', 'chef_departement_id' => $comptable->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_chef_de_departement_doit_appartenir_au_meme_etablissement(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'universitaire']);
        $autreEtablissement = Etablissement::factory()->create();
        $admin = $this->makeAdmin($etablissement);
        $enseignantAutreEtablissement = User::factory()->create(['etablissement_id' => $autreEtablissement->id]);
        $enseignantAutreEtablissement->assignRole('enseignant');

        $response = $this->actingAs($admin)->postJson('/api/departements', [
            'nom' => 'GTIC', 'code' => 'GTIC', 'chef_departement_id' => $enseignantAutreEtablissement->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_filiere_universitaire_requiert_un_departement(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'universitaire']);
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/filieres', ['nom' => 'Informatique', 'code' => 'INFO']);

        $response->assertStatus(422);
    }

    public function test_filiere_non_universitaire_rejette_un_departement(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'secondaire']);
        $admin = $this->makeAdmin($etablissement);
        $departement = Departement::create(['etablissement_id' => $etablissement->id, 'nom' => 'GTIC', 'code' => 'GTIC']);

        $response = $this->actingAs($admin)->postJson('/api/filieres', [
            'nom' => 'Général', 'code' => 'GEN', 'departement_id' => $departement->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_niveau_requires_valid_type_systeme(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeAdmin($etablissement);
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Filière', 'code' => 'F1']);

        $response = $this->actingAs($admin)->postJson('/api/niveaux', [
            'filiere_id' => $filiere->id,
            'libelle' => 'Niveau X',
            'type_systeme' => 'invalide',
        ]);

        $response->assertStatus(422);
    }

    public function test_comptable_can_list_classes(): void
    {
        $etablissement = Etablissement::factory()->create();
        $comptable = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $comptable->assignRole('comptable');

        $response = $this->actingAs($comptable)->getJson('/api/classes');

        $response->assertOk();
    }
}
