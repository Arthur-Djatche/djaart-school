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
use App\Models\Paiement;
use App\Models\Tranche;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaiementTest extends TestCase
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

    /** @return array{0: Etablissement, 1: Inscription, 2: Tranche, 3: Tranche} */
    private function makeInscriptionAvecDeuxTranches(): array
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
            'effectif_max' => 30,
        ]);
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id,
            'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id,
            'montant_total' => 100000,
            'frais_inscription' => 40000,
            'nombre_tranches' => 2,
        ]);
        $tranche1 = Tranche::create([
            'etablissement_id' => $etablissement->id,
            'frais_scolarite_id' => $frais->id,
            'numero' => 1,
            'montant' => 50000,
            'date_echeance' => '2025-09-01',
        ]);
        $tranche2 = Tranche::create([
            'etablissement_id' => $etablissement->id,
            'frais_scolarite_id' => $frais->id,
            'numero' => 2,
            'montant' => 50000,
            'date_echeance' => '2026-01-01',
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id,
            'matricule' => 'ETB-00001',
            'nom' => 'Traoré',
            'prenom' => 'Aïcha',
            'date_naissance' => '2013-05-14',
            'sexe' => 'F',
        ]);
        $inscription = Inscription::create([
            'etablissement_id' => $etablissement->id,
            'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id,
            'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id,
            'statut' => 'en_cours',
            'type_inscription' => 'nouvelle',
            'date_inscription' => now()->toDateString(),
        ]);

        return [$etablissement, $inscription, $tranche1, $tranche2];
    }

    private function paiementPayload(Inscription $inscription, float $montant, string $mode = 'especes'): array
    {
        return [
            'inscription_id' => $inscription->id,
            'montant' => $montant,
            'mode_paiement' => $mode,
        ];
    }

    public function test_paiement_total_genere_un_recu_et_valide_linscription(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 100000));

        $response->assertCreated()->assertJsonPath('data.montant', 100000);
        $this->assertNotNull($response->json('data.recu.id'));
        $this->assertSame(1, $response->json('data.recu.numero_recu'));
        $this->assertSame('validee', $inscription->fresh()->statut);
    }

    public function test_numeros_de_recu_sont_sequentiels_par_etablissement(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $r1 = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000));
        $r2 = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000));

        $this->assertSame(1, $r1->json('data.recu.numero_recu'));
        $this->assertSame(2, $r2->json('data.recu.numero_recu'));
    }

    public function test_paiement_partiel_laisse_linscription_en_cours(): void
    {
        [$etablissement, $inscription, $tranche1] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 20000));

        $response->assertCreated();
        $this->assertSame('en_cours', $inscription->fresh()->statut);

        $echeancier = $this->actingAs($comptable)->getJson("/api/apprenants/{$inscription->apprenant_id}/echeancier");
        $tranches = collect($echeancier->json('data.inscriptions.0.tranches'));
        $trancheData = $tranches->firstWhere('id', $tranche1->id);

        $this->assertSame('partielle', $trancheData['statut']);
        $this->assertEquals(30000, $trancheData['solde']);
    }

    public function test_paiement_couvrant_les_frais_dinscription_valide_sans_solder_la_tranche(): void
    {
        [$etablissement, $inscription, $tranche1] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        // 45 000 sur une tranche de 50 000 : la tranche reste "partielle" (solde 5 000),
        // mais 45 000 >= les 40 000 de frais d'inscription paramétrés sur la grille ->
        // l'inscription doit tout de même passer à "validee".
        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 45000));

        $response->assertCreated();
        $this->assertSame('validee', $inscription->fresh()->statut);

        $echeancier = $this->actingAs($comptable)->getJson("/api/apprenants/{$inscription->apprenant_id}/echeancier");
        $tranches = collect($echeancier->json('data.inscriptions.0.tranches'));
        $trancheData = $tranches->firstWhere('id', $tranche1->id);

        $this->assertSame('partielle', $trancheData['statut']);
    }

    public function test_un_paiement_unique_peut_couvrir_deux_tranches_a_la_fois(): void
    {
        [$etablissement, $inscription, $tranche1, $tranche2] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        // 70 000 en un seul encaissement : solde la tranche 1 (50 000) et couvre
        // partiellement la tranche 2 (20 000 sur 50 000) — repartition en cascade.
        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 70000));

        $response->assertCreated()->assertJsonPath('data.montant', 70000);
        $this->assertSame(1, Paiement::count());
        $this->assertNull(Paiement::first()->tranche_id);

        $echeancier = $this->actingAs($comptable)->getJson("/api/apprenants/{$inscription->apprenant_id}/echeancier");
        $tranches = collect($echeancier->json('data.inscriptions.0.tranches'));

        $trancheData1 = $tranches->firstWhere('id', $tranche1->id);
        $trancheData2 = $tranches->firstWhere('id', $tranche2->id);

        $this->assertSame('payee', $trancheData1['statut']);
        $this->assertEquals(0, $trancheData1['solde']);
        $this->assertSame('partielle', $trancheData2['statut']);
        $this->assertEquals(30000, $trancheData2['solde']);
        $this->assertEquals(30000, $echeancier->json('data.inscriptions.0.solde_total_pension'));
    }

    public function test_paiement_depassant_le_solde_total_de_la_pension_est_rejete(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 150000));

        $response->assertStatus(422);
        $this->assertSame(0, Paiement::count());
    }

    public function test_paiement_sur_pension_deja_soldee_est_rejete(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 100000))->assertCreated();

        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 10000));

        $response->assertStatus(422);
        $this->assertSame(1, Paiement::count());
    }

    public function test_enseignant_ne_peut_pas_encaisser(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $enseignant = $this->makeUser($etablissement, 'enseignant');

        $response = $this->actingAs($enseignant)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000));

        $response->assertStatus(403);
    }

    public function test_comptable_ne_peut_pas_encaisser_pour_un_autre_etablissement(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $autreEtablissement = Etablissement::factory()->create();
        $comptable = $this->makeUser($autreEtablissement, 'comptable');

        $response = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000));

        $response->assertStatus(422);
        $this->assertSame(0, Paiement::count());
    }

    public function test_telechargement_du_recu_refuse_a_un_autre_etablissement(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $created = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000))->json('data');
        $recuId = $created['recu']['id'];

        $autreEtablissement = Etablissement::factory()->create();
        $autreComptable = $this->makeUser($autreEtablissement, 'comptable');

        // Le scope multi-etablissement filtre le Recu au niveau du route-model-binding
        // avant meme d'atteindre la policy : l'isolation se traduit donc par un 404
        // (et non un 403), cohérent avec le reste du codebase.
        $response = $this->actingAs($autreComptable)->getJson("/api/recus/{$recuId}/telecharger");

        $response->assertStatus(404);
    }

    public function test_telechargement_du_recu_fonctionne_pour_le_meme_etablissement(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $created = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000))->json('data');
        $recuId = $created['recu']['id'];

        $response = $this->actingAs($comptable)->getJson("/api/recus/{$recuId}/telecharger");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    public function test_telechargement_inline_du_recu_pour_limpression_automatique(): void
    {
        [$etablissement, $inscription] = $this->makeInscriptionAvecDeuxTranches();
        $comptable = $this->makeUser($etablissement, 'comptable');

        $created = $this->actingAs($comptable)->postJson('/api/paiements', $this->paiementPayload($inscription, 50000))->json('data');
        $recuId = $created['recu']['id'];

        $response = $this->actingAs($comptable)->getJson("/api/recus/{$recuId}/telecharger?inline=1");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
