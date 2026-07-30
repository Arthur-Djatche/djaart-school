<?php

namespace Tests\Feature;

use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function makeUser(Etablissement $etablissement, string $role): User
    {
        $user = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_un_utilisateur_peut_mettre_a_jour_son_nom_et_sa_civilite(): void
    {
        $etablissement = Etablissement::factory()->create();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->putJson('/api/moi/profil', [
            'name' => 'Nouveau Nom',
            'civilite' => 'Mme',
        ]);

        $response->assertOk();
        $secretaire->refresh();
        $this->assertSame('Nouveau Nom', $secretaire->name);
        $this->assertSame('Mme', $secretaire->civilite);
    }

    public function test_civilite_hors_liste_est_rejetee(): void
    {
        $etablissement = Etablissement::factory()->create();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->putJson('/api/moi/profil', [
            'name' => 'Nouveau Nom',
            'civilite' => 'Docteur',
        ]);

        $response->assertStatus(422);
    }

    public function test_un_utilisateur_peut_televerser_sa_photo_de_profil(): void
    {
        Storage::fake('public');

        $etablissement = Etablissement::factory()->create();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->post('/api/moi/photo', [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertOk();
        $secretaire->refresh();
        $this->assertNotNull($secretaire->photo);
        Storage::disk('public')->assertExists($secretaire->photo);
    }

    public function test_un_utilisateur_ne_peut_pas_modifier_le_profil_dun_autre(): void
    {
        $etablissement = Etablissement::factory()->create();
        $secretaire = $this->makeUser($etablissement, 'secretaire');
        $autre = $this->makeUser($etablissement, 'comptable');

        // Aucune route n'accepte d'id cible : /api/moi/profil agit toujours
        // sur l'acteur authentifie, jamais sur un tiers.
        $this->actingAs($secretaire)->putJson('/api/moi/profil', ['name' => 'Usurpation'])->assertOk();

        $this->assertSame('Usurpation', $secretaire->fresh()->name);
        $this->assertNotSame('Usurpation', $autre->fresh()->name);
    }

    public function test_la_signature_avec_titre_est_utilisee_sur_les_documents(): void
    {
        Storage::fake('public');

        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $response = $this->actingAs($admin)->post("/api/etablissements/{$etablissement->id}/signature", [
            'signature' => UploadedFile::fake()->image('signature.png'),
            'signature_titre' => 'Le Fondateur',
        ]);

        $response->assertOk();
        $this->assertSame('Le Fondateur', $etablissement->fresh()->signature_titre);
        $this->assertNotNull($etablissement->fresh()->signature);
    }
}
