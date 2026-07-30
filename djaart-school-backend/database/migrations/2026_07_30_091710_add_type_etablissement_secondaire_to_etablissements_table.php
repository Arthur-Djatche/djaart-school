<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un etablissement peut cumuler jusqu'a 2 types (ex. secondaire + centre
     * de formation) — type_etablissement reste le type principal (obligatoire,
     * inchange), ce champ est le second type optionnel.
     */
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->string('type_etablissement_secondaire')->nullable()->after('type_etablissement');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn('type_etablissement_secondaire');
        });
    }
};
