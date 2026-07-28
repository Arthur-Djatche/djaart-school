<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unites_enseignement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etablissement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semestre_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('nom');
            $table->enum('type', ['fondamentale', 'professionnelle', 'transversale']);
            $table->timestamps();

            $table->index('etablissement_id');
            $table->unique(['semestre_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unites_enseignement');
    }
};
