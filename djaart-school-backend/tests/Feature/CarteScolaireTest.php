<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\CarteScolaire;
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

class CarteScolaireTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Apprenant, 2: AnneeAcademique} */
    private function makeStructure(): array
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
        Inscription::create([
            'etablissement_id' => $etablissement->id,
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $fraisScolarite->id,
            'statut' => 'validee',
            'type_inscription' => 'nouvelle',
            'date_inscription' => now()->toDateString(),
        ]);

        return [$etablissement, $apprenant, $annee];
    }

    public function test_generation_bloquee_si_pas_de_photo(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $response = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/cartes-scolaires");

        $response->assertStatus(422);
    }

    public function test_upload_photo_puis_generation_reussie(): void
    {
        [$etablissement, $apprenant, $annee] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertOk();

        $response = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/cartes-scolaires");

        $response->assertCreated()
            ->assertJsonPath('data.numero', 1)
            ->assertJsonPath('data.numero_duplicata', 0)
            ->assertJsonPath('data.date_expiration', $annee->date_fin->toDateString());
    }

    public function test_reemission_incremente_le_duplicata(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertOk();

        $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/cartes-scolaires")->assertCreated();
        $response = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/cartes-scolaires");

        $response->assertCreated()
            ->assertJsonPath('data.numero', 2)
            ->assertJsonPath('data.numero_duplicata', 1);
        $this->assertSame(2, CarteScolaire::count());
    }

    public function test_telechargement_est_un_pdf_non_vide(): void
    {
        [$etablissement, $apprenant] = $this->makeStructure();
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertOk();
        $created = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/cartes-scolaires")->json('data');

        $response = $this->actingAs($secretaire)->getJson("/api/cartes-scolaires/{$created['id']}/telecharger");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
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
        $apprenantAvecPhoto = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ET00000001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        $apprenantSansPhoto = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ET00000002',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);
        foreach ([$apprenantAvecPhoto, $apprenantSansPhoto] as $apprenant) {
            Inscription::create([
                'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
                'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
                'frais_scolarite_id' => $fraisScolarite->id, 'statut' => 'validee',
                'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
            ]);
        }

        return [$etablissement, $classe, $apprenantAvecPhoto, $apprenantSansPhoto];
    }

    public function test_generation_en_masse_rapporte_les_succes_et_echecs_individuellement(): void
    {
        [$etablissement, $classe, $apprenantAvecPhoto, $apprenantSansPhoto] = $this->makeStructureAvecDeuxApprenants();
        $secretaire = $this->makeUser($etablissement, 'secretaire');
        $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenantAvecPhoto->id}/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertOk();

        $response = $this->actingAs($secretaire)->postJson("/api/classes/{$classe->id}/cartes-scolaires/masse");

        $response->assertOk();
        $resultats = collect($response->json('data'));
        $ligneAvecPhoto = $resultats->firstWhere('apprenant_id', $apprenantAvecPhoto->id);
        $ligneSansPhoto = $resultats->firstWhere('apprenant_id', $apprenantSansPhoto->id);

        $this->assertTrue($ligneAvecPhoto['success']);
        $this->assertFalse($ligneSansPhoto['success']);
        $this->assertSame(1, CarteScolaire::count());
    }

    public function test_telechargement_zip_regroupe_plusieurs_cartes(): void
    {
        [$etablissement, $classe, $apprenantAvecPhoto, $apprenantSansPhoto] = $this->makeStructureAvecDeuxApprenants();
        $secretaire = $this->makeUser($etablissement, 'secretaire');
        foreach ([$apprenantAvecPhoto, $apprenantSansPhoto] as $apprenant) {
            $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenant->id}/photo", [
                'photo' => UploadedFile::fake()->image('photo.jpg'),
            ])->assertOk();
        }

        $r1 = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenantAvecPhoto->id}/cartes-scolaires")->json('data');
        $r2 = $this->actingAs($secretaire)->postJson("/api/apprenants/{$apprenantSansPhoto->id}/cartes-scolaires")->json('data');

        $response = $this->actingAs($secretaire)->get("/api/cartes-scolaires/zip?ids={$r1['id']},{$r2['id']}");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_isolation_multi_etablissement(): void
    {
        [, $apprenant] = $this->makeStructure();
        $autreEtablissement = Etablissement::factory()->create();
        $autreSecretaire = $this->makeUser($autreEtablissement, 'secretaire');

        $response = $this->actingAs($autreSecretaire)->postJson("/api/apprenants/{$apprenant->id}/cartes-scolaires");

        $response->assertStatus(404);
    }
}
