<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_scolarite', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->decimal('montant_total', 10, 2);
            $table->unsignedTinyInteger('nombre_tranches');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['niveau_id', 'annee_academique_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_scolarite');
    }
};
