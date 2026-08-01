<?php

namespace Tests\Feature;

use App\Mail\CompteCreeMail;
use App\Mail\DemandeDemoValideeMail;
use App\Models\DemandeDemo;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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

    /**
     * Bug rapporte : un serveur SMTP qui rejette le destinataire (ex. "550
     * recipient unexistant") faisait echouer toute la soumission en 500,
     * alors que la demande elle-meme n'a aucune raison d'etre bloquee par
     * un incident de notification interne (cf. App\Support\Mailer).
     */
    public function test_un_echec_denvoi_de_notification_ne_bloque_pas_la_soumission(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $pendingMail = \Mockery::mock();
        $pendingMail->shouldReceive('send')->once()->andThrow(
            new \RuntimeException('Expected response code "250" but got code "550", with message "550 recipient unexistant"')
        );
        Mail::shouldReceive('to')->once()->andReturn($pendingMail);

        $response = $this->postJson('/api/demandes-demo', [
            'nom' => 'Fatou Diop',
            'email' => 'fatou@example.com',
            'nom_etablissement' => 'Lycée Moderne',
            'effectif_estime' => 300,
        ]);

        $response->assertCreated();
        $this->assertSame(1, DemandeDemo::count());
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

    public function test_validation_cree_etablissement_et_admin_avec_acces_48h(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $demande = DemandeDemo::create([
            'nom' => 'Fatou Diop', 'email' => 'fatou@example.com', 'nom_etablissement' => 'Lycée Moderne',
        ]);

        $avant = now();
        $response = $this->actingAs($superAdmin)->postJson("/api/demandes-demo/{$demande->id}/valider", [
            'type_etablissement' => 'secondaire',
        ]);

        $response->assertOk();

        $demande->refresh();
        $this->assertSame('validee', $demande->statut);
        $this->assertNotNull($demande->etablissement_id);
        $this->assertSame($superAdmin->id, $demande->traite_par_id);

        $etablissement = Etablissement::find($demande->etablissement_id);
        $this->assertSame('secondaire', $etablissement->type_etablissement);
        // 48h pres, pas simplement "un jour proche" — la precision a l'heure
        // est le point meme de ce correctif (abonnement_expire_le en datetime).
        $this->assertTrue($etablissement->abonnement_expire_le->between($avant->copy()->addHours(47), $avant->copy()->addHours(49)));

        $admin = User::where('email', 'fatou@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin_etablissement'));
        $this->assertTrue($admin->must_change_password);
        $this->assertSame($etablissement->id, $admin->etablissement_id);

        Mail::assertSent(DemandeDemoValideeMail::class, fn ($mail) => $mail->user->is($admin));
        Mail::assertNotSent(CompteCreeMail::class);
    }

    public function test_admin_non_super_ne_peut_pas_valider_une_demande(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin_etablissement');
        $demande = DemandeDemo::create([
            'nom' => 'Fatou Diop', 'email' => 'fatou@example.com', 'nom_etablissement' => 'Lycée Moderne',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/demandes-demo/{$demande->id}/valider", [
            'type_etablissement' => 'secondaire',
        ]);

        $response->assertStatus(403);
        $this->assertSame('en_attente', $demande->fresh()->statut);
    }

    public function test_une_demande_deja_traitee_ne_peut_pas_etre_revalidee(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $demande = DemandeDemo::create([
            'nom' => 'Fatou Diop', 'email' => 'fatou@example.com', 'nom_etablissement' => 'Lycée Moderne', 'statut' => 'validee',
        ]);

        $response = $this->actingAs($superAdmin)->postJson("/api/demandes-demo/{$demande->id}/valider", [
            'type_etablissement' => 'secondaire',
        ]);

        $response->assertStatus(422);
    }

    public function test_validation_pour_un_admin_existant_le_rattache_sans_nouveau_mot_de_passe(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $premierEtablissement = Etablissement::factory()->create();
        $adminExistant = User::factory()->create([
            'etablissement_id' => $premierEtablissement->id,
            'email' => 'fatou@example.com',
            'must_change_password' => false,
        ]);
        $adminExistant->assignRole('admin_etablissement');
        $adminExistant->etablissementsGeres()->attach($premierEtablissement->id, ['role' => 'admin_etablissement']);
        $motDePasseAvant = $adminExistant->password;

        $demande = DemandeDemo::create([
            'nom' => 'Fatou Diop', 'email' => 'fatou@example.com', 'nom_etablissement' => 'Lycée Moderne',
        ]);

        $this->actingAs($superAdmin)->postJson("/api/demandes-demo/{$demande->id}/valider", [
            'type_etablissement' => 'secondaire',
        ])->assertOk();

        $adminExistant->refresh();
        $this->assertFalse($adminExistant->must_change_password);
        $this->assertSame($motDePasseAvant, $adminExistant->password);
        $this->assertSame(2, $adminExistant->etablissementsGeres()->count());

        Mail::assertNotSent(DemandeDemoValideeMail::class);
    }

    public function test_lacces_de_demo_est_bloque_passe_les_48h(): void
    {
        $maintenant = now();
        $this->travelTo($maintenant);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $demande = DemandeDemo::create([
            'nom' => 'Fatou Diop', 'email' => 'fatou@example.com', 'nom_etablissement' => 'Lycée Moderne',
        ]);

        $this->actingAs($superAdmin)->postJson("/api/demandes-demo/{$demande->id}/valider", [
            'type_etablissement' => 'secondaire',
        ])->assertOk();

        $admin = User::where('email', 'fatou@example.com')->first();
        $admin->update(['must_change_password' => false]);

        $this->travelTo($maintenant->copy()->addHours(47));
        $this->actingAs($admin)->getJson('/api/dashboard')->assertOk();

        $this->travelTo($maintenant->copy()->addHours(49));
        $this->actingAs($admin)->getJson('/api/dashboard')->assertStatus(403);
    }
}
