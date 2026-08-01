<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Meme pattern que la table commandes : le super_admin valide une demande
 * de demo, ce qui cree l'etablissement (+ admin) qu'elle reference ici.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandes_demo', function (Blueprint $table) {
            $table->string('statut')->default('en_attente')->after('message');
            $table->foreignId('etablissement_id')->nullable()->after('statut')->constrained()->nullOnDelete();
            $table->foreignId('traite_par_id')->nullable()->after('etablissement_id')->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable()->after('traite_par_id');
        });
    }

    public function down(): void
    {
        Schema::table('demandes_demo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('etablissement_id');
            $table->dropConstrainedForeignId('traite_par_id');
            $table->dropColumn(['statut', 'traite_le']);
        });
    }
};
