<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('frais_scolarite_id')->constrained('frais_scolarite')->cascadeOnDelete();
            $table->enum('statut', ['en_cours', 'validee', 'suspendue', 'annulee', 'cloturee'])->default('en_cours');
            $table->enum('type_inscription', ['nouvelle', 'reinscription'])->default('nouvelle');
            $table->date('date_inscription');
            $table->timestamps();

            $table->index('etablissement_id');
            $table->index(['classe_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
