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
            enseignantEmail: 'enseignant.primaire@djaart.school',
            enseignantName: 'Enseignant Primaire Démo',
            filiereNom: 'Primaire',
            filiereCode: 'PRIM',
            typeSysteme: 'classique',
            niveaux: [
                ['libelle' => 'CP1', 'ordre' => 1],
                ['libelle' => 'CP2', 'ordre' => 2],
            ],
            matieres: [
                'Lecture', 'Calcul', 'Éveil scientifique', 'Écriture',
                'Expression orale', 'Éducation civique', 'Arts plastiques', 'Éducation physique',
            ],
        );
        $this->seedEtablissement(
            nom: 'Université Démo DJAART',
            type: 'universitaire',
            sigle: 'UDD',
            adminEmail: 'admin.universite@djaart.school',
            adminName: 'Admin Université',
            enseignantEmail: 'enseignant.universite@djaart.school',
            enseignantName: 'Enseignant Université Démo',
            filiereNom: 'Informatique',
            filiereCode: 'INFO',
            typeSysteme: 'lmd',
            niveaux: [
                ['libelle' => 'Licence 1', 'ordre' => 1],
                ['libelle' => 'Licence 2', 'ordre' => 2],
            ],
            matieres: [
                'Algorithmique', 'Réseaux', 'Bases de données', 'Systèmes d\'exploitation',
                'Génie logiciel', 'Mathématiques appliquées', 'Anglais technique', 'Architecture des ordinateurs',
            ],
            creditsEcts: 6,
        );
        $this->seedEtablissement(
            nom: 'Centre de Formation Démo DJAART',
            type: 'centre_formation',
            sigle: 'CFD',
            adminEmail: 'admin.centreformation@djaart.school',
            adminName: 'Admin Centre de Formation',
            enseignantEmail: 'enseignant.centreformation@djaart.school',
            enseignantName: 'Enseignant Centre de Formation Démo',
            filiereNom: 'Électricité',
            filiereCode: 'ELEC',
            typeSysteme: 'classique',
            niveaux: [
                ['libelle' => 'CQP Électricien', 'ordre' => 1],
                ['libelle' => 'DQP Électricien', 'ordre' => 2],
            ],
            matieres: [
                'Électricité générale', 'Sécurité électrique', 'Électronique', 'Automatisme',
                'Lecture de plans', 'Normes NF C 15-100', 'Habilitation électrique', 'Maintenance industrielle',
            ],
        );
    }

    private function seedLyceeDemo(): void
    {
        $etablissement = Etablissement::where('nom', 'Lycée Démo DJAART')->firstOrFail();
        $enseignant = User::where('email', 'enseignant@djaart.school')->first();

        $annee = AnneeAcademique::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'libelle' => '2025-2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'statut' => 'en_cours'],
        );

        $filiere = Filiere::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'code' => 'GEN'],
            ['nom' => 'Général'],
        );

        // Groupes de matieres de demo (Groupe I = scientifique, Groupe II = lettres),
        // pour illustrer les sous-totaux par groupe sur le bulletin classique.
        $groupesParMatiere = [
            'Mathématiques' => 'Groupe I',
            'Physique-Chimie' => 'Groupe I',
            'SVT' => 'Groupe I',
            'Français' => 'Groupe II',
            'Anglais' => 'Groupe II',
            'Histoire-Géographie' => 'Groupe II',
            'EPS' => 'Groupe II',
            'Arts Plastiques' => 'Groupe II',
        ];

        foreach ([['libelle' => '6ème', 'ordre' => 1], ['libelle' => '5ème', 'ordre' => 2]] as $data) {
            $niveau = Niveau::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'filiere_id' => $filiere->id, 'libelle' => $data['libelle']],
                ['ordre' => $data['ordre'], 'type_systeme' => 'classique'],
            );

            $classe = Classe::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'annee_academique_id' => $annee->id],
                ['libelle' => $data['libelle'].' A', 'effectif_max' => 45],
            );

            if ($enseignant && ! $classe->professeur_principal_id) {
                $classe->update(['professeur_principal_id' => $enseignant->id]);
            }

            foreach (array_keys($groupesParMatiere) as $matiereNom) {
                Matiere::firstOrCreate(
                    ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveau->id, 'nom' => $matiereNom],
                    ['coefficient' => 2, 'groupe' => $groupesParMatiere[$matiereNom]],
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
        string $enseignantEmail,
        string $enseignantName,
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

        $enseignant = User::updateOrCreate(
            ['email' => $enseignantEmail],
            ['name' => $enseignantName, 'password' => 'password', 'etablissement_id' => $etablissement->id],
        );
        $enseignant->syncRoles(['enseignant']);

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
