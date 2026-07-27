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
        Schema::create('attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['scolarite', 'frequentation', 'reussite']);
            $table->unsignedInteger('numero');
            $table->string('fichier_pdf');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['etablissement_id', 'numero'], 'attestations_etablissement_numero_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
