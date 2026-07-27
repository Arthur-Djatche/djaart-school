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
use App\Models\ReleveDeNotes;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RapportTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Inscription} */
    private function makeInscriptionAvecReleve(string $mention): array
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
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 20000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        $inscription = Inscription::create([
            'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id, 'statut' => 'validee',
            'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
        ]);
        ReleveDeNotes::create([
            'etablissement_id' => $etablissement->id, 'inscription_id' => $inscription->id,
            'semestre_id' => null, 'moyenne_generale' => 14.5, 'mention' => $mention,
            'fichier_pdf' => '',
        ]);

        return [$etablissement, $inscription];
    }

    public function test_rapport_impayes_est_un_pdf_valide_pour_le_comptable(): void
    {
        [$etablissement] = $this->makeInscriptionAvecReleve('Bien');
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->get('/api/rapports/impayes');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_rapport_statistiques_reussite_est_un_pdf_valide_pour_ladmin_etablissement(): void
    {
        [$etablissement] = $this->makeInscriptionAvecReleve('Bien');
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $response = $this->actingAs($admin)->get('/api/rapports/statistiques-reussite');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_rapport_statistiques_reussite_accessible_au_super_admin_sans_etablissement(): void
    {
        $this->makeInscriptionAvecReleve('Excellent');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->get('/api/rapports/statistiques-reussite');

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_enseignant_ne_peut_pas_acceder_aux_rapports(): void
    {
        [$etablissement] = $this->makeInscriptionAvecReleve('Bien');
        $enseignant = $this->makeUser($etablissement, 'enseignant');

        $this->actingAs($enseignant)->get('/api/rapports/impayes')->assertStatus(403);
        $this->actingAs($enseignant)->get('/api/rapports/statistiques-reussite')->assertStatus(403);
    }

    public function test_secretaire_ne_peut_pas_acceder_aux_rapports(): void
    {
        [$etablissement] = $this->makeInscriptionAvecReleve('Bien');
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $this->actingAs($secretaire)->get('/api/rapports/impayes')->assertStatus(403);
    }
}
