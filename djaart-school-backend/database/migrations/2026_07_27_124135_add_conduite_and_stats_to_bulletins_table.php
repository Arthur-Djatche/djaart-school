<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->json('details_groupes')->nullable()->after('rang');
            $table->decimal('moyenne_classe', 4, 2)->nullable()->after('details_groupes');
            $table->decimal('taux_reussite', 5, 1)->nullable()->after('moyenne_classe');
            $table->decimal('moyenne_max', 4, 2)->nullable()->after('taux_reussite');
            $table->decimal('moyenne_min', 4, 2)->nullable()->after('moyenne_max');
            $table->unsignedInteger('absences')->default(0)->after('moyenne_min');
            $table->unsignedInteger('absences_non_justifiees')->default(0)->after('absences');
            $table->unsignedInteger('retards')->default(0)->after('absences_non_justifiees');
            $table->string('mention_travail')->nullable()->after('retards');
            $table->string('mention_conduite')->nullable()->after('mention_travail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->dropColumn([
                'details_groupes', 'moyenne_classe', 'taux_reussite', 'moyenne_max', 'moyenne_min',
                'absences', 'absences_non_justifiees', 'retards', 'mention_travail', 'mention_conduite',
            ]);
        });
    }
};
