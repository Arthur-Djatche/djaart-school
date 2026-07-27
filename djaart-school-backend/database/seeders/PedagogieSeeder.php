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

        // Affectation de demo : l'unique compte enseignant de demo (Lycée Démo DJAART)
        // sur la matière Mathématiques de la classe "6ème A".
        $enseignant = User::where('email', 'enseignant@djaart.school')->firstOrFail();
        $classe = Classe::where('libelle', '6ème A')->firstOrFail();
        $matiere = Matiere::where('etablissement_id', $classe->etablissement_id)
            ->where('niveau_id', $classe->niveau_id)
            ->where('nom', 'Mathématiques')
            ->firstOrFail();

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
