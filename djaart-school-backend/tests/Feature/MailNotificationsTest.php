<?php

namespace Tests\Feature;

use App\Mail\BulletinPretMail;
use App\Mail\CompteCreeMail;
use App\Mail\DemandeDemoRecueMail;
use App\Mail\DroitsAccesModifiesMail;
use App\Mail\InscriptionValideeMail;
use App\Mail\PaiementRecuMail;
use App\Mail\ReleveDisponibleMail;
use App\Models\AffectationEnseignant;
use App\Models\AnneeAcademique;
use App\Models\Apprenant;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\FraisScolarite;
use App\Models\Inscription;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Note;
use App\Models\Sequence;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailNotificationsTest extends TestCase
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

    public function test_creation_dun_utilisateur_envoie_un_mail_de_bienvenue(): void
    {
        Mail::fake();

        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');

        $this->actingAs($admin)->postJson('/api/users', [
            'name' => 'Nouvelle Secrétaire',
            'email' => 'nouvelle.secretaire@djaart.school',
            'password' => 'password123',
            'role' => 'secretaire',
        ])->assertCreated();

        Mail::assertSent(CompteCreeMail::class, fn ($mail) => $mail->hasTo('nouvelle.secretaire@djaart.school'));
    }

    public function test_modification_des_droits_envoie_un_mail(): void
    {
        Mail::fake();

        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');
        $secretaire = $this->makeUser($etablissement, 'secretaire');

        $this->actingAs($admin)->putJson("/api/users/{$secretaire->id}/permissions", [
            'permissions' => ['acces.caisse'],
        ])->assertOk();

        Mail::assertSent(DroitsAccesModifiesMail::class, fn ($mail) => $mail->hasTo($secretaire->email));
    }

    public function test_paiement_couvrant_les_frais_dinscription_envoie_recu_et_validation(): void
    {
        Mail::fake();

        $etablissement = Etablissement::factory()->create();
        $comptable = $this->makeUser($etablissement, 'comptable');

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
        ]);
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 25000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
            'email' => 'famille.traore@example.com',
        ]);
        $inscription = Inscription::create([
            'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id, 'statut' => 'en_cours',
            'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
        ]);

        $this->actingAs($comptable)->postJson('/api/paiements', [
            'inscription_id' => $inscription->id,
            'montant' => 30000,
            'mode_paiement' => 'especes',
        ])->assertCreated();

        Mail::assertSent(PaiementRecuMail::class, fn ($mail) => $mail->hasTo('famille.traore@example.com'));
        Mail::assertSent(InscriptionValideeMail::class, fn ($mail) => $mail->hasTo('famille.traore@example.com'));
    }

    public function test_apprenant_sans_email_ne_declenche_aucun_envoi(): void
    {
        Mail::fake();

        $etablissement = Etablissement::factory()->create();
        $comptable = $this->makeUser($etablissement, 'comptable');

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
        ]);
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 25000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Koné', 'prenom' => 'Moussa', 'date_naissance' => '2012-08-22', 'sexe' => 'M',
        ]);
        $inscription = Inscription::create([
            'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id, 'statut' => 'en_cours',
            'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
        ]);

        $this->actingAs($comptable)->postJson('/api/paiements', [
            'inscription_id' => $inscription->id,
            'montant' => 30000,
            'mode_paiement' => 'especes',
        ])->assertCreated();

        Mail::assertNotSent(PaiementRecuMail::class);
        Mail::assertNotSent(InscriptionValideeMail::class);
    }

    public function test_cloture_de_sequence_envoie_les_bulletins_aux_apprenants_avec_email(): void
    {
        Mail::fake();

        $etablissement = Etablissement::factory()->create();
        $admin = $this->makeUser($etablissement, 'admin_etablissement');
        $enseignant = $this->makeUser($etablissement, 'enseignant');

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
        ]);
        $matiere = Matiere::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'nom' => 'Mathématiques', 'coefficient' => 3,
        ]);
        $sequence = Sequence::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'numero' => 1, 'libelle' => 'Séquence 1',
        ]);
        $affectation = AffectationEnseignant::create([
            'etablissement_id' => $etablissement->id, 'classe_id' => $classe->id,
            'matiere_id' => $matiere->id, 'enseignant_id' => $enseignant->id,
            'annee_academique_id' => $annee->id,
        ]);
        $frais = FraisScolarite::create([
            'etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id,
            'annee_academique_id' => $annee->id, 'montant_total' => 100000,
            'frais_inscription' => 25000, 'nombre_tranches' => 1,
        ]);
        $apprenant = Apprenant::create([
            'etablissement_id' => $etablissement->id, 'matricule' => 'ETB-00001',
            'nom' => 'Traoré', 'prenom' => 'Aïcha', 'date_naissance' => '2013-05-14', 'sexe' => 'F',
            'email' => 'famille.traore@example.com',
        ]);
        Inscription::create([
            'etablissement_id' => $etablissement->id, 'apprenant_id' => $apprenant->id,
            'classe_id' => $classe->id, 'annee_academique_id' => $annee->id,
            'frais_scolarite_id' => $frais->id,
            'statut' => 'en_cours', 'type_inscription' => 'nouvelle', 'date_inscription' => now()->toDateString(),
        ]);
        Note::create([
            'etablissement_id' => $etablissement->id, 'affectation_id' => $affectation->id,
            'apprenant_id' => $apprenant->id, 'sequence_id' => $sequence->id,
            'type_evaluation' => 'sequence', 'valeur' => 15, 'absent' => false, 'soumis_le' => now(),
        ]);

        $this->actingAs($admin)->postJson("/api/classes/{$classe->id}/sequences/{$sequence->id}/cloturer")->assertCreated();

        Mail::assertSent(BulletinPretMail::class, fn ($mail) => $mail->hasTo('famille.traore@example.com'));
    }

    public function test_demande_de_demo_notifie_les_super_admins(): void
    {
        Mail::fake();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->postJson('/api/demandes-demo', [
            'nom' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'nom_etablissement' => 'Lycée Test',
        ])->assertCreated();

        Mail::assertSent(DemandeDemoRecueMail::class, fn ($mail) => $mail->hasTo($superAdmin->email));
    }
}
