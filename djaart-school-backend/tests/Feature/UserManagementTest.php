<?php

namespace Tests\Feature;

use App\Mail\CompteCreeMail;
use App\Mail\MotDePasseReinitialiseMail;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_super_admin_can_list_and_create_users(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $etablissement = Etablissement::factory()->create();

        $response = $this->actingAs($superAdmin)->getJson('/api/users');
        $response->assertOk();

        $response = $this->actingAs($superAdmin)->postJson('/api/users', [
            'name' => 'Nouveau Comptable',
            'email' => 'nouveau.comptable@djaart.school',
            'password' => 'password123',
            'role' => 'comptable',
            'etablissement_id' => $etablissement->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'nouveau.comptable@djaart.school');
        $this->assertTrue(User::where('email', 'nouveau.comptable@djaart.school')->first()->hasRole('comptable'));
    }

    public function test_admin_etablissement_cannot_assign_super_admin_role(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $response = $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Tentative',
            'email' => 'tentative@djaart.school',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);

        $response->assertStatus(422);
    }

    public function test_non_admin_role_cannot_access_users_endpoint(): void
    {
        $etablissement = Etablissement::factory()->create();
        $enseignant = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $enseignant->assignRole('enseignant');

        $response = $this->actingAs($enseignant)->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_etablissement_cannot_see_users_from_another_etablissement(): void
    {
        $etablissementA = Etablissement::factory()->create();
        $etablissementB = Etablissement::factory()->create();

        $adminA = User::factory()->create(['etablissement_id' => $etablissementA->id]);
        $adminA->assignRole('admin_etablissement');

        $userB = User::factory()->create(['etablissement_id' => $etablissementB->id, 'name' => 'Utilisateur B']);
        $userB->assignRole('secretaire');

        $response = $this->actingAs($adminA)->getJson('/api/users');

        $response->assertOk();
        $emails = collect($response->json('data'))->pluck('email');
        $this->assertNotContains($userB->email, $emails);
    }

    public function test_creation_dun_utilisateur_genere_un_mot_de_passe_et_force_le_changement(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $etablissement = Etablissement::factory()->create();

        $response = $this->actingAs($superAdmin)->postJson('/api/users', [
            'name' => 'Nouvelle Secrétaire',
            'email' => 'nouvelle.secretaire@djaart.school',
            'role' => 'secretaire',
            'etablissement_id' => $etablissement->id,
        ]);

        $response->assertCreated();

        $user = User::where('email', 'nouvelle.secretaire@djaart.school')->first();
        $this->assertTrue($user->must_change_password);

        Mail::assertSent(CompteCreeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email)
                && Hash::check($mail->motDePasse, $user->password);
        });
    }

    public function test_admin_ne_peut_pas_changer_son_propre_role(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $response = $this->actingAs($admin)->putJson("/api/users/{$admin->id}", [
            'role' => 'secretaire',
        ]);

        $response->assertStatus(422);
        $this->assertTrue($admin->fresh()->hasRole('admin_etablissement'));
    }

    public function test_reinitialisation_du_mot_de_passe_par_ladmin_genere_un_nouveau_mot_de_passe(): void
    {
        Mail::fake();

        $etablissement = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $secretaire = User::factory()->create(['etablissement_id' => $etablissement->id, 'password' => 'ancien-mot-de-passe']);
        $secretaire->assignRole('secretaire');
        $ancienHash = $secretaire->password;

        $response = $this->actingAs($admin)->putJson("/api/users/{$secretaire->id}", [
            'reinitialiser_mot_de_passe' => true,
        ]);

        $response->assertOk();

        $secretaire->refresh();
        $this->assertTrue($secretaire->must_change_password);
        $this->assertNotSame($ancienHash, $secretaire->password);

        Mail::assertSent(MotDePasseReinitialiseMail::class, function ($mail) use ($secretaire) {
            return $mail->hasTo($secretaire->email) && Hash::check($mail->motDePasse, $secretaire->password);
        });
    }

    public function test_admin_etablissement_can_delete_user_in_own_etablissement(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $secretaire = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $secretaire->assignRole('secretaire');

        $response = $this->actingAs($admin)->deleteJson("/api/users/{$secretaire->id}");

        $response->assertOk();
        $this->assertModelMissing($secretaire);
    }
}
