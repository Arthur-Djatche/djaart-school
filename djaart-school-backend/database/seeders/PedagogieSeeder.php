<?php

namespace Database\Seeders;

use App\Models\AffectationEnseignant;
use App\Models\Classe;
use App\Models\FraisScolarite;
use App\Models\Matiere;
use App\Models\Semestre;
use App\Models\Sequence;
use App\Models\User;
use Illuminate\Database\Seeder;

class PedagogieSeeder extends Seeder
{
    public function run(): void
    {
        // Une grille de frais existe deja pour chaque couple (niveau, annee) reellement
        // utilise dans les donnees de demo (cf. FraisScolariteSeeder) : on la reutilise
        // comme source des couples niveau+annee a decouper en sequences ou semestres.
        FraisScolarite::with('niveau')->get()->each(function (FraisScolarite $fraisScolarite) {
            $niveau = $fraisScolarite->niveau;

            if ($niveau->type_systeme === 'lmd') {
                foreach ([1, 2] as $numero) {
                    Semestre::firstOrCreate(
                        [
                            'etablissement_id' => $niveau->etablissement_id,
                            'niveau_id' => $niveau->id,
                            'annee_academique_id' => $fraisScolarite->annee_academique_id,
                            'numero' => $numero,
                        ],
                        ['libelle' => "Semestre {$numero}"],
                    );
                }
            } else {
                foreach ([1, 2, 3] as $numero) {
                    Sequence::firstOrCreate(
                        [
                            'etablissement_id' => $niveau->etablissement_id,
                            'niveau_id' => $niveau->id,
                            'annee_academique_id' => $fraisScolarite->annee_academique_id,
                            'numero' => $numero,
                        ],
                        ['libelle' => "Séquence {$numero}"],
                    );
                }
            }
        });

        // Affectation de demo : pour chaque type d'établissement, le compte enseignant
        // de demo est affecté sur les 8 matières de la classe qui porte les 2 apprenants
        // de demo (nécessaire pour que la saisie de notes/bulletin soit utilisable
        // immédiatement). Aucune Note n'est créée ici : pour l'Université, l'utilisateur
        // saisit lui-même les notes.
        $affectationsDemo = [
            ['classe' => '6ème A', 'enseignant' => 'enseignant@djaart.school'],
            ['classe' => 'CP1 A', 'enseignant' => 'enseignant.primaire@djaart.school'],
            ['classe' => 'Licence 1 A', 'enseignant' => 'enseignant.universite@djaart.school'],
            ['classe' => 'CQP Électricien A', 'enseignant' => 'enseignant.centreformation@djaart.school'],
        ];

        foreach ($affectationsDemo as $data) {
            $enseignant = User::where('email', $data['enseignant'])->firstOrFail();
            $classe = Classe::where('libelle', $data['classe'])->firstOrFail();
            $matieres = Matiere::where('etablissement_id', $classe->etablissement_id)
                ->where('niveau_id', $classe->niveau_id)
                ->get();

            foreach ($matieres as $matiere) {
                AffectationEnseignant::firstOrCreate([
                    'etablissement_id' => $classe->etablissement_id,
                    'classe_id' => $classe->id,
                    'matiere_id' => $matiere->id,
                    'annee_academique_id' => $classe->annee_academique_id,
                ], [
                    'enseignant_id' => $enseignant->id,
                ]);
            }
        }
    }
}
