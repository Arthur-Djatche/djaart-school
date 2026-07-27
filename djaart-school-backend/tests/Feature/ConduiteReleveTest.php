<?php

namespace Tests\Feature;

use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\ConduiteReleve;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\Sequence;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConduiteReleveTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Classe, 2: Sequence, 3: Inscription, 4: Inscription} */
    private function makeStructure(?User $professeurPrincipal = null): array
    {
        $etablissement = $professeurPrincipal?->etablissement ?? Etablissement::factory()->create();
        $filiere = Filiere::create(['etablissement_id' => $etablissement->id, 'nom' => 'Filière', 'code' => 'F1']);
        $niveau = Niveau::create([
            'etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id,
            'libelle' => 'Niveau 1', 'ordre' => 1, 'type_systeme' => 'classique',
        ]);
        $annee = AnneeAcademique::create([
            'etablissement_id' => $etablissement->id, 'libelle' => '2025-2026',
            'date_debut' => '2025-09-01', 'date_fin' => '2026-07-31',
        ]);
        $classe = Classe::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'libelle' => 'Classe A', 'effectif_max' => 30,
            'professeur_principal_id' => $professeurPrincipal?->id,
        ]);
        $sequence = Sequence::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'numero' => 1, 'libelle' => 'Séquence 1',
        ]);
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 20000, 'nombre_tranches' => 1,
        ]);
        $apprenant1 = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ET00000001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
        ]);
        $apprenant2 = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ET00000002',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);
        $inscriptions = [];
        foreach ([$apprenant1, $apprenant2] as $apprenant) {
            $inscriptions[] = Inscription::create([
                'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
                'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
                'frais_scolarite_id' => $frais->id, 'statut' => 'en_cours',
                'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
            ]);
        }

        return [$etablissement, $classe, $sequence, $inscriptions[0], $inscriptions[1]];
    }

    public function test_admin_peut_saisir_et_relire_la_conduite(): void
    {
        [$etablissement, $classe, $sequence, $inscription1, $inscription2] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", [
            'lignes' => [
                ['inscription_id' => $inscription1->id, 'absences' => 3, 'absences_non_justifiees' => 1, 'retards' => 2, 'mention_travail' => 'tableau_honneur', 'mention_conduite' => 'encouragements'],
                ['inscription_id' => $inscription2->id, 'absences' => 0],
            ],
        ]);

        $response->assertOk();
        $this->assertSame(2, ConduiteReleve::count());

        $grille = $this->actingAs($admin)->getJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite");
        $grille->assertOk();
        $lignes = collect($grille->json('data'));
        $ligne1 = $lignes->firstWhere('inscription_id', $inscription1->id);
        $this->assertSame(3, $ligne1['absences']);
        $this->assertSame('tableau_honneur', $ligne1['mention_travail']);
    }

    public function test_resaisie_met_a_jour_la_meme_ligne_sans_dupliquer(): void
    {
        [$etablissement, $classe, $sequence, $inscription1] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $payload = fn ($absences) => [
            'lignes' => [['inscription_id' => $inscription1->id, 'absences' => $absences]],
        ];

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", $payload(2))->assertOk();
        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", $payload(5))->assertOk();

        $this->assertSame(1, ConduiteReleve::count());
        $this->assertSame(5, ConduiteReleve::first()->absences);
    }

    public function test_professeur_principal_peut_saisir_la_conduite_de_sa_classe(): void
    {
        $professeurPrincipal = User::factory()->create(['etablissement_id' => Etablissement::factory()->create()->id]);
        $professeurPrincipal->assignRole('enseignant');
        [$etablissement, $classe, $sequence, $inscription1] = $this->makeStructure($professeurPrincipal);

        $response = $this->actingAs($professeurPrincipal)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", [
            'lignes' => [['inscription_id' => $inscription1->id, 'absences' => 1]],
        ]);

        $response->assertOk();
    }

    public function test_enseignant_non_titulaire_ne_peut_pas_saisir_la_conduite(): void
    {
        [$etablissement, $classe, $sequence, $inscription1] = $this->makeStructure();
        $autreEnseignant = $this->makeUser($etablissement, 'enseignant');

        $response = $this->actingAs($autreEnseignant)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", [
            'lignes' => [['inscription_id' => $inscription1->id, 'absences' => 1]],
        ]);

        $response->assertStatus(403);
    }

    public function test_conduite_bloquee_apres_cloture_de_la_sequence(): void
    {
        [$etablissement, $classe, $sequence, $inscription1] = $this->makeStructure();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        Bulletin::create([
            'etablissement_id' => $etablissement->id, 'inscription_id' => $inscription1->id,
            'sequence_id' => $sequence->id, 'moyenne_generale' => 10, 'rang' => 1, 'fichier_pdf' => '',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", [
            'lignes' => [['inscription_id' => $inscription1->id, 'absences' => 1]],
        ]);

        $response->assertStatus(422);
    }

    public function test_comptable_ne_peut_pas_saisir_la_conduite(): void
    {
        [$etablissement, $classe, $sequence, $inscription1] = $this->makeStructure();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/conduite", [
            'lignes' => [['inscription_id' => $inscription1->id, 'absences' => 1]],
        ]);

        $response->assertStatus(403);
    }
}
