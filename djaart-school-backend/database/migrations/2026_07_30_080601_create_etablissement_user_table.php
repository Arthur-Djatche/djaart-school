<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de liaison pour les admin_etablissement gerant plusieurs
     * etablissements (permutation depuis le meme dashboard) : users.etablissement_id
     * devient l'etablissement "actif" (celui affiche/manipule), cette table la
     * liste complete de ceux que l'utilisateur peut choisir.
     */
    public function up(): void
    {
        Schema::create('etablissement_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['etablissement_id', 'user_id']);
        });

        // Retro-alimente la table de liaison pour les admin_etablissement deja
        // existants (leur etablissement_id actuel devient leur seul etablissement gere).
        DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'admin_etablissement')
            ->whereNotNull('users.etablissement_id')
            ->select('users.id as user_id', 'users.etablissement_id')
            ->get()
            ->each(function ($row) {
                DB::table('etablissement_user')->insertOrIgnore([
                    'etablissement_id' => $row->etablissement_id,
                    'user_id' => $row->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissement_user');
    }
};
