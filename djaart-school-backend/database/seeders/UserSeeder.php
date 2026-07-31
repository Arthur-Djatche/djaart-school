<?php

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private const DEMO_USERS = [
        'admin_etablissement' => 'Admin Établissement',
        'secretaire' => 'Secrétaire Démo',
        'comptable' => 'Comptable Démo',
        'enseignant' => 'Enseignant Démo',
        'apprenant' => 'Apprenant Démo',
    ];

    public function run(): void
    {
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@djaart.school'],
            ['name' => 'Super Admin', 'password' => 'password'],
        );
        $superAdmin->assignRole('super_admin');

        $etablissement = Etablissement::firstOrCreate(
            ['nom' => 'Lycée Démo DJAART'],
            ['type_etablissement' => 'secondaire', 'sigle' => 'LDD'],
        );

        foreach (self::DEMO_USERS as $role => $name) {
            $user = User::updateOrCreate(
                ['email' => "{$role}@djaart.school"],
                ['name' => $name, 'password' => 'password', 'etablissement_id' => $etablissement->id],
            );
            $user->syncRoles([$role]);

            if ($role === 'admin_etablissement') {
                $user->etablissementsGeres()->syncWithoutDetaching([
                    $etablissement->id => ['role' => $role, 'permissions' => []],
                ]);
            }
        }
    }
}
