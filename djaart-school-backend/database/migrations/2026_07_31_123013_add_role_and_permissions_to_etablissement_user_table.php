<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un meme acteur (n'importe quel role, pas seulement admin_etablissement)
     * peut desormais intervenir dans plusieurs etablissements, avec un role
     * et des droits acces.xxx propres a chacun — stockes ici plutot que sur
     * l'utilisateur, qui ne porte que le role/les droits de l'etablissement
     * "actif" du moment (synchronises a chaque bascule, cf. ProfilController).
     */
    public function up(): void
    {
        Schema::table('etablissement_user', function (Blueprint $table) {
            $table->string('role')->nullable()->after('user_id');
            $table->json('permissions')->nullable()->after('role');
        });

        // Retro-alimentation : les lignes existantes (admin_etablissement
        // uniquement, seul role gerant plusieurs etablissements jusqu'ici)
        // recoivent leur role actuel, sans droits supplementaires. Boucle
        // ligne par ligne (pas de UPDATE...JOIN) pour rester compatible
        // MySQL et SQLite (suite de tests).
        DB::table('etablissement_user')
            ->join('users', 'users.id', '=', 'etablissement_user.user_id')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select('etablissement_user.id', 'roles.name as role')
            ->get()
            ->each(function ($row) {
                DB::table('etablissement_user')->where('id', $row->id)->update(['role' => $row->role]);
            });
    }

    public function down(): void
    {
        Schema::table('etablissement_user', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });
    }
};
