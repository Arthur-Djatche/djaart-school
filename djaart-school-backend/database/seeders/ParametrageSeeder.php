<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\User;
use Illuminate\Database\Seeder;

class ParametrageSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedLyceeDemo();
        $this->seedEtablissement(
            nom: 'École Primaire Démo DJAART',
            type: 'primaire',
            sigle: 'EPD',
            adminEmail: 'admin.primaire@djaart.school',
            adminName: 'Admin École Primaire',
            filiereNom: 'Primaire',
            filiereCode: 'PRIM',
            typeSysteme: 'classique',
            niveaux: [
                ['libelle' => 'CP1', 'ordre' => 1],
                ['libelle' => 'CP2', 'ordre' => 2],
            ],
            matieres: ['Lecture', 'Calcul', 'Éveil scientifique'],
        );
        $this->seedEtablissement(
            nom: 'Université Démo DJAART',
            type: 'universitaire',
            sigle: 'UDD',
            adminEmail: 'admin.universite@djaart.school',
            adminName: 'Admin Université',
            filiereNom: 'Informatique',
            filiereCode: 'INFO',
            typeSysteme: 'lmd',
            niveaux: [
                ['libelle' => 'Licence 1', 'ordre' => 1],
                ['libelle' => 'Licence 2', 'ordre' => 2],
            ],
            matieres: ['Algorithmique', 'Réseaux', 'Bases de données'],
            creditsEcts: 6,
        );
        $this->seedEtablissement(
            nom: 'Centre de Formation Démo DJAART',
            type: 'centre_formation',
            sigle: 'CFD',
            adminEmail: 'admin.centreformation@djaart.school',
            adminName: 'Admin Centre de Formation',
            filiereNom: 'Électricité',
            filiereCode: 'ELEC',
            typeSysteme: 'classique',
            niveaux: [
                ['libelle' => 'CQP Électricien', 'ordre' => 1],
                ['libelle' => 'DQP Électricien', 'ordre' => 2],
            ],
            matieres: ['Électricité générale', 'Sécurité électrique'],
        );
    }

    private function seedLyceeDemo(): void
    {
        $etablissement = Etablissement::where('nom', 'Lycée Démo DJAART')->firstOrFail();

        $annee = AnneeAcademique::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'libelle' => '2025-2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'statut' => 'en_cours'],
        );

        $filiere = Filiere::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'code' => 'GEN'],
            ['nom' => 'Général'],
        );

        foreach ([['libelle' => '6ème', 'ordre' => 1], ['libelle' => '5ème', 'ordre' => 2]] as $data) {
            $niveau = Niveau::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id, 'libelle' => $data['libelle']],
                ['ordre' => $data['ordre'], 'type_systeme' => 'classique'],
            );

            Classe::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'annee_academique_id' => $annee->id],
                ['libelle' => $data['libelle'].' A', 'effectif_max' => 45],
            );

            foreach (['Mathématiques', 'Français', 'Histoire-Géographie'] as $matiereNom) {
                Matiere::firstOrCreate(
                    ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'nom' => $matiereNom],
                    ['coefficient' => 2],
                );
            }
        }
    }

    /** @param array<int, array{libelle: string, ordre: int}> $niveaux */
    /** @param array<int, string> $matieres */
    private function seedEtablissement(
        string $nom,
        string $type,
        string $sigle,
        string $adminEmail,
        string $adminName,
        string $filiereNom,
        string $filiereCode,
        string $typeSysteme,
        array $niveaux,
        array $matieres,
        ?int $creditsEcts = null,
    ): void {
        $etablissement = Etablissement::firstOrCreate(
            ['nom' => $nom],
            ['type_etablissement' => $type, 'sigle' => $sigle],
        );

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            ['name' => $adminName, 'password' => 'password', 'etablissement_id' => $etablissement->id],
        );
        $admin->syncRoles(['admin_etablissement']);

        $annee = AnneeAcademique::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'libelle' => '2025-2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'statut' => 'en_cours'],
        );

        $filiere = Filiere::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'code' => $filiereCode],
            ['nom' => $filiereNom],
        );

        foreach ($niveaux as $data) {
            $niveau = Niveau::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id, 'libelle' => $data['libelle']],
                ['ordre' => $data['ordre'], 'type_systeme' => $typeSysteme],
            );

            Classe::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'annee_academique_id' => $annee->id],
                ['libelle' => $data['libelle'].' A', 'effectif_max' => 40],
            );

            foreach ($matieres as $matiereNom) {
                Matiere::firstOrCreate(
                    ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'nom' => $matiereNom],
                    ['coefficient' => 2, 'credits_ects' => $creditsEcts],
                );
            }
        }
    }
}
