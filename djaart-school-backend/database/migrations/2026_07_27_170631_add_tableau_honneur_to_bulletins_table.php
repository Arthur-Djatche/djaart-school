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
            $table->boolean('tableau_honneur')->default(false)->after('mention_travail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bulletins', function (Blueprint $table) {
            $table->dropColumn('tableau_honneur');
        });
    }
};
