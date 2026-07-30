<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail matiere par matiere (nom, coefficient, valeur, absent) de la
     * sequence, deja calcule a la cloture mais jusqu'ici jamais persiste
     * (seuls les details_groupes, des sous-totaux, l'etaient) — necessaire
     * pour reconstruire le bulletin annuel detaille sans tout recalculer.
     */
    public function up(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->json('details_lignes')->nullable()->after('details_groupes');
        });
    }

    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->dropColumn('details_lignes');
        });
    }
};
