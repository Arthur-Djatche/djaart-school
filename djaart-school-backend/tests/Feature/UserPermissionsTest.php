<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionsTest extends TestCase
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

    private function makeInscription(Etablissement $etablissement): Inscription
    {
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
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'frais_inscription' => 40000,
            'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id,
            'matricule' => 'ETB-00001',
            'nom' => 'Traoré',
            'prenom' => 'Aïcha',
            'date_naissance' => '2013-05-14',
            'sexe' => 'F',
        ]);

        return Inscription::create([
            'etablissement_id' => $etablissement->id,
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id,
            'statut' => 'en_cours',
            'type_inscription' => 'nouvelle',
            'date_inscription' => now()->toDateString(),
        ]);
    }

    public function test_secretaire_ne_peut_pas_encaisser_avant_droit_accorde(): void
    {
        $etablissement = Etablissement::factory()->create();
        $secretaire = $this->makeUser($etablissement, 'secretaire');
        $inscription = $this->makeInscription($etablissement);

        $response = $this->actingAs($secretaire)->postJson('/api/paiements', [
            'inscription_id' => $inscription->id,
            'montant' => 50000,
            'mode_paiement' => 'especes',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_etablissement_accorde_acces_caisse_et_la_secretaire_peut_encaisser(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');
        $secretaire = $this->makeUser($etablissement, 'secretaire');
        $inscription = $this->makeInscription($etablissement);

        $grant = $this->actingAs($admin)->putJson("/api/users/{$secretaire->id}/permissions", [
            'permissions' => ['acces.caisse'],
        ]);
        $grant->assertOk();
        $this->assertContains('acces.caisse', $grant->json('data.permissions'));

        $response = $this->actingAs($secretaire->fresh())->postJson('/api/paiements', [
            'inscription_id' => $inscription->id,
            'montant' => 50000,
            'mode_paiement' => 'especes',
        ]);

        $response->assertCreated();
    }

    public function test_retrait_du_droit_bloque_a_nouveau_lacces(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');
        $secretaire = $this->makeUser($etablissement, 'secretaire');
        $inscription = $this->makeInscription($etablissement);

        $this->actingAs($admin)->putJson("/api/users/{$secretaire->id}/permissions", [
            'permissions' => ['acces.caisse'],
        ])->assertOk();

        $this->actingAs($admin)->putJson("/api/users/{$secretaire->id}/permissions", [
            'permissions' => [],
        ])->assertOk();

        $response = $this->actingAs($secretaire->fresh())->postJson('/api/paiements', [
            'inscription_id' => $inscription->id,
            'montant' => 50000,
            'mode_paiement' => 'especes',
        ]);

        $response->assertStatus(403);
    }

    public function test_nom_de_permission_hors_catalogue_est_rejete(): void
    {
        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($admin)->putJson("/api/users/{$secretaire->id}/permissions", [
            'permissions' => ['acces.tout_le_systeme'],
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_ne_peut_pas_modifier_les_droits_dun_acteur_dun_autre_etablissement(): void
    {
        $etablissementA = Etablissement::factory()->create();
        $etablissementB = Etablissement::factory()->create();
        $adminA = $this->makeUser($etablissementA, 'admin_etablissement');
        $secretaireB = $this->makeUser($etablissementB, 'secretaire');

        $response = $this->actingAs($adminA)->putJson("/api/users/{$secretaireB->id}/permissions", [
            'permissions' => ['acces.caisse'],
        ]);

        $response->assertStatus(422);
        $this->assertEmpty($secretaireB->fresh()->getDirectPermissions());
    }
}
