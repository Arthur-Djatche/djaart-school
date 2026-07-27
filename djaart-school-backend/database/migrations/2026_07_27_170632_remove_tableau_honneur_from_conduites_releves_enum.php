<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Tableau d'honneur" est desormais calcule automatiquement (moyenne >= 12,
 * cf. Bulletin::tableau_honneur) et n'est plus une valeur possible pour la
 * saisie manuelle de mention_travail/mention_conduite.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('conduites_releves')->where('mention_travail', 'tableau_honneur')->update(['mention_travail' => null]);
        DB::table('conduites_releves')->where('mention_conduite', 'tableau_honneur')->update(['mention_conduite' => null]);

        // MySQL uniquement : SQLite (tests) traduit deja enum() en simple CHECK
        // constraint qu'il ne sait pas alterer sans recreer la table ; la
        // restriction des valeurs acceptees est de toute facon imposee cote
        // application (StoreConduiteRequest::rules), ce qui suffit pour les tests.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE conduites_releves MODIFY mention_travail ENUM('encouragements', 'avertissement', 'blame') NULL");
            DB::statement("ALTER TABLE conduites_releves MODIFY mention_conduite ENUM('encouragements', 'avertissement', 'blame') NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE conduites_releves MODIFY mention_travail ENUM('tableau_honneur', 'encouragements', 'avertissement', 'blame') NULL");
            DB::statement("ALTER TABLE conduites_releves MODIFY mention_conduite ENUM('tableau_honneur', 'encouragements', 'avertissement', 'blame') NULL");
        }
    }
};
