<?php

namespace Tests\Feature;

use App\Models\DemandeDemo;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandeDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_visiteur_peut_soumettre_une_demande_de_demo(): void
    {
        $response = $this->postJson('/api/demandes-demo', [
            'nom' => 'Fatou Diop',
            'email' => 'fatou@example.com',
            'nom_etablissement' => 'Lycée Moderne',
            'effectif_estime' => 300,
        ]);

        $response->assertCreated();
        $this->assertSame(1, DemandeDemo::count());
        $this->assertSame('fatou@example.com', DemandeDemo::first()->email);
    }

    public function test_demande_de_demo_requiert_les_champs_obligatoires(): void
    {
        $response = $this->postJson('/api/demandes-demo', ['nom' => 'Fatou Diop']);

        $response->assertStatus(422);
        $this->assertSame(0, DemandeDemo::count());
    }

    public function test_liste_des_demandes_reservee_au_super_admin(): void
    {
        DemandeDemo::create([
            'nom' => 'Fatou Diop', 'email' => 'fatou@example.com', 'nom_etablissement' => 'Lycée Moderne',
        ]);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->getJson('/api/demandes-demo');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_admin_etablissement_ne_peut_pas_voir_les_demandes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin_etablissement');

        $response = $this->actingAs($admin)->getJson('/api/demandes-demo');

        $response->assertStatus(403);
    }
}
