<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ville');
            $table->unsignedInteger('nombre_apprenants');
            $table->string('telephone');
            $table->string('email');
            $table->string('nom_etablissement');
            $table->enum('statut', ['en_attente', 'validee', 'rejetee'])->default('en_attente');
            $table->foreignId('etablissement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('traite_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_le')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
