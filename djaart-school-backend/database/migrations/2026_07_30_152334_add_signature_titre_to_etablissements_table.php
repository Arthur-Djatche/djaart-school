<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grade/titre du signataire (Le Directeur, La Directrice, Le Fondateur...)
     * affiche a cote de l'image de signature sur les documents PDF, a la
     * place du texte "Le Directeur / La Directrice" jusque-la fige en dur.
     */
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->string('signature_titre')->nullable()->after('signature');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn('signature_titre');
        });
    }
};
