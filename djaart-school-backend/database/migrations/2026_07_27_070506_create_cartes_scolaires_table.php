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
        Schema::create('cartes_scolaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->unsignedInteger('numero_duplicata')->default(0);
            $table->date('date_emission');
            $table->date('date_expiration');
            $table->string('fichier_pdf');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['etablissement_id', 'numero'], 'cartes_scolaires_etablissement_numero_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartes_scolaires');
    }
};
