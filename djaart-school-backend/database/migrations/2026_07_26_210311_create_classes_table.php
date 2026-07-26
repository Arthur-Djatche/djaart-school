<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('niveau_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->string('libelle');
            $table->unsignedSmallInteger('effectif_max')->default(50);
            $table->timestamps();

            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
