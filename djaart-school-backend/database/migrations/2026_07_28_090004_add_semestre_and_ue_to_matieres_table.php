<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une matiere (EC) LMD est desormais scopee a un semestre precis (et a une
 * unite d'enseignement) plutot que partagee sur tout le niveau — necessaire
 * pour qu'un EC n'apparaisse que dans le semestre ou il est reellement
 * enseigne. Reste nullable : les matieres classique (systeme sequence)
 * continuent de ne dependre que du niveau, sans semestre ni UE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->string('code')->nullable()->after('nom');
            $table->foreignId('semestre_id')->nullable()->after('niveau_id')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('unite_enseignement_id')->nullable()->after('semestre_id')
                ->constrained('unites_enseignement')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unite_enseignement_id');
            $table->dropConstrainedForeignId('semestre_id');
            $table->dropColumn('code');
        });
    }
};
