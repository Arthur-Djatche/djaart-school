<?php

namespace Database\Factories;

use App\Models\Etablissement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Etablissement>
 */
class EtablissementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'type_etablissement' => fake()->randomElement(['primaire', 'secondaire', 'universitaire', 'centre_formation']),
            'sigle' => fake()->lexify('???'),
        ];
    }
}
