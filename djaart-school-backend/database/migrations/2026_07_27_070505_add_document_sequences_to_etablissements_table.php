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
        Schema::table('etablissements', function (Blueprint $table) {
            $table->unsignedInteger('next_attestation_sequence')->default(1)->after('next_recu_sequence');
            $table->unsignedInteger('next_carte_sequence')->default(1)->after('next_attestation_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table) {
            $table->dropColumn(['next_attestation_sequence', 'next_carte_sequence']);
        });
    }
};
