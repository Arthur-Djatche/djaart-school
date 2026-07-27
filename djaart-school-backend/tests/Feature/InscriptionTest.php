<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Niveau;
use App\Models\Tranche;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscriptionTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Classe} */
    private function makeStructure(int $effectifMax = 30, bool $withFrais = true): array
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
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'libelle' => 'Classe A',
            'effectif_max' => $effectifMax,
        ]);

        if ($withFrais) {
            $frais = FraisScolarite::create([
                'etablissement_id' => $etablissement->id,
                'niveau_id' => $niveau->id,
                'annee_academique_id' => $annee->id,
                'montant_total' => 100000,
                'nombre_tranches' => 1,
            ]);
            Tranche::create([
                'etablissement_id' => $etablissement->id,
                'frais_scolarite_id' => $frais->id,
                'numero' => 1,
                'montant' => 100000,
                'date_echeance' => '2025-09-01',
            ]);
        }

        return [$etablissement, $classe];
    }

    private function apprenantPayload(): array
    {
        return [
            'nom' => 'Traoré',
            'prenom' => 'Aïcha',
            'date_naissance' => '2013-05-14',
            'sexe' => 'F',
        ];
    }

    public function test_inscription_generates_matricule_and_attaches_frais(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.statut', 'en_cours')
            ->assertJsonPath('data.frais_scolarite.montant_total', 100000);
        $this->assertNotEmpty($response->json('data.apprenant.matricule'));
    }

    public function test_matricules_are_sequential_per_etablissement(): void
    {
        [$etablissement, $classe] = $this->makeStructure(effectifMax: 30);
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $r1 = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ]);
        $r2 = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => ['nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M'],
        ]);

        $r1->assertCreated();
        $r2->assertCreated();

        $matricule1 = $r1->json('data.apprenant.matricule');
        $matricule2 = $r2->json('data.apprenant.matricule');

        $this->assertNotEquals($matricule1, $matricule2);
        $this->assertStringEndsWith('00001', $matricule1);
        $this->assertStringEndsWith('00002', $matricule2);
    }

    public function test_matricule_respecte_le_format_2_lettres_majuscules_plus_8_chiffres(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $matricule = $response->json('data.apprenant.matricule');

        $this->assertSame(10, strlen($matricule));
        $this->assertMatchesRegularExpression('/^[A-Z]{2}[0-9]{8}$/', $matricule);
    }

    public function test_matricule_utilise_xx_quand_le_sigle_est_vide_ou_trop_court(): void
    {
        $etablissementSansSigle = Etablissement::factory()->create(['sigle' => null]);
        [, $classeSansSigle] = $this->makeStructureForEtablissement($etablissementSansSigle);
        $secretaire = $this->makeUser($etablissementSansSigle, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classeSansSigle->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $matricule = $response->json('data.apprenant.matricule');

        $this->assertStringStartsWith('XX', $matricule);
    }

    /** @return array{0: Etablissement, 1: Classe} */
    private function makeStructureForEtablissement(Etablissement $etablissement): array
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
            'nombre_tranches' => 1,
        ]);
        Tranche::create([
            'etablissement_id' => $etablissement->id,
            'frais_scolarite_id' => $frais->id,
            'numero' => 1,
            'montant' => 100000,
            'date_echeance' => '2025-09-01',
        ]);

        return [$etablissement, $classe];
    }

    public function test_full_classe_rejects_inscription(): void
    {
        [$etablissement, $classe] = $this->makeStructure(effectifMax: 1);
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ])->assertCreated();

        $response = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => ['nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M'],
        ]);

        $response->assertStatus(422);
    }

    public function test_classe_without_frais_scolarite_rejects_inscription(): void
    {
        [$etablissement, $classe] = $this->makeStructure(withFrais: false);
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $response->assertStatus(422);
    }

    public function test_enseignant_cannot_create_inscription(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $enseignant = $this->makeUser($etablissement, 'enseignant');

        $response = $this->actingAs($enseignant)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $response->assertStatus(403);
    }

    public function test_comptable_can_create_inscription(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $response->assertCreated()->assertJsonPath('data.statut', 'en_cours');
    }

    public function test_secretaire_cannot_inscribe_into_another_etablissement_classe(): void
    {
        [, $classeA] = $this->makeStructure();
        [$etablissementB] = $this->makeStructure();
        $secretaireB = $this->makeUser($etablissementB, 'secretaire');

        $response = $this->actingAs($secretaireB)->postJson('/api/inscriptions', [
            'classe_id' => $classeA->id,
            'apprenant' => $this->apprenantPayload(),
        ]);

        $response->assertStatus(422);
    }

    public function test_cancel_transitions_to_annulee(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $created = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ])->json('data');

        $response = $this->actingAs($secretaire)->postJson("/api/inscriptions/{$created['id']}/annuler");

        $response->assertOk()->assertJsonPath('data.statut', 'annulee');
    }

    public function test_existing_apprenant_id_is_reused_without_new_matricule(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id,
            'matricule' => 'ETB-00001',
            ...$this->apprenantPayload(),
        ]);

        $response = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant_id' => $apprenant->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.apprenant.matricule', 'ETB-00001');
        $this->assertSame(1, Apprenant::count());
    }

    public function test_second_inscription_of_same_apprenant_is_marked_as_reinscription(): void
    {
        [$etablissement, $classe] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $first = $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $classe->id,
            'apprenant' => $this->apprenantPayload(),
        ])->assertJsonPath('data.type_inscription', 'nouvelle');

        $apprenantId = $first->json('data.apprenant.id');

        // Deuxième inscription du même apprenant dans une classe de son propre établissement.
        $secondClasse = Classe::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $classe->niveau_id,
            'annee_academique_id' => $classe->annee_academique_id,
            'libelle' => 'Classe B',
            'effectif_max' => 30,
        ]);

        $this->actingAs($secretaire)->postJson('/api/inscriptions', [
            'classe_id' => $secondClasse->id,
            'apprenant_id' => $apprenantId,
        ])->assertJsonPath('data.type_inscription', 'reinscription');
    }
}
