<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute 'rattrapage' aux valeurs acceptees par type_evaluation (note de
 * rattrapage de Session Normale, LMD uniquement). Contrairement au precedent
 * correctif ENUM (retrait de 'tableau_honneur' sur conduites_releves, MySQL
 * uniquement), celui-ci doit aussi s'appliquer a SQLite (utilise par la
 * suite de tests) car on AJOUTE une valeur reellement utilisee par les
 * tests — Laravel 12 sait desormais alterer un ENUM nativement sur les deux
 * pilotes sans doctrine/dbal (verifie empiriquement, non installe ici).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->enum('type_evaluation', ['sequence', 'cc', 'session_normale', 'rattrapage'])->change();
        });
    }

    public function down(): void
    {
        DB::table('notes')->where('type_evaluation', 'rattrapage')->delete();

        Schema::table('notes', function (Blueprint $table) {
            $table->enum('type_evaluation', ['sequence', 'cc', 'session_normale'])->change();
        });
    }
};
