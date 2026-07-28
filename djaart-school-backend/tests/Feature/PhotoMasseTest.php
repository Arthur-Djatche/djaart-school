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
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoMasseTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Classe, 2: Apprenant, 3: Apprenant} */
    private function makeStructureAvecDeuxApprenants(): array
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
        // Kone doit passer avant Traore dans le tri alphabetique (nom, prenom).
        $moussa = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ET00000001',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);
        $aicha = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ET00000002',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        foreach ([$moussa, $aicha] as $apprenant) {
            Inscription::create([
                'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
                'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
                'frais_scolarite_id' => $fraisScolarite->id, 'statut' => 'validee',
                'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
            ]);
        }

        return [$etablissement, $classe, $moussa, $aicha];
    }

    public function test_import_rejete_si_le_nombre_de_photos_ne_correspond_pas(): void
    {
        [$etablissement, $classe] = $this->makeStructureAvecDeuxApprenants();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson("/api/classes/{$classe->id}/apprenants/photos-masse", [
            'photos' => [UploadedFile::fake()->image('1.jpg')],
        ]);

        $response->assertStatus(422);
        $this->assertNull($classe->inscriptions()->first()->apprenant->fresh()->photo);
    }

    public function test_import_attribue_les_photos_par_position_dans_lordre_alphabetique(): void
    {
        [$etablissement, $classe, $moussa, $aicha] = $this->makeStructureAvecDeuxApprenants();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson("/api/classes/{$classe->id}/apprenants/photos-masse", [
            'photos' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg')],
        ]);

        $response->assertOk();
        $resultats = collect($response->json('data'));

        // Kone (index 0, alphabetiquement avant Traore) recoit la 1ere photo du tableau.
        $this->assertSame($moussa->id, $resultats->first()['apprenant_id']);
        $this->assertSame($aicha->id, $resultats->last()['apprenant_id']);
        $this->assertTrue($resultats->every(fn ($r) => $r['success']));
        $this->assertNotNull($moussa->fresh()->photo);
        $this->assertNotNull($aicha->fresh()->photo);
    }

    public function test_comptable_ne_peut_pas_importer_en_masse(): void
    {
        [$etablissement, $classe] = $this->makeStructureAvecDeuxApprenants();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson("/api/classes/{$classe->id}/apprenants/photos-masse", [
            'photos' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg')],
        ]);

        $response->assertStatus(403);
    }

    public function test_secretaire_dun_autre_etablissement_ne_peut_pas_importer(): void
    {
        [, $classe] = $this->makeStructureAvecDeuxApprenants();
        $autreEtablissement = Etablissement::factory()->create();
        $autreSecretaire = $this->makeUser($autreEtablissement, 'secretaire');

        $response = $this->actingAs($autreSecretaire)->postJson("/api/classes/{$classe->id}/apprenants/photos-masse", [
            'photos' => [UploadedFile::fake()->image('1.jpg'), UploadedFile::fake()->image('2.jpg')],
        ]);

        $response->assertStatus(404);
    }
}
