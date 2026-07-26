<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annees_academiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut', ['en_preparation', 'en_cours', 'cloturee'])->default('en_preparation');
            $table->timestamps();

            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annees_academiques');
    }
};
