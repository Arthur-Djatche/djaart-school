<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le chef est desormais porte par le Departement (une specialite/filiere
 * appartient a un departement, qui a un seul chef), pas par la Filiere
 * elle-meme — cf. correctif "departements universitaires".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('filieres', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chef_departement_id');
        });
    }

    public function down(): void
    {
        Schema::table('filieres', function (Blueprint $table) {
            $table->foreignId('chef_departement_id')->nullable()->after('code')
                ->constrained('users')->nullOnDelete();
        });
    }
};
