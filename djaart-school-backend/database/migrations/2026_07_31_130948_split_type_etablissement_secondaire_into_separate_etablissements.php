<?php

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LIBELLES_TYPE = [
        'primaire' => 'Primaire',
        'secondaire' => 'Secondaire',
        'universitaire' => 'Universitaire',
        'centre_formation' => 'Centre de formation',
    ];

    /**
     * Le cumul "2 types sur le meme etablissement" (type_etablissement_secondaire)
     * est remplace par 2 etablissements distincts partageant le meme admin,
     * qui bascule desormais entre eux (cf. User::etablissementsGeres) au lieu
     * d'avoir acces aux deux d'un coup sur un seul etablissement.
     */
    public function up(): void
    {
        // 1) Comble les lignes de pivot manquantes pour l'etablissement "actif"
        // de chaque utilisateur (comptes de demo notamment : crees avant que le
        // pivot ne devienne la source de verite, jamais rattaches depuis) —
        // sans quoi la bascule vers un etablissement nouvellement cree a
        // l'etape 2 laisserait l'utilisateur incapable de revenir au premier.
        User::whereNotNull('etablissement_id')->get()->each(function (User $user) {
            $dejaLie = $user->etablissementsGeres()->where('etablissements.id', $user->etablissement_id)->exists();
            if ($dejaLie) {
                return;
            }

            $role = $user->getRoleNames()->first();
            if (! $role) {
                return;
            }

            $user->etablissementsGeres()->attach($user->etablissement_id, [
                'role' => $role,
                'permissions' => $user->getDirectPermissions()->pluck('name')->all(),
            ]);
        });

        // 2) Scinde chaque etablissement a 2 types en 2 etablissements, en
        // rattachant chaque admin_etablissement du premier au second.
        Etablissement::whereNotNull('type_etablissement_secondaire')->get()->each(function (Etablissement $etablissement) {
            $nouveau = Etablissement::create([
                'nom' => $etablissement->nom.' — '.(self::LIBELLES_TYPE[$etablissement->type_etablissement_secondaire] ?? $etablissement->type_etablissement_secondaire),
                'type_etablissement' => $etablissement->type_etablissement_secondaire,
                'abonnement_expire_le' => $etablissement->abonnement_expire_le,
                'sigle' => $etablissement->sigle,
                'adresse' => $etablissement->adresse,
            ]);

            $admins = User::whereHas('etablissementsGeres', function ($query) use ($etablissement) {
                $query->where('etablissements.id', $etablissement->id)->where('etablissement_user.role', 'admin_etablissement');
            })->get();

            $admins->each(function (User $admin) use ($nouveau) {
                $admin->etablissementsGeres()->syncWithoutDetaching([
                    $nouveau->id => [
                        'role' => 'admin_etablissement',
                        'permissions' => $admin->getDirectPermissions()->pluck('name')->all(),
                    ],
                ]);
            });
        });

        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn('type_etablissement_secondaire');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->string('type_etablissement_secondaire')->nullable()->after('type_etablissement');
        });
    }
};
