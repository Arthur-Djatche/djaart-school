<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Attestation;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttestationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeUser(Etablissement $etablissement, string $role): User
    {
        $user = User::factory()->create(['etablissement_id' => $etablissement->id]);
        $user->assignRole($role);

        return $user;
    }

    /** @return array{0: Etablissement, 1: Apprenant, 2: Inscription} */
    private function makeStructure(string $statutInscription = 'validee'): array
    {
        $etablissement = Etablissement::factory()->create();
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Filière', 'code' => 'F1']);
        $niveau = Niveau::create([
            'etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id,
            'libelle' => 'Niveau 1', 'ordre' => 1, 'type_systeme' => 'classique',
        ]);
        $annee = AnneeAcademique::create([
            'etablissement_id' => $etablissement->id, 'libelle' => '2025-2026',
            'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'statut' => 'en_cours',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'libelle' => 'Classe A', 'effectif_max' => 30,
        ]);
        $fraisScolarite = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 15000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        $inscription = Inscription::create([
            'etablissement_id' => $etablissement->id,
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $fraisScolarite->id,
            'statut' => $statutInscription,
            'type_inscription' => 'nouvelle',
            'date_inscription' => now()->toDateString(),
        ]);

        return [$etablissement, $apprenant, $inscription];
    }

    public function test_generation_bloquee_si_inscription_non_validee(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure('en_cours');
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'scolarite',
        ]);

        $response->assertStatus(422);
    }

    public function test_generation_reussie_avec_numero_sequentiel(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure('validee');
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $r1 = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'scolarite',
        ]);
        $r2 = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'reussite',
        ]);

        $r1->assertCreated()->assertJsonPath('data.numero', 1);
        $r2->assertCreated()->assertJsonPath('data.numero', 2);
        $this->assertSame(2, Attestation::count());
    }

    public function test_telechargement_recu_est_un_pdf_non_vide(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure('validee');
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $created = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'scolarite',
        ])->json('data');

        $response = $this->actingAs($secretaire)->getJson("/api/attestations/{$created['id']}/telecharger");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_comptable_ne_peut_pas_generer_attestation(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure('validee');
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'scolarite',
        ]);

        $response->assertStatus(403);
    }

    public function test_enseignant_ne_peut_pas_generer_attestation(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure('validee');
        $enseignant = $this->makeUser($etablissement, 'enseignant');

        $response = $this->actingAs($enseignant)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'scolarite',
        ]);

        $response->assertStatus(403);
    }

    public function test_isolation_multi_etablissement(): void
    {
        [, $apprenant] = $this->makeStructure('validee');
        $autreEtablissement = Etablissement::factory()->create();
        $autreSecretaire = $this->makeUser($autreEtablissement, 'secretaire');

        $response = $this->actingAs($autreSecretaire)->postJson("/api/apprenants/{$apprenant->id}/attestations", [
            'type' => 'scolarite',
        ]);

        $response->assertStatus(404);
    }
}
