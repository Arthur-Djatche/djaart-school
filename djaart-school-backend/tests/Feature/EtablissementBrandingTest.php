<?php

namespace Tests\Feature;

use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EtablissementBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    }

    private function makeUser(Etablissement $etablissement, string $role): User
    {
        $user = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_etablissement_peut_televerser_le_logo_et_la_signature_de_son_etablissement(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $logo = $this->actingAs($admin)->postJson("/api/etablissements/{$etablissement->id}/logo", [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);
        $signature = $this->actingAs($admin)->postJson("/api/etablissements/{$etablissement->id}/signature", [
            'signature' => UploadedFile::fake()->image('signature.png'),
        ]);

        $logo->assertOk();
        $signature->assertOk();
        $this->assertNotNull($logo->json('data.logo_url'));
        $this->assertNotNull($signature->json('data.signature_url'));
        Storage::disk('public')->assertExists($etablissement->fresh()->logo);
        Storage::disk('public')->assertExists($etablissement->fresh()->signature);
    }

    public function test_reteleversement_du_logo_supprime_lancien_fichier(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $this->actingAs($admin)->postJson("/api/etablissements/{$etablissement->id}/logo", [
            'logo' => UploadedFile::fake()->image('logo1.png'),
        ])->assertOk();
        $ancienChemin = $etablissement->fresh()->logo;

        $this->actingAs($admin)->postJson("/api/etablissements/{$etablissement->id}/logo", [
            'logo' => UploadedFile::fake()->image('logo2.png'),
        ])->assertOk();

        Storage::disk('public')->assertMissing($ancienChemin);
    }

    public function test_admin_etablissement_ne_peut_pas_modifier_le_branding_dun_autre_etablissement(): void
    {
        $etablissement = Etablissement::factory()->create();
        $autreEtablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($autreEtablissement, 'admin_etablissement');

        $response = $this->actingAs($admin)->postJson("/api/etablissements/{$etablissement->id}/logo", [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertStatus(403);
    }

    public function test_enseignant_ne_peut_pas_modifier_le_branding(): void
    {
        $etablissement = Etablissement::factory()->create();
        $enseignant = $this->makeUser($etablissement, 'enseignant');

        $response = $this->actingAs($enseignant)->postJson("/api/etablissements/{$etablissement->id}/logo", [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_peut_modifier_le_branding_de_nimporte_quel_etablissement(): void
    {
        $etablissement = Etablissement::factory()->create();
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->postJson("/api/etablissements/{$etablissement->id}/signature", [
            'signature' => UploadedFile::fake()->image('signature.png'),
        ]);

        $response->assertOk();
    }
}
