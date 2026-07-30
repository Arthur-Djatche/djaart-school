<?php

namespace Tests\Feature;

use App\Mail\CommandeRecueMail;
use App\Mail\CommandeValideeMail;
use App\Mail\NouvelEtablissementAjouteMail;
use App\Models\Commande;
use App\Models\Etablissement;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommandeEtMultiEtablissementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
    }

    private function commandePayload(array $overrides = []): array
    {
        return array_merge([
            'nom' => 'Jean Dupont',
            'ville' => 'Douala',
            'nombre_apprenants' => 120,
            'telephone' => '699000000',
            'email' => 'jean.dupont@example.com',
            'nom_etablissement' => 'Lycée Nouveau',
        ], $overrides);
    }

    public function test_soumission_publique_dune_commande_notifie_les_super_admins(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->postJson('/api/commandes', $this->commandePayload());

        $response->assertCreated();
        $this->assertSame('en_attente', Commande::first()->statut);
        Mail::assertSent(CommandeRecueMail::class, fn ($mail) => $mail->hasTo($superAdmin->email));
    }

    public function test_liste_des_commandes_reservee_au_super_admin(): void
    {
        Commande::create($this->commandePayload());
        $etablissement = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $this->actingAs($admin)->getJson('/api/commandes')->assertStatus(403);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin)->getJson('/api/commandes')->assertOk();
    }

    public function test_validation_cree_etablissement_et_admin_avec_mot_de_passe_provisoire(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $commande = Commande::create($this->commandePayload());

        $response = $this->actingAs($superAdmin)->postJson("/api/commandes/{$commande->id}/valider", [
            'type_etablissement' => 'secondaire',
            'duree_mois' => 12,
            'permissions' => ['acces.caisse', 'acces.inscriptions'],
        ]);

        $response->assertOk();

        $commande->refresh();
        $this->assertSame('validee', $commande->statut);
        $this->assertNotNull($commande->etablissement_id);
        $this->assertSame($superAdmin->id, $commande->traite_par_id);

        $etablissement = Etablissement::find($commande->etablissement_id);
        $this->assertSame('secondaire', $etablissement->type_etablissement);
        $this->assertTrue($etablissement->abonnement_expire_le->isSameDay(now()->addMonths(12)));

        $admin = User::where('email', 'jean.dupont@example.com')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin_etablissement'));
        $this->assertTrue($admin->must_change_password);
        $this->assertSame($etablissement->id, $admin->etablissement_id);
        $this->assertEqualsCanonicalizing(['acces.caisse', 'acces.inscriptions'], $admin->getDirectPermissions()->pluck('name')->all());
        $this->assertTrue($admin->etablissementsGeres()->where('etablissements.id', $etablissement->id)->exists());

        Mail::assertSent(CommandeValideeMail::class, fn ($mail) => $mail->user->is($admin));
    }

    public function test_validation_pour_un_admin_existant_ajoute_un_2e_etablissement_sans_toucher_au_mot_de_passe(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $premierEtablissement = Etablissement::factory()->create();
        $adminExistant = User::factory()->create([
            'etablissement_id' => $premierEtablissement->id,
            'email' => 'jean.dupont@example.com',
            'must_change_password' => false,
        ]);
        $adminExistant->assignRole('admin_etablissement');
        $adminExistant->etablissementsGeres()->attach($premierEtablissement->id);
        $motDePasseAvant = $adminExistant->password;

        $commande = Commande::create($this->commandePayload());

        $this->actingAs($superAdmin)->postJson("/api/commandes/{$commande->id}/valider", [
            'type_etablissement' => 'secondaire',
            'duree_mois' => 6,
            'permissions' => [],
        ])->assertOk();

        $adminExistant->refresh();
        $this->assertFalse($adminExistant->must_change_password);
        $this->assertSame($motDePasseAvant, $adminExistant->password);
        $this->assertSame(2, $adminExistant->etablissementsGeres()->count());

        Mail::assertSent(NouvelEtablissementAjouteMail::class, fn ($mail) => $mail->user->is($adminExistant));
        Mail::assertNotSent(CommandeValideeMail::class);
    }

    public function test_une_commande_deja_traitee_ne_peut_pas_etre_revalidee(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $commande = Commande::create($this->commandePayload(['statut' => 'validee']));

        $response = $this->actingAs($superAdmin)->postJson("/api/commandes/{$commande->id}/valider", [
            'type_etablissement' => 'secondaire',
            'duree_mois' => 12,
            'permissions' => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_mot_de_passe_provisoire_bloque_les_autres_routes_jusquau_changement(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = User::factory()->create([
            'etablissement_id' => $etablissement->id,
            'password' => 'mot-de-passe-provisoire',
            'must_change_password' => true,
        ]);
        $admin->assignRole('admin_etablissement');

        $this->actingAs($admin)->getJson('/api/dashboard')->assertStatus(423);
        $this->actingAs($admin)->getJson('/api/me')->assertOk();

        $response = $this->actingAs($admin)->putJson('/api/moi/mot-de-passe', [
            'mot_de_passe_actuel' => 'mot-de-passe-provisoire',
            'password' => 'nouveau-mot-de-passe',
            'password_confirmation' => 'nouveau-mot-de-passe',
        ]);
        $response->assertOk();

        $admin->refresh();
        $this->assertFalse($admin->must_change_password);
        $this->actingAs($admin)->getJson('/api/dashboard')->assertOk();
    }

    public function test_abonnement_expire_bloque_lacces_sauf_pour_le_super_admin(): void
    {
        $etablissement = Etablissement::factory()->create(['abonnement_expire_le' => now()->subDay()]);
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $this->actingAs($admin)->getJson('/api/dashboard')->assertStatus(403);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin)->getJson('/api/dashboard')->assertOk();
    }

    public function test_bascule_vers_un_etablissement_non_gere_est_rejetee(): void
    {
        $etablissementA = Etablissement::factory()->create();
        $etablissementB = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissementA->id]);
        $admin->assignRole('admin_etablissement');
        $admin->etablissementsGeres()->attach($etablissementA->id);

        $response = $this->actingAs($admin)->putJson('/api/moi/etablissement-actif', [
            'etablissement_id' => $etablissementB->id,
        ]);

        $response->assertStatus(422);
        $this->assertSame($etablissementA->id, $admin->fresh()->etablissement_id);
    }

    public function test_bascule_vers_un_etablissement_gere_fonctionne(): void
    {
        $etablissementA = Etablissement::factory()->create();
        $etablissementB = Etablissement::factory()->create();
        $admin = User::factory()->create(['etablissement_id' => $etablissementA->id]);
        $admin->assignRole('admin_etablissement');
        $admin->etablissementsGeres()->attach([$etablissementA->id, $etablissementB->id]);

        $response = $this->actingAs($admin)->putJson('/api/moi/etablissement-actif', [
            'etablissement_id' => $etablissementB->id,
        ]);

        $response->assertOk();
        $this->assertSame($etablissementB->id, $admin->fresh()->etablissement_id);
    }

    public function test_admin_etablissement_ne_peut_pas_changer_le_type_de_son_etablissement(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'secondaire']);
        $admin = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $admin->assignRole('admin_etablissement');

        $response = $this->actingAs($admin)->putJson("/api/etablissements/{$etablissement->id}", [
            'type_etablissement' => 'universitaire',
        ]);

        $response->assertStatus(422);
        $this->assertSame('secondaire', $etablissement->fresh()->type_etablissement);
    }

    public function test_super_admin_peut_changer_le_type_detablissement(): void
    {
        $etablissement = Etablissement::factory()->create(['type_etablissement' => 'secondaire']);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->putJson("/api/etablissements/{$etablissement->id}", [
            'type_etablissement' => 'universitaire',
        ]);

        $response->assertOk();
        $this->assertSame('universitaire', $etablissement->fresh()->type_etablissement);
    }
}
