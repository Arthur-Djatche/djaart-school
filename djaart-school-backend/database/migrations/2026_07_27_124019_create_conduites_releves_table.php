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
        Schema::create('conduites_releves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('absences')->default(0);
            $table->unsignedInteger('absences_non_justifiees')->default(0);
            $table->unsignedInteger('retards')->default(0);
            $table->unsignedInteger('retards_non_justifies')->default(0);
            $table->enum('mention_travail', ['tableau_honneur', 'encouragements', 'avertissement', 'blame'])->nullable();
            $table->enum('mention_conduite', ['tableau_honneur', 'encouragements', 'avertissement', 'blame'])->nullable();
            $table->timestamps();

            $table->unique(['inscription_id', 'sequence_id'], 'conduites_releves_inscription_sequence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conduites_releves');
    }
};
