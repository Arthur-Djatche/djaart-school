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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('affectation_id')->constrained('affectations_enseignant')->cascadeOnDelete();
            $table->foreignId('apprenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('semestre_id')->nullable()->constrained()->cascadeOnDelete();
            $table->enum('type_evaluation', ['sequence', 'cc', 'session_normale']);
            $table->decimal('valeur', 4, 2)->nullable();
            $table->boolean('absent')->default(false);
            $table->timestamp('soumis_le')->nullable();
            $table->timestamps();

            $table->index('etablissement_id');
            $table->index(['affectation_id', 'sequence_id', 'semestre_id', 'type_evaluation'], 'notes_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
