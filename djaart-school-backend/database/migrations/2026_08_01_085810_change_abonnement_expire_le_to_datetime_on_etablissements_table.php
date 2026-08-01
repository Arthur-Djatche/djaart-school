<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passe de date (jour pres) a datetime : necessaire pour les acces de demo
 * limites a 48h (cf. DemandeDemoController::valider) — une simple date
 * n'aurait pas la precision de l'heure de validation, faussant la duree
 * reelle accordee de plusieurs heures selon le moment de la journee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dateTime('abonnement_expire_le')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->date('abonnement_expire_le')->nullable()->change();
        });
    }
};
