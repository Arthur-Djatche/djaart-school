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

class FraisScolariteTest extends TestCase
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

    public function test_tranches_sum_mismatch_is_rejected(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'mode' => 'tranches',
            'tranches' => [
                ['numero' => 1, 'montant' => 40000, 'date_echeance' => '2025-09-01'],
                ['numero' => 2, 'montant' => 40000, 'date_echeance' => '2025-10-01'],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_comptant_mode_creates_single_tranche(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'mode' => 'comptant',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre_tranches', 1)
            ->assertJsonPath('data.tranches.0.montant', 100000);
    }

    public function test_tranches_mode_with_correct_sum_is_accepted(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $response = $this->actingAs($admin)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'mode' => 'tranches',
            'tranches' => [
                ['numero' => 1, 'montant' => 40000, 'date_echeance' => '2025-09-01'],
                ['numero' => 2, 'montant' => 60000, 'date_echeance' => '2025-10-01'],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.nombre_tranches', 2);
    }

    public function test_duplicate_niveau_annee_is_rejected(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $this->actingAs($admin)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'mode' => 'comptant',
        ])->assertCreated();

        $response = $this->actingAs($admin)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 120000,
            'mode' => 'comptant',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_cannot_configure_niveau_of_another_etablissement(): void
    {
        [, $niveauA, $anneeA] = $this->makeStructure();
        [$etablissementB] = $this->makeStructure();
        $adminB = $this->makeAdmin($etablissementB);

        $response = $this->actingAs($adminB)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveauA->id,
            'annee_academique_id' => $anneeA->id,
            'montant_total' => 100000,
            'mode' => 'comptant',
        ]);

        $response->assertStatus(422);
    }

    public function test_update_replaces_tranches(): void
    {
        [$etablissement, $niveau, $annee] = $this->makeStructure();
        $admin = $this->makeAdmin($etablissement);

        $created = $this->actingAs($admin)->postJson('/api/frais-scolarite', [
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'mode' => 'comptant',
        ])->json('data');

        $response = $this->actingAs($admin)->putJson("/api/frais-scolarite/{$created['id']}", [
            'montant_total' => 90000,
            'mode' => 'tranches',
            'tranches' => [
                ['numero' => 1, 'montant' => 45000, 'date_echeance' => '2025-09-01'],
                ['numero' => 2, 'montant' => 45000, 'date_echeance' => '2025-10-01'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.montant_total', 90000)
            ->assertJsonPath('data.nombre_tranches', 2);
        $this->assertCount(2, $response->json('data.tranches'));
    }
}
