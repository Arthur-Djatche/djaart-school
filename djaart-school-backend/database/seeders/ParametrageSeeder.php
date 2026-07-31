<?php

namespace Database\Seeders;

use App\Models\AnneeAcademique;
use App\Models\Classe;
use App\Models\Departement;
use App\Models\Etablissement;
use App\Models\Filiere;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Semestre;
use App\Models\UniteEnseignement;
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
        $this->seedUniversiteDemo();
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

    /**
     * Université de démo, structurée comme un vrai relevé LMD : un
     * département (GTIC) portant 2 spécialités (Filiere) — Génie Logiciel,
     * pleinement peuplée (2 semestres, UE typées, EC propres à chaque
     * semestre, 2 apprenants), et Maintenance des Systèmes Informatiques,
     * une coquille minimale qui démontre juste le regroupement par
     * département. Remplace l'ancien seedEtablissement générique (matières
     * plates partagées sur tout le niveau), incompatible avec les EC
     * désormais scopés par semestre.
     */
    private function seedUniversiteDemo(): void
    {
        $etablissement = Etablissement::firstOrCreate(
            ['nom' => 'Université Démo DJAART'],
            ['type_etablissement' => 'universitaire', 'sigle' => 'UDD'],
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin.universite@djaart.school'],
            ['name' => 'Admin Université', 'password' => 'password', 'etablissement_id' => $etablissement->id],
        );
        $admin->syncRoles(['admin_etablissement']);
        $admin->etablissementsGeres()->syncWithoutDetaching([
            $etablissement->id => ['role' => 'admin_etablissement', 'permissions' => []],
        ]);

        // Demontre la bascule multi-etablissement (cf. Topbar) : le meme
        // admin gere aussi un centre de formation distinct, plutot que de
        // cumuler ce 2e type sur l'universite elle-meme.
        $etablissementCentreFormation = Etablissement::firstOrCreate(
            ['nom' => 'Université Démo DJAART — Centre de formation'],
            ['type_etablissement' => 'centre_formation', 'sigle' => 'UDD-CF'],
        );
        $admin->etablissementsGeres()->syncWithoutDetaching([
            $etablissementCentreFormation->id => ['role' => 'admin_etablissement', 'permissions' => []],
        ]);

        $enseignant = User::updateOrCreate(
            ['email' => 'enseignant.universite@djaart.school'],
            ['name' => 'Enseignant Université Démo', 'password' => 'password', 'etablissement_id' => $etablissement->id],
        );
        $enseignant->syncRoles(['enseignant']);

        $annee = AnneeAcademique::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'libelle' => '2025-2026'],
            ['date_debut' => '2025-09-01', 'date_fin' => '2026-07-31', 'statut' => 'en_cours'],
        );

        $departement = Departement::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'code' => 'GTIC'],
            ['nom' => "Génie de Technologie de l'Information et de la Communication", 'chef_departement_id' => $enseignant->id],
        );

        $filiereGl = Filiere::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'code' => 'GL'],
            ['nom' => 'Génie Logiciel', 'departement_id' => $departement->id],
        );

        // Filiere coquille (aucun niveau/classe) : demontre uniquement le
        // regroupement de plusieurs specialites sous un meme departement.
        Filiere::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'code' => 'MSI'],
            ['nom' => 'Maintenance des Systèmes Informatiques', 'departement_id' => $departement->id],
        );

        $niveauL1 = Niveau::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'filiere_id' => $filiereGl->id, 'libelle' => 'Licence 1'],
            ['ordre' => 1, 'type_systeme' => 'lmd'],
        );
        $niveauL2 = Niveau::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'filiere_id' => $filiereGl->id, 'libelle' => 'Licence 2'],
            ['ordre' => 2, 'type_systeme' => 'lmd'],
        );

        Classe::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveauL1->id, 'annee_academique_id' => $annee->id],
            ['libelle' => 'Licence 1 A', 'effectif_max' => 40],
        );
        Classe::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveauL2->id, 'annee_academique_id' => $annee->id],
            ['libelle' => 'Licence 2 A', 'effectif_max' => 40],
        );

        $semestre1 = Semestre::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveauL1->id, 'annee_academique_id' => $annee->id, 'numero' => 1],
            ['libelle' => 'Semestre 1'],
        );
        $semestre2 = Semestre::firstOrCreate(
            ['etablissement_id' => $etablissement->id, 'niveau_id' => $niveauL1->id, 'annee_academique_id' => $annee->id, 'numero' => 2],
            ['libelle' => 'Semestre 2'],
        );

        $this->seedUesEtEc($etablissement, $semestre1, [
            ['code' => 'UE111', 'nom' => 'Mathématiques et Algorithmique', 'type' => 'fondamentale', 'matieres' => [
                ['code' => 'GL1111', 'nom' => 'Analyse Mathématique I', 'credits' => 3],
                ['code' => 'GL1112', 'nom' => 'Algorithmique et Programmation', 'credits' => 3],
            ]],
            ['code' => 'UE112', 'nom' => 'Environnement Informatique', 'type' => 'professionnelle', 'matieres' => [
                ['code' => 'GL1121', 'nom' => "Systèmes d'exploitation", 'credits' => 3],
                ['code' => 'GL1122', 'nom' => 'Bases de données I', 'credits' => 3],
            ]],
            ['code' => 'UE113', 'nom' => 'Anglais et Communication', 'type' => 'transversale', 'matieres' => [
                ['code' => 'GL1131', 'nom' => 'Anglais Technique I', 'credits' => 2],
            ]],
        ]);

        $this->seedUesEtEc($etablissement, $semestre2, [
            ['code' => 'UE121', 'nom' => 'Mathématiques Appliquées', 'type' => 'fondamentale', 'matieres' => [
                ['code' => 'GL1211', 'nom' => 'Analyse Mathématique II', 'credits' => 3],
                ['code' => 'GL1212', 'nom' => 'Probabilités et Statistiques', 'credits' => 2],
            ]],
            ['code' => 'UE122', 'nom' => 'Génie Logiciel', 'type' => 'professionnelle', 'matieres' => [
                ['code' => 'GL1221', 'nom' => 'Programmation Orientée Objet', 'credits' => 3],
                ['code' => 'GL1222', 'nom' => 'Bases de données II', 'credits' => 3],
            ]],
            ['code' => 'UE123', 'nom' => 'Communication Professionnelle', 'type' => 'transversale', 'matieres' => [
                ['code' => 'GL1231', 'nom' => 'Anglais Technique II', 'credits' => 2],
            ]],
        ]);
    }

    /** @param array<int, array{code: string, nom: string, type: string, matieres: array<int, array{code: string, nom: string, credits: int}>}> $unites */
    private function seedUesEtEc(Etablissement $etablissement, Semestre $semestre, array $unites): void
    {
        foreach ($unites as $uniteData) {
            $unite = UniteEnseignement::firstOrCreate(
                ['etablissement_id' => $etablissement->id, 'semestre_id' => $semestre->id, 'code' => $uniteData['code']],
                ['nom' => $uniteData['nom'], 'type' => $uniteData['type']],
            );

            foreach ($uniteData['matieres'] as $matiereData) {
                Matiere::firstOrCreate(
                    ['etablissement_id' => $etablissement->id, 'niveau_id' => $semestre->niveau_id, 'code' => $matiereData['code']],
                    [
                        'semestre_id' => $semestre->id,
                        'unite_enseignement_id' => $unite->id,
                        'nom' => $matiereData['nom'],
                        'coefficient' => 1,
                        'credits_ects' => $matiereData['credits'],
                        'ponderation_cc' => 40,
                        'ponderation_session_normale' => 60,
                    ],
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
        $admin->etablissementsGeres()->syncWithoutDetaching([
            $etablissement->id => ['role' => 'admin_etablissement', 'permissions' => []],
        ]);

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
