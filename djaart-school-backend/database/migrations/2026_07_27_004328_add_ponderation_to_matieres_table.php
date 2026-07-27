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
        Schema::table('matieres', function (Blueprint $table) {
            $table->unsignedTinyInteger('ponderation_cc')->default(40)->after('credits_ects');
            $table->unsignedTinyInteger('ponderation_session_normale')->default(60)->after('ponderation_cc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matieres', function (Blueprint $table) {
            $table->dropColumn(['ponderation_cc', 'ponderation_session_normale']);
        });
    }
};
