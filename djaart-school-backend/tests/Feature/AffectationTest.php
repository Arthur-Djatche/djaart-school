<?php

namespace Tests\Feature;

use App\Models\AffectationEnseignant;
use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffectationTest extends TestCase
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

    private function makeEnseignant(Etablissement $etablissement): User
    {
        $enseignant = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $enseignant->assignRole('enseignant');

        return $enseignant;
    }

    /** @return array{0: Etablissement, 1: Classe, 2: Matiere} */
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
        $matiere = Matiere::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'nom' => 'Mathématiques',
            'coefficient' => 2,
        ]);

        return [$etablissement, $classe, $matiere];
    }

    public function test_admin_can_create_affectation(): void
    {
        [$etablissement, $classe, $matiere] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);
        $enseignant = $this->makeEnseignant($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/affectations', [
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $enseignant->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.classe.id', $classe->id)
            ->assertJsonPath('data.matiere.id', $matiere->id)
            ->assertJsonPath('data.enseignant.id', $enseignant->id);
    }

    public function test_rejects_enseignant_from_another_etablissement(): void
    {
        [$etablissement, $classe, $matiere] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);
        $autreEtablissement = Etablissement::factory()->create();
        $enseignant = $this->makeEnseignant($autreEtablissement);

        $response = $this->actingAs($admin)->postJson('/api/affectations', [
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $enseignant->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_rejects_user_without_enseignant_role(): void
    {
        [$etablissement, $classe, $matiere] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);
        $secretaire = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $secretaire->assignRole('secretaire');

        $response = $this->actingAs($admin)->postJson('/api/affectations', [
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $secretaire->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_matiere_must_belong_to_classe_niveau(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);
        $enseignant = $this->makeEnseignant($etablissement);
        $autreNiveau = Niveau::create([
            'etablissement_id' => $etablissement->id,
            'filiere_id' => $classe->niveau->filiere_id,
            'libelle' => 'Niveau 2',
            'ordre' => 2,
            'type_systeme' => 'classique',
        ]);
        $autreMatiere = Matiere::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $autreNiveau->id,
            'nom' => 'Anglais',
            'coefficient' => 1,
        ]);

        $response = $this->actingAs($admin)->postJson('/api/affectations', [
            'classe_id' => $classe->id,
            'matiere_id' => $autreMatiere->id,
            'enseignant_id' => $enseignant->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_unique_classe_matiere_annee(): void
    {
        [$etablissement, $classe, $matiere] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);
        $enseignant = $this->makeEnseignant($etablissement);

        $this->actingAs($admin)->postJson('/api/affectations', [
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $enseignant->id,
        ])->assertCreated();

        $autreEnseignant = $this->makeEnseignant($etablissement);
        $response = $this->actingAs($admin)->postJson('/api/affectations', [
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $autreEnseignant->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_enseignant_sees_only_own_affectations(): void
    {
        [$etablissement, $classe, $matiere] = $this->makeStructure();
        $enseignantA = $this->makeEnseignant($etablissement);
        $enseignantB = $this->makeEnseignant($etablissement);

        AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id,
            'classe_id' => $classe->id,
            'matiere_id' => $matiere->id,
            'enseignant_id' => $enseignantA->id,
            'annee_academique_id' => $classe->annee_academique_id,
        ]);

        $response = $this->actingAs($enseignantB)->getJson('/api/affectations');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_secretaire_cannot_access_affectations(): void
    {
        [$etablissement] = $this->makeStructure();
        $secretaire = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $secretaire->assignRole('secretaire');

        $response = $this->actingAs($secretaire)->getJson('/api/affectations');

        $response->assertStatus(403);
    }
}
