<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequenceTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Niveau, 2: AnneeAcademique} */
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

        return [$etablissement, $niveau, $annee];
    }

    public function test_admin_can_create_sequence(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/sequences', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Séquence 1',
        ]);

        $response->assertCreated()->assertJsonPath('data.numero', 1);
    }

    public function test_duplicate_numero_for_same_niveau_annee_is_rejected(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $this->actingAs($admin)->postJson('/api/sequences', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Séquence 1',
        ])->assertCreated();

        $response = $this->actingAs($admin)->postJson('/api/sequences', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Doublon',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_cannot_configure_sequence_of_another_etablissement(): void
    {
        [, $niveauA, $anneeA] = $this->makeStructure();
        [$etablissementB] = $this->makeStructure();
        $adminB = $this->makeAdmin($etablissementB);

        $response = $this->actingAs($adminB)->postJson('/api/sequences', [
            'niveau_id' => $niveauA->id,
            'annee_academique_id' => $anneeA->id,
            'numero' => 1,
            'libelle' => 'Séquence 1',
        ]);

        $response->assertStatus(422);
    }

    public function test_enseignant_can_list_sequences(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $enseignant = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $enseignant->assignRole('enseignant');

        $this->actingAs($this->makeAdmin($etablissement))->postJson('/api/sequences', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'numero' => 1,
            'libelle' => 'Séquence 1',
        ])->assertCreated();

        $response = $this->actingAs($enseignant)->getJson('/api/sequences');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
